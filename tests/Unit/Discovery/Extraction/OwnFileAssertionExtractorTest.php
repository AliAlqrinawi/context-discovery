<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Extraction;

use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use ContextDiscovery\Discovery\Extraction\OwnFileAssertionExtractor;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedFile;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(OwnFileAssertionExtractor::class)]
final class OwnFileAssertionExtractorTest extends TestCase
{
    private OwnFileAssertionExtractor $extractor;

    protected function setUp(): void
    {
        // The slicer is a pure text transformation; no filesystem is involved.
        $this->extractor = new OwnFileAssertionExtractor(new TokenizerMemberSlicer());
    }

    public function testASymbolTheUseBlockDoesNotImportYieldsAnAbsence(): void
    {
        // The case forward-import-following structurally cannot see: there is no reference to
        // follow, only a missing one. Experiment 1's `Log`.
        $assertions = $this->extract([
            "        Log::info('syncing', ['item' => \$item->id]);",
        ]);

        self::assertCount(1, $assertions);
        self::assertSame(AssertionKind::SameFileSymbolAbsence, $assertions[0]->kind);
        self::assertSame('Log', $assertions[0]->subject);
        self::assertStringContainsString('use block does not import', $assertions[0]->claim);
        self::assertSame('app/Services/Plaid/PlaidAccountService.php', $assertions[0]->originPath);
    }

    public function testAnImportedClassIsNotAnAbsence(): void
    {
        // `PlaidAccount` is in the use block, so it is a cross-file reference for the other
        // extractor to raise — not an absence.
        self::assertSame([], $this->extract(['        $account = new PlaidAccount();']));
    }

    public function testTheFilesOwnTypeIsNotAnAbsence(): void
    {
        self::assertSame([], $this->extract(['        $x = PlaidAccountService::class;']));
    }

    public function testASiblingCallYieldsASameFileReferenceAndNeverANamedReference(): void
    {
        // Freeze review 04: `$this->method(` is research context type 1, not type 2.
        $assertions = $this->extract(['        $this->upsertFromPlaid($item, $accounts);']);

        self::assertCount(1, $assertions);
        self::assertSame(AssertionKind::SameFileReference, $assertions[0]->kind);
        self::assertSame('upsertFromPlaid', $assertions[0]->subject);
        self::assertStringContainsString('sibling member', $assertions[0]->claim);
    }

    public function testThisExtractorNeverEmitsACrossFileNamedReference(): void
    {
        $assertions = $this->extract([
            "        Log::info('x');",
            '        $this->upsertFromPlaid($item, $accounts);',
            '        $account = new PlaidAccount();',
            '        $repo = new PlaidAccountRepository();',
        ]);

        foreach ($assertions as $assertion) {
            self::assertNotSame(AssertionKind::NamedReference, $assertion->kind);
        }
    }

    public function testACallToSomethingThatIsNotAMemberOfThisFileYieldsNothing(): void
    {
        // Inherited or magic; resolving it would need the parent, which is depth two (P3).
        self::assertSame([], $this->extract(['        $this->inheritedFromParent();']));
    }

    public function testAPropertyReadIsNotASiblingCall(): void
    {
        self::assertSame([], $this->extract(['        $x = $this->upsertFromPlaid;']));
    }

    #[DataProvider('formsThatAreNotClassReferences')]
    public function testAnUnrecognisedFormYieldsNoAssertion(string $line): void
    {
        self::assertSame([], $this->extract([$line]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function formsThatAreNotClassReferences(): iterable
    {
        yield 'a plain function call' => ['        $x = array_map($fn, $rows);'];
        yield 'a scalar type hint' => ['        $total = (int) $value;'];
        yield 'a string that looks like a class' => ["        \$name = 'Log::info';"];
        yield 'a comment' => ['        // Log::info was removed'];
        yield 'a local variable' => ['        $accounts = [];'];
        yield 'self' => ['        self::FOO;'];
        yield 'static' => ['        static::bar();'];
        yield 'parent' => ['        parent::__construct();'];
    }

    public function testRemovedLinesAreNotWhatTheChangeAsserts(): void
    {
        $region = new ChangedRegion(20, 20, [], ["        Log::info('gone');"]);

        self::assertSame([], $this->extractor->forRegion($this->file(), $region, $this->source()));
    }

    public function testTheSameSymbolTwiceInOneRegionIsOneAssertion(): void
    {
        $assertions = $this->extract([
            "        Log::info('first');",
            "        Log::warning('second');",
        ]);

        self::assertCount(1, $assertions);
    }

    public function testExperimentOnesRegionYieldsBothOwnFileKinds(): void
    {
        $assertions = $this->extract([
            "        Log::info('syncing', ['item' => \$item->id]);",
            '        $this->upsertFromPlaid($item, $accounts);',
        ]);

        self::assertSame(
            [
                ['same_file_symbol_absence', 'Log'],
                ['same_file_reference', 'upsertFromPlaid'],
            ],
            array_map(
                static fn (Assertion $a): array => [$a->kind->value, $a->subject],
                $assertions
            )
        );
    }

    /**
     * @param list<string> $addedLines
     *
     * @return list<Assertion>
     */
    private function extract(array $addedLines): array
    {
        return $this->extractor->forRegion(
            $this->file(),
            new ChangedRegion(20, 20 + count($addedLines), $addedLines, []),
            $this->source(),
        );
    }

    private function file(): ChangedFile
    {
        return new ChangedFile('app/Services/Plaid/PlaidAccountService.php', [], []);
    }

    private function source(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Services\Plaid;

use App\Models\PlaidAccount;
use App\Repositories\PlaidAccountRepository;

class PlaidAccountService
{
    public function syncFromResponse(PlaidItem $item, array $accounts): void
    {
        $this->upsertFromPlaid($item, $accounts);
    }

    public function upsertFromPlaid(PlaidItem $item, array $accounts): void
    {
    }
}
PHP;
    }
}
