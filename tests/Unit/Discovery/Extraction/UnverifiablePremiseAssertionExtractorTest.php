<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Extraction;

use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use ContextDiscovery\Discovery\Extraction\UnverifiablePremiseAssertionExtractor;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedFile;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * One case per trigger in ADR-A009: each trigger present ⇒ exactly one premise; each trigger
 * absent ⇒ none. A premise is emitted if and only if its literal trigger is there.
 */
#[CoversClass(UnverifiablePremiseAssertionExtractor::class)]
final class UnverifiablePremiseAssertionExtractorTest extends TestCase
{
    private UnverifiablePremiseAssertionExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new UnverifiablePremiseAssertionExtractor(new TokenizerMemberSlicer());
    }

    // ------------------------------------------------------------ trigger present

    public function testTwoPersistenceWritesWithNoLocalTransactionRaiseTheTransactionPremise(): void
    {
        // Exp 1: the reconciliation is safe only inside a transaction, and the transaction lives
        // in the caller.
        $assertions = $this->extract([
            '        $account->save();',
            '        $stale->delete();',
        ]);

        self::assertSame(['surrounding-transaction'], $this->subjects($assertions));
        self::assertSame(AssertionKind::UnverifiablePremise, $assertions[0]->kind);
        self::assertStringContainsString('decided by the caller', $assertions[0]->claim);
    }

    public function testACacheLockRaisesTheAtomicStorePremise(): void
    {
        // Exp 3: the lock is a cross-process mutex only if the deployed store is atomic.
        self::assertSame(
            ['atomic-lock-store'],
            $this->subjects($this->extract(["        Cache::lock('plaid:'.\$id)->block(5);"]))
        );
    }

    #[DataProvider('rowLockForms')]
    public function testARowLockRaisesTheSchemaIndexPremise(string $line): void
    {
        // Exp 3: the DB lock's usefulness hinges on the schema behind the predicate.
        self::assertSame(['schema-index-support'], $this->subjects($this->extract([$line])));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function rowLockForms(): iterable
    {
        yield 'lockForUpdate' => ['        $query->lockForUpdate()->first();'];
        yield 'sharedLock' => ['        $query->sharedLock()->get();'];
    }

    public function testARemovedTraitUseRaisesTheDataStatePremise(): void
    {
        // Exp 3: dropping SoftDeletes makes pre-existing trashed rows visible again.
        $assertions = $this->extractor->forRegion(
            $this->file(),
            new ChangedRegion(12, 14, [], ['    use SoftDeletes;']),
            $this->source(),
        );

        self::assertSame(['data-state-after-behaviour-change'], $this->subjects($assertions));
    }

    public function testSeveralTriggersInOneRegionRaiseOnePremiseEach(): void
    {
        $assertions = $this->extractor->forRegion(
            $this->file(),
            new ChangedRegion(
                12,
                18,
                [
                    "        Cache::lock('x')->block(5);",
                    '        $row = $query->lockForUpdate()->first();',
                    '        $account->save();',
                    '        $stale->delete();',
                ],
                ['    use SoftDeletes;'],
            ),
            $this->source(),
        );

        self::assertSame(
            [
                'surrounding-transaction',
                'atomic-lock-store',
                'schema-index-support',
                'data-state-after-behaviour-change',
            ],
            $this->subjects($assertions)
        );
    }

    // ------------------------------------------------------------ trigger absent

    public function testOnePersistenceWriteIsNotEnough(): void
    {
        self::assertSame([], $this->extract(['        $account->save();']));
    }

    public function testAMemberThatOpensItsOwnTransactionRaisesNothing(): void
    {
        // The premise is about a transaction the *caller* must provide. If the member opens one,
        // there is nothing unverified.
        $assertions = $this->extractor->forRegion(
            $this->file(),
            new ChangedRegion(20, 24, ['            $account->save();', '            $stale->delete();'], []),
            $this->source(),
        );

        self::assertSame([], $this->subjects($assertions));
    }

    public function testWritesOutsideAnyMemberRaiseNothing(): void
    {
        // The enclosing member cannot be inspected, so the second half of the trigger cannot be
        // established. Not firing is the conservative direction.
        $assertions = $this->extractor->forRegion(
            $this->file(),
            new ChangedRegion(1, 2, ['$account->save();', '$stale->delete();'], []),
            $this->source(),
        );

        self::assertSame([], $this->subjects($assertions));
    }

    public function testABareFunctionOfTheSameNameIsNotAPersistenceWrite(): void
    {
        self::assertSame([], $this->extract([
            '        $a = create($thing);',
            '        $b = update($other);',
        ]));
    }

    #[DataProvider('linesThatLookLikeTriggersButAreNot')]
    public function testATriggerNamedInACommentOrStringDoesNotFire(string $line): void
    {
        self::assertSame([], $this->extract([$line]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function linesThatLookLikeTriggersButAreNot(): iterable
    {
        yield 'a comment' => ['        // Cache::lock( was removed here'];
        yield 'a docblock' => ['        /** uses lockForUpdate( previously */'];
        yield 'a string' => ["        \$note = 'Cache::lock( is not used';"];
        yield 'a different facade' => ["        Redis::lock('x');"];
        yield 'a similarly named method' => ['        $query->lockForUpdateLater();'];
    }

    #[DataProvider('removedLinesThatAreNotTraitUses')]
    public function testARemovedImportIsNotATraitRemoval(string $line): void
    {
        $assertions = $this->extractor->forRegion(
            $this->file(),
            new ChangedRegion(12, 14, [], [$line]),
            $this->source(),
        );

        self::assertSame([], $this->subjects($assertions));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function removedLinesThatAreNotTraitUses(): iterable
    {
        yield 'a top-level import' => ['use App\Models\PlaidAccount;'];
        yield 'an indented qualified name' => ['    use App\Concerns\SoftDeletes;'];
        yield 'a function import' => ['use function array_map;'];
        yield 'a closure use clause' => ['        $fn = function () use ($x) {'];
        yield 'an aliased import' => ['use App\Models\PlaidItem as Item;'];
    }

    public function testExperimentTwosTraceLoggingShapeYieldsZeroPremises(): void
    {
        // The precision control. Its diff names no Cache::lock, no row lock, removes no trait and
        // adds no persistence write — so nothing fires, and the bundle stays almost empty.
        self::assertSame([], $this->extract([
            "        Log::info('TEMPORARY TRACE: incoming', ['count' => count(\$accounts)]);",
            "        Log::info('TEMPORARY TRACE: stored', \$this->traceStoredRows(\$item));",
            "        Log::info('TEMPORARY TRACE: candidate', ['id' => \$candidate->fresh()->id]);",
            '        $alreadyStored = $stored->contains($incoming->account_id);',
            "        Log::info('TEMPORARY TRACE: type', ['t' => gettype(\$alreadyStored)]);",
        ]));
    }

    public function testARegionMatchingNoTriggerYieldsNothing(): void
    {
        self::assertSame([], $this->extract([
            '        $mapped = array_map($fn, $accounts);',
            '        return $mapped;',
        ]));
    }

    public function testAnEmptyRegionYieldsNothing(): void
    {
        self::assertSame([], $this->extract([]));
    }

    // ------------------------------------------------------------ helpers

    /**
     * @param list<string> $addedLines
     *
     * @return list<Assertion>
     */
    private function extract(array $addedLines): array
    {
        return $this->extractor->forRegion(
            $this->file(),
            new ChangedRegion(12, 12 + max(count($addedLines), 1), $addedLines, []),
            $this->source(),
        );
    }

    /**
     * @param list<Assertion> $assertions
     *
     * @return list<string>
     */
    private function subjects(array $assertions): array
    {
        return array_map(static fn (Assertion $assertion): string => $assertion->subject, $assertions);
    }

    private function file(): ChangedFile
    {
        return new ChangedFile('app/Services/Plaid/PlaidAccountService.php', [], []);
    }

    /**
     * `syncFromResponse` opens no transaction; `wrapped` opens its own.
     */
    private function source(): string
    {
        return <<<'PHP'
<?php

namespace App\Services\Plaid;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlaidAccountService
{
    use SoftDeletes;

    public function syncFromResponse($item, array $accounts): void
    {
        $account->save();
        $stale->delete();
    }

    public function wrapped($item): void
    {
        DB::transaction(function () use ($item) {
            $account->save();
            $stale->delete();
        });
    }
}
PHP;
    }
}
