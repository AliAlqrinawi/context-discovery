<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Extraction;

use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use ContextDiscovery\Discovery\Extraction\NamedReferenceAssertionExtractor;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedFile;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * R2's recall is exactly the three cross-file forms the contract lists. Everything else yields
 * nothing — that is the closed set, not an oversight (ADR-A003).
 */
#[CoversClass(NamedReferenceAssertionExtractor::class)]
final class NamedReferenceAssertionExtractorTest extends TestCase
{
    private NamedReferenceAssertionExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new NamedReferenceAssertionExtractor(new TokenizerMemberSlicer());
    }

    // ------------------------------------------------------------ the three recognised forms

    public function testFormOneAStaticOrEnumMemberAccess(): void
    {
        // Exp 4: `PlaidItemStatus::REVOKED`.
        self::assertSame(
            ['App\Enums\PlaidItemStatus::REVOKED'],
            $this->subjects(['        if ($item->status === PlaidItemStatus::REVOKED) {'])
        );
    }

    public function testFormTwoAMethodOnATypedCollaboratorProperty(): void
    {
        // Exp 4: `$this->plaidClient->createLinkToken(`.
        self::assertSame(
            ['App\Clients\PlaidClient::createLinkToken'],
            $this->subjects(['        $token = $this->plaidClient->createLinkToken($item);'])
        );
    }

    public function testFormTwoResolvesThroughAnAliasedImport(): void
    {
        self::assertSame(
            ['App\Repositories\PlaidItemRepository::findByPlaidItemId'],
            $this->subjects(['        $row = $this->items->findByPlaidItemId($id);'])
        );
    }

    #[DataProvider('classNamePositions')]
    public function testFormThreeAClassNameInANewTypeOrStaticCallPosition(string $line): void
    {
        // Exp 1: the `PlaidAccount` model surface.
        self::assertSame(['App\Models\PlaidAccount'], $this->subjects([$line]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function classNamePositions(): iterable
    {
        yield 'new' => ['        $account = new PlaidAccount();'];
        yield 'parameter type' => ['    public function handle(PlaidAccount $account): void'];
        yield 'return type' => ['    public function first(): PlaidAccount'];
        yield 'nullable type' => ['    public function maybe(): ?PlaidAccount'];
        yield 'instanceof' => ['        if ($row instanceof PlaidAccount) {'];
    }

    public function testAllThreeFormsInOneRegion(): void
    {
        self::assertSame(
            [
                'App\Enums\PlaidItemStatus::REVOKED',
                'App\Clients\PlaidClient::createLinkToken',
                'App\Models\PlaidAccount',
            ],
            $this->subjects([
                '        if ($item->status === PlaidItemStatus::REVOKED) {',
                '            $token = $this->plaidClient->createLinkToken($item);',
                '        }',
                '        $account = new PlaidAccount();',
            ])
        );
    }

    public function testAMemberAccessYieldsOneAssertionNotTwo(): void
    {
        // `Name::member` is form 1; the bare-class scan must not also claim it.
        $assertions = $this->extract(['        $x = PlaidAccount::forItem($item);']);

        self::assertCount(1, $assertions);
        self::assertSame('App\Models\PlaidAccount::forItem', $assertions[0]->subject);
    }

    public function testEveryAssertionIsANamedReferenceCarryingItsOrigin(): void
    {
        $assertions = $this->extract(['        $account = new PlaidAccount();']);

        self::assertSame(AssertionKind::NamedReference, $assertions[0]->kind);
        self::assertSame('app/Services/Plaid/PlaidAccountService.php', $assertions[0]->originPath);
        self::assertStringContainsString('defined in another file', $assertions[0]->claim);
    }

    // ------------------------------------------------------------ form four: an undeclared $this->m(

    /**
     * §3.3's fourth row (ADR-A029): `$this->m(` to a member the changed file does **not** declare.
     * The subject is the *calling* class — the file's own FQCN — because that is the only fact the
     * changed file states; where the member lives is the resolver's question (ADR-A028).
     */
    public function testFormFourAnUndeclaredThisCallNamesTheCallingClass(): void
    {
        $assertions = $this->extract(['        return $this->success($account);']);

        self::assertCount(1, $assertions);
        self::assertSame(AssertionKind::NamedReference, $assertions[0]->kind);
        self::assertSame('App\Services\Plaid\PlaidAccountService::success', $assertions[0]->subject);
        self::assertSame('app/Services/Plaid/PlaidAccountService.php', $assertions[0]->originPath);
        self::assertStringContainsString('does not declare', $assertions[0]->claim);
    }

    public function testFormFourIsNotGatedByTheImportBlock(): void
    {
        // Forms 1–3 need an import to name a class; form 4 names the file's own class, so a file
        // with no `use` block still yields it (ADR-A029 §8 narrows the no-imports test to 1–3).
        $region = new ChangedRegion(20, 21, ['        return $this->success($account);'], []);

        $assertions = $this->extractor->forRegion(
            $this->file(),
            $region,
            "<?php\n\nnamespace App\\Services\\Plaid;\n\nclass Bare\n{\n}\n"
        );

        self::assertCount(1, $assertions);
        self::assertSame('App\Services\Plaid\Bare::success', $assertions[0]->subject);
    }

    public function testFormFourInAGlobalNamespaceClassIsTheBareName(): void
    {
        $region = new ChangedRegion(20, 21, ['        return $this->success($account);'], []);

        $assertions = $this->extractor->forRegion($this->file(), $region, "<?php\n\nclass Bare\n{\n}\n");

        self::assertCount(1, $assertions);
        self::assertSame('Bare::success', $assertions[0]->subject);
    }

    public function testACallOnATypedPropertyIsFormTwoNotFour(): void
    {
        // `$this->prop->m(` names the property's class; the calling class is not the subject.
        self::assertSame(['App\\Clients\\PlaidClient::success'], $this->subjects(['        $this->plaidClient->success();']));
    }

    public function testFormFourOnceForRepeatedCallsInOneRegion(): void
    {
        self::assertCount(1, $this->extract([
            '        $this->success($a);',
            '        $this->success($b);',
        ]));
    }

    #[DataProvider('thisCallsThatAreNotFormFour')]
    public function testFormFourYieldsNothingFor(string $line, string $fileText = ''): void
    {
        $region = new ChangedRegion(20, 21, [$line], []);

        self::assertSame([], $this->extractor->forRegion($this->file(), $region, $fileText === '' ? $this->source() : $fileText));
    }

    /**
     * @return iterable<string, array{0: string, 1?: string}>
     */
    public static function thisCallsThatAreNotFormFour(): iterable
    {
        // The declared sibling is the own-file move's (form four is *undeclared* only).
        yield 'a declared sibling' => ['        $this->upsertFromPlaid($item, $accounts);'];
        yield 'a property read' => ['        $x = $this->success;'];
        yield 'a dynamic call' => ['        $this->$method($item);'];
        // Shape 6 stays out (ADR-A029 §4): the keyword forms name no class the file states.
        yield 'parent::' => ['        parent::success($account);'];
        yield 'self::' => ['        self::success($account);'];
        yield 'static::' => ['        static::success($account);'];
        // An anonymous class has no FQCN to be the subject.
        yield 'inside an anonymous class' => [
            '        return $this->success($account);',
            "<?php\n\nnamespace App\\Services\\Plaid;\n\n\$x = new class {\n};\n",
        ];
    }

    // ------------------------------------------------------------ everything else yields nothing

    #[DataProvider('formsOutsideTheClosedList')]
    public function testAnUnrecognisedFormYieldsNoAssertion(string $line): void
    {
        self::assertSame([], $this->subjects([$line]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function formsOutsideTheClosedList(): iterable
    {
        yield 'a same-file sibling belongs to the own-file move' => [
            '        $this->upsertFromPlaid($item, $accounts);',
        ];
        yield 'an unimported name is the absence case' => ["        Log::info('syncing');"];
        yield 'a local variable has no declared type' => ['        $client->createLinkToken($item);'];
        yield 'a variable class name' => ['        $x = new $className();'];
        yield 'a class name in a string' => ["        \$name = 'PlaidAccount';"];
        yield 'a class name in a comment' => ['        // PlaidAccount is used below'];
        yield 'a dynamic method call' => ['        $this->plaidClient->$method($item);'];
        yield 'the ::class constant names no contract' => ['        $x = PlaidAccount::class;'];
        yield 'a static property is not a member contract' => ['        $x = PlaidAccount::$cache;'];
        yield 'an untyped property' => ['        $this->untyped->doThing();'];
        yield 'a plain function call' => ['        $rows = array_map($fn, $rows);'];
    }

    public function testRemovedLinesAreNotWhatTheChangeAsserts(): void
    {
        $region = new ChangedRegion(20, 20, [], ['        $account = new PlaidAccount();']);

        self::assertSame([], $this->extractor->forRegion($this->file(), $region, $this->source()));
    }

    public function testAFileWithNoImportsYieldsNothingForFormsOneToThree(): void
    {
        // Narrowed under ADR-A029 §8: forms 1–3 name a class through the import block, so no
        // imports means no assertion. Form 4 is not gated here — see
        // `testFormFourIsNotGatedByTheImportBlock`.
        $region = new ChangedRegion(20, 21, ['        $account = new PlaidAccount();'], []);

        self::assertSame(
            [],
            $this->extractor->forRegion($this->file(), $region, "<?php\n\nclass Bare\n{\n}\n")
        );
    }

    public function testTheSameReferenceTwiceInOneRegionIsOneAssertion(): void
    {
        self::assertCount(1, $this->extract([
            '        $a = new PlaidAccount();',
            '        $b = new PlaidAccount();',
        ]));
    }

    // ------------------------------------------------------------ helpers

    /**
     * @param list<string> $addedLines
     *
     * @return list<string>
     */
    private function subjects(array $addedLines): array
    {
        return array_map(
            static fn (Assertion $assertion): string => $assertion->subject,
            $this->extract($addedLines)
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

use App\Clients\PlaidClient;
use App\Enums\PlaidItemStatus;
use App\Models\PlaidAccount;
use App\Repositories\PlaidItemRepository as ItemRepository;

class PlaidAccountService
{
    public $untyped;

    public function __construct(
        private readonly PlaidClient $plaidClient,
        private ItemRepository $items,
    ) {
    }

    public function upsertFromPlaid(PlaidItem $item, array $accounts): void
    {
    }
}
PHP;
    }
}
