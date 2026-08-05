<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Pipeline;

use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use ContextDiscovery\Assembly\BudgetEnforcer;
use ContextDiscovery\Assembly\BundleAssembler;
use ContextDiscovery\Assembly\ItemPriority;
use ContextDiscovery\Assembly\TokenEstimate;
use ContextDiscovery\Discovery\Extraction\AssertionExtractor;
use ContextDiscovery\Discovery\Extraction\OwnFileAssertionExtractor;
use ContextDiscovery\Discovery\Flagging\AssumptionWriter;
use ContextDiscovery\Discovery\Lever\LeverPolicy;
use ContextDiscovery\Discovery\Parsing\UnifiedDiffParser;
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

    private function discover(string $diffText, int $budget, ?FakeSourceRepository $source = null): Bundle
    {
        $source ??= new FakeSourceRepository([self::PATH => $this->source()]);
        $slicer = new TokenizerMemberSlicer();

        $context = new DiscoverContext(
            new UnifiedDiffParser(),
            $source,
            new AssertionExtractor([new OwnFileAssertionExtractor($slicer)]),
            new LeverPolicy(),
            new FakeClassLocator(),
            new OwnFileResolver($source, $slicer),
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
