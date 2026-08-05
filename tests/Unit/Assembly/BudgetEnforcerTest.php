<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Assembly;

use ContextDiscovery\Assembly\BudgetEnforcer;
use ContextDiscovery\Assembly\ItemPriority;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Bundle\Bundle;
use ContextDiscovery\Domain\Bundle\BundleItem;
use ContextDiscovery\Domain\Bundle\Lever;
use ContextDiscovery\Domain\Bundle\Provenance;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BudgetEnforcer::class)]
final class BudgetEnforcerTest extends TestCase
{
    private BudgetEnforcer $enforcer;

    protected function setUp(): void
    {
        $this->enforcer = new BudgetEnforcer(new ItemPriority());
    }

    public function testABundleWithinBudgetIsLeftAlone(): void
    {
        $bundle = $this->bundle(500, [
            $this->fetched(AssertionKind::NamedReference, 'model surface', 100),
            $this->flagged('transaction premise', 20),
        ]);

        $enforced = $this->enforcer->enforce($bundle);

        self::assertCount(2, $enforced->items);
        self::assertSame([], $enforced->dropped);
        self::assertSame(120, $enforced->usedTokens);
        self::assertSame(500, $enforced->budgetTokens);
    }

    public function testDropsFollowTheBandOrderLowestPriorityFirst(): void
    {
        $bundle = $this->bundle(200, [
            $this->flagged('a premise', 100),
            $this->fetched(AssertionKind::SameFileSymbolAbsence, 'use block', 50),
            $this->fetched(AssertionKind::ChangedSignature, 'call sites', 50),
            $this->fetched(AssertionKind::NamedReference, 'model surface', 50),
        ]);

        $enforced = $this->enforcer->enforce($bundle);

        self::assertSame(['model surface'], array_column($this->drops($enforced), 'reason'));
        self::assertSame(200, $enforced->usedTokens);
    }

    public function testDroppingContinuesUpTheBandsUntilTheBudgetIsMet(): void
    {
        $bundle = $this->bundle(100, [
            $this->flagged('a premise', 100),
            $this->fetched(AssertionKind::SameFileSymbolAbsence, 'use block', 50),
            $this->fetched(AssertionKind::SameFileReference, 'sibling method', 50),
            $this->fetched(AssertionKind::ChangedSignature, 'call sites', 50),
            $this->fetched(AssertionKind::NamedReference, 'model surface', 50),
        ]);

        $enforced = $this->enforcer->enforce($bundle);

        // Band 4, then band 3, then the two band-2 items — in that order.
        self::assertSame(
            ['model surface', 'call sites', 'sibling method', 'use block'],
            array_column($this->drops($enforced), 'reason')
        );
        self::assertCount(1, $enforced->items);
        self::assertSame(Lever::Flagged, $enforced->items[0]->lever);
        self::assertSame(100, $enforced->usedTokens);
    }

    public function testWithinABandTheLargestItemGoesFirstSoTheFewestAreLost(): void
    {
        $bundle = $this->bundle(100, [
            $this->fetched(AssertionKind::NamedReference, 'small', 30),
            $this->fetched(AssertionKind::NamedReference, 'large', 70),
            $this->fetched(AssertionKind::NamedReference, 'medium', 50),
        ]);

        $enforced = $this->enforcer->enforce($bundle);

        self::assertSame(['large'], array_column($this->drops($enforced), 'reason'));
        self::assertSame(80, $enforced->usedTokens);
    }

    public function testEveryDropIsRecordedWithItsReasonNoteAndTokens(): void
    {
        $bundle = $this->bundle(10, [
            $this->fetched(AssertionKind::NamedReference, 'route wiring for reauth endpoint', 260),
        ]);

        $enforced = $this->enforcer->enforce($bundle);

        self::assertCount(1, $enforced->dropped);
        self::assertSame('route wiring for reauth endpoint', $enforced->dropped[0]->reason);
        self::assertSame('below budget priority', $enforced->dropped[0]->note);
        self::assertSame(260, $enforced->dropped[0]->tokens);
        self::assertSame(0, $enforced->usedTokens, 'nothing is lost silently (P7)');
    }

    public function testAFlaggedItemIsNeverDroppedEvenWhenTheFlagsAloneExceedTheBudget(): void
    {
        // Approved decision D4: P10 wins. The bundle is emitted, over budget and honest, rather
        // than shedding concerns to make the arithmetic look right.
        $bundle = $this->bundle(10, [
            $this->flagged('surrounding transaction', 20),
            $this->flagged('atomic lock store', 20),
            $this->flagged('schema index support', 20),
        ]);

        $enforced = $this->enforcer->enforce($bundle);

        self::assertCount(3, $enforced->items);
        self::assertSame([], $enforced->dropped);
        self::assertSame(60, $enforced->usedTokens);
        self::assertGreaterThan(
            $enforced->budgetTokens,
            $enforced->usedTokens,
            'the overage is visible in the bundle, and the CLI reports it on stderr'
        );
    }

    public function testFetchedItemsAreShedBeforeTheBudgetIsDeclaredUnmeetable(): void
    {
        $bundle = $this->bundle(10, [
            $this->flagged('a premise', 40),
            $this->fetched(AssertionKind::NamedReference, 'model surface', 100),
            $this->fetched(AssertionKind::SameFileReference, 'sibling method', 100),
        ]);

        $enforced = $this->enforcer->enforce($bundle);

        self::assertSame(['model surface', 'sibling method'], array_column($this->drops($enforced), 'reason'));
        self::assertCount(1, $enforced->items);
        self::assertSame(40, $enforced->usedTokens);
    }

    public function testUsedTokensAlwaysMatchesTheSurvivingItems(): void
    {
        $enforced = $this->enforcer->enforce($this->bundle(120, [
            $this->fetched(AssertionKind::NamedReference, 'a', 60),
            $this->fetched(AssertionKind::NamedReference, 'b', 60),
            $this->fetched(AssertionKind::NamedReference, 'c', 60),
        ]));

        self::assertSame(
            array_sum(array_map(static fn (BundleItem $item): int => $item->tokens, $enforced->items)),
            $enforced->usedTokens
        );
    }

    public function testSurvivingItemsKeepTheirOriginalOrder(): void
    {
        $enforced = $this->enforcer->enforce($this->bundle(100, [
            $this->fetched(AssertionKind::SameFileSymbolAbsence, 'first', 40),
            $this->fetched(AssertionKind::NamedReference, 'dropped', 90),
            $this->fetched(AssertionKind::ChangedSignature, 'second', 40),
        ]));

        self::assertSame(['first', 'second'], array_column($this->items($enforced), 'reason'));
    }

    public function testEnforcingTheSameBundleTwiceShedsTheSameItems(): void
    {
        $bundle = $this->bundle(100, [
            $this->fetched(AssertionKind::NamedReference, 'tie a', 60),
            $this->fetched(AssertionKind::NamedReference, 'tie b', 60),
            $this->fetched(AssertionKind::NamedReference, 'tie c', 60),
        ]);

        self::assertEquals($this->enforcer->enforce($bundle), $this->enforcer->enforce($bundle));
    }

    public function testAnEmptyBundleSurvivesEnforcementUnchanged(): void
    {
        $enforced = $this->enforcer->enforce($this->bundle(8000, []));

        self::assertSame([], $enforced->items);
        self::assertSame([], $enforced->dropped);
        self::assertSame(0, $enforced->usedTokens);
    }

    // ---------------------------------------------------------------- helpers

    /**
     * @param list<BundleItem> $items
     */
    private function bundle(int $budget, array $items): Bundle
    {
        return new Bundle(
            items: $items,
            dropped: [],
            budgetTokens: $budget,
            usedTokens: array_sum(array_map(static fn (BundleItem $item): int => $item->tokens, $items)),
        );
    }

    private function fetched(AssertionKind $kind, string $reason, int $tokens): BundleItem
    {
        return new BundleItem(Lever::Fetched, $reason, $kind, new Provenance('app/One.php'), 'payload', $tokens);
    }

    private function flagged(string $reason, int $tokens): BundleItem
    {
        return new BundleItem(
            Lever::Flagged,
            $reason,
            AssertionKind::UnverifiablePremise,
            new Provenance('app/One.php'),
            'ASSUMPTION: ...',
            $tokens,
        );
    }

    /**
     * @return list<array{reason:string,note:string,tokens:int}>
     */
    private function drops(Bundle $bundle): array
    {
        return array_map(
            static fn ($drop): array => [
                'reason' => $drop->reason,
                'note' => $drop->note,
                'tokens' => $drop->tokens,
            ],
            $bundle->dropped
        );
    }

    /**
     * @return list<array{reason:string}>
     */
    private function items(Bundle $bundle): array
    {
        return array_map(static fn (BundleItem $item): array => ['reason' => $item->reason], $bundle->items);
    }
}
