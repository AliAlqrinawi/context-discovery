<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Pipeline;

use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use ContextDiscovery\Assembly\BudgetEnforcer;
use ContextDiscovery\Assembly\BundleAssembler;
use ContextDiscovery\Assembly\ItemPriority;
use ContextDiscovery\Assembly\TokenEstimate;
use ContextDiscovery\Discovery\Extraction\AssertionExtractor;
use ContextDiscovery\Discovery\Extraction\ChangedSignatureAssertionExtractor;
use ContextDiscovery\Discovery\Extraction\NamedReferenceAssertionExtractor;
use ContextDiscovery\Discovery\Extraction\OwnFileAssertionExtractor;
use ContextDiscovery\Discovery\Flagging\AssumptionWriter;
use ContextDiscovery\Discovery\Lever\LeverPolicy;
use ContextDiscovery\Discovery\Parsing\UnifiedDiffParser;
use ContextDiscovery\Adapters\Search\ScopedGrepCallSiteSearch;
use ContextDiscovery\Discovery\Resolution\CallerResolver;
use ContextDiscovery\Discovery\Resolution\NamedReferenceResolver;
use ContextDiscovery\Discovery\Resolution\OwnFileResolver;
use ContextDiscovery\Domain\Bundle\Bundle;
use ContextDiscovery\Domain\Bundle\BundleItem;
use ContextDiscovery\Pipeline\DiscoverContext;
use ContextDiscovery\Tests\Fakes\FakeClassLocator;
use ContextDiscovery\Tests\Fakes\FakeSourceRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The walking skeleton, end to end: diff text in, context bundle out, with only the own-file move
 * wired. Everything but the class locator is the real collaborator.
 */
#[CoversClass(DiscoverContext::class)]
final class DiscoverContextTest extends TestCase
{
    private const PATH = 'app/Services/Plaid/PlaidAccountService.php';

    private ?FakeClassLocator $locator = null;

    private int $maxCallSites = 20;

    private string $callerScope = 'app/';

    /** @var list<string> */
    private array $diagnostics = [];

    protected function setUp(): void
    {
        $this->diagnostics = [];
    }

    public function testADiffBecomesABundleOfOwnFileSlices(): void
    {
        $bundle = $this->discover($this->diff(), 8000);

        self::assertSame(
            [
                ['same_file_symbol_absence', 'fetched', null],
                ['same_file_symbol_absence', 'fetched', 'syncFromResponse'],
                ['same_file_reference', 'fetched', 'upsertFromPlaid'],
            ],
            array_map(
                static fn (BundleItem $item): array => [
                    $item->assertionKind->value,
                    $item->lever->value,
                    $item->provenance->member,
                ],
                $bundle->items
            )
        );

        self::assertSame([], $bundle->dropped);
        self::assertSame(8000, $bundle->budgetTokens);
        self::assertGreaterThan(0, $bundle->usedTokens);
    }

    public function testEveryItemCarriesAReasonAndALever(): void
    {
        foreach ($this->discover($this->diff(), 8000)->items as $item) {
            self::assertNotSame('', trim($item->reason));
        }
    }

    public function testTheFullChangedFileNeverBecomesPayload(): void
    {
        // ADR-A005: reading the changed file is free; including any part of it must be justified.
        foreach ($this->discover($this->diff(), 8000)->items as $item) {
            self::assertNotSame($this->source(), $item->payload);
            self::assertStringNotContainsString('declare(strict_types=1);', $item->payload);
        }
    }

    public function testTheSameDiffAndRepositoryProduceTheSameBundle(): void
    {
        self::assertEquals($this->discover($this->diff(), 8000), $this->discover($this->diff(), 8000));
    }

    public function testTheBudgetIsEnforcedAndDropsAreRecorded(): void
    {
        $bundle = $this->discover($this->diff(), 12);

        self::assertNotSame([], $bundle->dropped);
        self::assertLessThanOrEqual($bundle->budgetTokens, $bundle->usedTokens);

        foreach ($bundle->dropped as $dropped) {
            self::assertSame('below budget priority', $dropped->note);
        }
    }

    public function testAnUnreadableChangedFileIsDiagnosedRatherThanIgnored(): void
    {
        $bundle = $this->discover($this->diff(), 8000, new FakeSourceRepository());

        self::assertSame([], $bundle->items, 'nothing can be asserted about a file that is not there');
        self::assertCount(1, $this->diagnostics);
        self::assertStringContainsString('unreadable path', $this->diagnostics[0]);
        self::assertStringContainsString(self::PATH, $this->diagnostics[0]);
    }

    public function testAnEmptyDiffIsAnEmptyBundleNotAFailure(): void
    {
        // Experiment 2's correct answer is an almost-empty bundle; emptiness is a result.
        $bundle = $this->discover('', 8000);

        self::assertSame([], $bundle->items);
        self::assertSame([], $bundle->dropped);
        self::assertSame(0, $bundle->usedTokens);
    }

    public function testADiffTouchingNothingResolvableProducesNoItems(): void
    {
        $diff = implode("\n", [
            '--- a/README.md',
            '+++ b/README.md',
            '@@ -1,1 +1,2 @@',
            ' # Title',
            '+a documentation line',
        ]) . "\n";

        self::assertSame([], $this->discover($diff, 8000)->items);
    }

    public function testACrossFileReferenceIsResolvedThroughThePsr4Map(): void
    {
        $this->locator = new FakeClassLocator(['App\Models\PlaidAccount' => 'app/Models/PlaidAccount.php']);

        $bundle = $this->discover(
            $this->diffNaming('$account = PlaidAccount::forItem($item);'),
            8000,
            new FakeSourceRepository([
                self::PATH => $this->source(),
                'app/Models/PlaidAccount.php' => "<?php\n\nclass PlaidAccount\n{\n    public function forItem(\$item)\n    {\n    }\n}\n",
            ]),
        );

        $named = array_values(array_filter(
            $bundle->items,
            static fn (BundleItem $item): bool => $item->assertionKind->value === 'named_reference'
        ));

        self::assertCount(1, $named);
        self::assertSame('fetched', $named[0]->lever->value);
        self::assertSame('app/Models/PlaidAccount.php', $named[0]->provenance->path);
        self::assertSame('forItem', $named[0]->provenance->member);
        self::assertSame([], $this->diagnostics);
    }

    public function testAReferenceThePsr4MapCannotPlaceIsFlaggedAndDiagnosed(): void
    {
        // "Missing map ⇒ every NamedReference becomes a flag, plus one diagnostic."
        $bundle = $this->discover($this->diffNaming('$account = PlaidAccount::forItem($item);'), 8000);

        $named = array_values(array_filter(
            $bundle->items,
            static fn (BundleItem $item): bool => $item->assertionKind->value === 'named_reference'
        ));

        self::assertCount(1, $named);
        self::assertSame('flagged', $named[0]->lever->value);
        self::assertStringContainsString('could not be resolved on disk', $named[0]->payload);

        self::assertCount(1, $this->diagnostics);
        self::assertStringContainsString('missing PSR-4 entry', $this->diagnostics[0]);
        self::assertStringContainsString('App\Models\PlaidAccount::forItem', $this->diagnostics[0]);
    }

    public function testTheCrossFileSliceIsBandedBelowTheOwnFileSlices(): void
    {
        // ACP-01's point: under budget pressure the model surface goes before the sibling does.
        $this->locator = new FakeClassLocator(['App\Models\PlaidAccount' => 'app/Models/PlaidAccount.php']);

        $bundle = $this->discover(
            $this->diffNaming('$account = PlaidAccount::forItem($item);'),
            10,
            new FakeSourceRepository([
                self::PATH => $this->source(),
                'app/Models/PlaidAccount.php' => "<?php\n\nclass PlaidAccount\n{\n    public function forItem(\$item)\n    {\n        return 1;\n    }\n}\n",
            ]),
        );

        self::assertNotSame([], $bundle->dropped);

        foreach ($bundle->items as $item) {
            self::assertNotSame('named_reference', $item->assertionKind->value);
        }
    }

    public function testAChangedSignatureIsResolvedToItsCallSites(): void
    {
        $bundle = $this->discover($this->signatureDiff(), 8000, $this->repositoryWithCallers());

        $callSites = $this->itemsOfKind($bundle, 'changed_signature');

        self::assertCount(2, $callSites);

        foreach ($callSites as $item) {
            self::assertSame('fetched', $item->lever->value);
            self::assertSame($item->provenance->firstLine, $item->provenance->lastLine);
            self::assertStringContainsString('reactivate(', $item->payload);
        }

        self::assertSame('app/Controllers/PlaidController.php', $callSites[0]->provenance->path);
    }

    public function testABoundedSearchThatOverflowsFetchesTheBoundAndFlagsTheRest(): void
    {
        // ADR-A006: the excess is recorded as a flag, never a silent truncation (P10).
        $this->maxCallSites = 1;

        $bundle = $this->discover($this->signatureDiff(), 8000, $this->repositoryWithCallers());

        $items = $this->itemsOfKind($bundle, 'changed_signature');

        self::assertCount(2, $items, 'one call site kept, one truncation flag');

        $levers = array_map(static fn (BundleItem $item): string => $item->lever->value, $items);

        self::assertContains('fetched', $levers);
        self::assertContains('flagged', $levers);

        $flag = array_values(array_filter(
            $items,
            static fn (BundleItem $item): bool => $item->lever->value === 'flagged'
        ))[0];

        self::assertStringContainsString('beyond the search bound', $flag->payload);

        self::assertCount(1, array_filter(
            $this->diagnostics,
            static fn (string $line): bool => str_contains($line, 'call sites truncated at 1')
        ));
    }

    public function testACallerSearchThatFindsNothingIsReportedRatherThanAssumed(): void
    {
        // The premise catalogue has no statement for "no callers here", so nothing untrue is put
        // in the bundle; the search and its empty result are reported instead.
        $bundle = $this->discover($this->signatureDiff(), 8000, new FakeSourceRepository([
            self::PATH => $this->source(),
        ]));

        self::assertSame([], $this->itemsOfKind($bundle, 'changed_signature'));
        self::assertCount(1, array_filter(
            $this->diagnostics,
            static fn (string $line): bool => str_contains($line, 'caller search for reactivate( under app/: 0 call sites')
        ));
    }

    public function testAScopeThatCouldNotBeSearchedIsALookupFailureAndIsFlagged(): void
    {
        // Freeze review 05 keeps P10 intact for genuine failures: nothing was searched, so the
        // concern is stated rather than dropped.
        $this->callerScope = 'src/';

        $bundle = $this->discover($this->signatureDiff(), 8000, $this->repositoryWithCallers());

        $items = $this->itemsOfKind($bundle, 'changed_signature');

        self::assertCount(1, $items);
        self::assertSame('flagged', $items[0]->lever->value);

        // Freeze review 06: the caller search has its own failure premise. `unresolved-reference`
        // stays exclusive to NamedReferenceResolver — it would say something false here.
        self::assertSame(
            'ASSUMPTION: callers of this signature could not be searched; scope unreadable',
            $items[0]->payload
        );
        self::assertStringNotContainsString('named reference', $items[0]->payload);

        self::assertCount(1, array_filter(
            $this->diagnostics,
            static fn (string $line): bool => str_contains($line, 'unresolved changed_signature')
        ));
    }

    public function testAChangedFileWithNothingToSliceIsASuccessfulNegative(): void
    {
        // The other side of the same rule: the file was read, and it has no use block and no
        // enclosing member — an answer, not a failure.
        $diff = implode("\n", [
            '--- a/app/Bare.php',
            '+++ b/app/Bare.php',
            '@@ -1,1 +1,2 @@',
            ' <?php',
            "+Log::info('x');",
        ]) . "\n";

        $bundle = $this->discover($diff, 8000, new FakeSourceRepository([
            'app/Bare.php' => "<?php\n\nLog::info('x');\n",
        ]));

        self::assertSame([], $bundle->items);
        self::assertCount(1, array_filter(
            $this->diagnostics,
            static fn (string $line): bool => str_contains($line, 'own-file lookup for Log')
        ));
        self::assertSame([], array_filter(
            $this->diagnostics,
            static fn (string $line): bool => str_contains($line, 'unresolved')
        ));
    }

    /**
     * @return list<BundleItem>
     */
    private function itemsOfKind(Bundle $bundle, string $kind): array
    {
        return array_values(array_filter(
            $bundle->items,
            static fn (BundleItem $item): bool => $item->assertionKind->value === $kind
        ));
    }

    private function signatureDiff(): string
    {
        return implode("\n", [
            '--- a/' . self::PATH,
            '+++ b/' . self::PATH,
            '@@ -12,1 +12,1 @@',
            '-    public function reactivate(PlaidItem $item, array $data): void',
            '+    public function reactivate(PlaidItem $item, string $token): void',
        ]) . "\n";
    }

    private function repositoryWithCallers(): FakeSourceRepository
    {
        return new FakeSourceRepository([
            self::PATH => $this->source(),
            'app/Controllers/PlaidController.php' => "<?php\n\n\$this->items->reactivate(\$item, \$token);\n",
            'app/Services/Sync.php' => "<?php\n\n\$other->reactivate(\$item);\n",
        ]);
    }

    private function diffNaming(string $addedLine): string
    {
        return implode("\n", [
            '--- a/' . self::PATH,
            '+++ b/' . self::PATH,
            '@@ -12,2 +12,3 @@',
            '     public function syncFromResponse(PlaidItem $item, array $accounts): void',
            '     {',
            '+        ' . $addedLine,
        ]) . "\n";
    }

    private function discover(string $diffText, int $budget, ?FakeSourceRepository $source = null): Bundle
    {
        $source ??= new FakeSourceRepository([self::PATH => $this->source()]);
        $slicer = new TokenizerMemberSlicer();

        $locator = $this->locator ?? new FakeClassLocator();

        $context = new DiscoverContext(
            new UnifiedDiffParser(),
            $source,
            new AssertionExtractor([
                new OwnFileAssertionExtractor($slicer),
                new NamedReferenceAssertionExtractor($slicer),
                new ChangedSignatureAssertionExtractor(),
            ]),
            new LeverPolicy(),
            $locator,
            new OwnFileResolver($source, $slicer),
            new NamedReferenceResolver($locator, $source, $slicer),
            new CallerResolver(new ScopedGrepCallSiteSearch($source), $source, $this->callerScope, $this->maxCallSites),
            new AssumptionWriter(),
            new BundleAssembler(new TokenEstimate()),
            new BudgetEnforcer(new ItemPriority()),
        );

        return $context->run(
            $diffText,
            $budget,
            function (string $line): void {
                $this->diagnostics[] = $line;
            },
        );
    }

    private function diff(): string
    {
        return implode("\n", [
            'diff --git a/' . self::PATH . ' b/' . self::PATH,
            '--- a/' . self::PATH,
            '+++ b/' . self::PATH,
            '@@ -12,2 +12,4 @@',
            '     public function syncFromResponse(PlaidItem $item, array $accounts): void',
            '     {',
            "+        Log::info('syncing');",
            '+        $this->upsertFromPlaid($item, $accounts);',
        ]) . "\n";
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
        Log::info('syncing');
        $this->upsertFromPlaid($item, $accounts);
    }

    public function upsertFromPlaid(PlaidItem $item, array $accounts): void
    {
    }
}
PHP;
    }
}
