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

    public function testAFileWithNoImportsYieldsNothing(): void
    {
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
