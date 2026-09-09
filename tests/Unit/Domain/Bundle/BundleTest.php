<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Domain\Bundle;

use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Bundle\Bundle;
use ContextDiscovery\Domain\Bundle\BundleAssertion;
use ContextDiscovery\Domain\Bundle\BundleItem;
use ContextDiscovery\Domain\Bundle\Lever;
use ContextDiscovery\Domain\Bundle\Provenance;
use ContextDiscovery\Tests\Support\BuildsBundles;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The other half of P5 under v2: an item may name an assertion, but the assertion has to be there.
 *
 * v1 could not get this wrong, because the reason travelled inside the item. v2 buys de-duplicated
 * output at the price of a reference that could dangle, so the reference is checked where it cannot
 * be skipped — on construction, before anything is serialised (ADR-A024).
 */
#[CoversClass(Bundle::class)]
final class BundleTest extends TestCase
{
    use BuildsBundles;

    public function testAnItemNamingAnAssertionTheBundleCarriesIsAccepted(): void
    {
        $claim = $this->claim(AssertionKind::NamedReference, 'a reason');

        $bundle = $this->bundleOf(8000, [
            new BundleItem(Lever::Fetched, $claim->id, new Provenance('app/One.php'), 'x', 10),
        ]);

        self::assertCount(1, $bundle->items);
        self::assertCount(1, $bundle->assertions);
        self::assertSame(8000, $bundle->budgetTokens());
    }

    public function testAnOrphanItemIsRejectedRatherThanSerialised(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Orphan bundle item');

        new Bundle(
            assertions: [new BundleAssertion('a000000000001', AssertionKind::NamedReference, 's', 'r', 'app/One.php')],
            items: [new BundleItem(Lever::Fetched, 'affffffffffff', new Provenance('app/Two.php'), 'x', 10)],
            diagnostics: [],
            dropped: [],
            run: $this->runMetadata(),
            usedTokens: 10,
        );
    }

    public function testAnAssertionWithNoItemsIsFineBecauseEvidenceCanBeCollapsedOrDropped(): void
    {
        // ADR-A021 collapses two indistinguishable items into one, and the budget drops others.
        // Neither makes the surviving bundle invalid: the guard is one-directional by design.
        $bundle = new Bundle(
            assertions: [new BundleAssertion('a000000000001', AssertionKind::NamedReference, 's', 'r', 'app/One.php')],
            items: [],
            diagnostics: [],
            dropped: [],
            run: $this->runMetadata(),
            usedTokens: 0,
        );

        self::assertSame([], $bundle->items);
        self::assertCount(1, $bundle->assertions);
    }

    public function testAnEmptyBundleIsAResultRatherThanAFailure(): void
    {
        $bundle = new Bundle([], [], [], [], $this->runMetadata(), 0);

        self::assertSame([], $bundle->assertions);
        self::assertSame([], $bundle->items);
        self::assertSame([], $bundle->diagnostics);
        self::assertSame([], $bundle->dropped);
        self::assertSame(0, $bundle->usedTokens);
    }
}
