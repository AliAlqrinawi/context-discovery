<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Assembly;

use ContextDiscovery\Assembly\ItemPriority;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Bundle\BundleItem;
use ContextDiscovery\Domain\Bundle\Lever;
use ContextDiscovery\Domain\Bundle\Provenance;
use ContextDiscovery\Tests\Support\BuildsBundles;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The bands are reproduced from 01-architecture.md §3.4. They are a drop order, not a relevance
 * score — these tests pin the order, they make no claim about which context matters more.
 */
#[CoversClass(ItemPriority::class)]
final class ItemPriorityTest extends TestCase
{
    use BuildsBundles;

    private ItemPriority $priority;

    protected function setUp(): void
    {
        $this->priority = new ItemPriority();
    }

    #[DataProvider('fetchedKindsAndBands')]
    public function testAFetchedItemIsBandedByItsKind(AssertionKind $kind, int $expected): void
    {
        self::assertSame($expected, $this->band(Lever::Fetched, $kind));
    }

    /**
     * @return iterable<string, array{AssertionKind, int}>
     */
    public static function fetchedKindsAndBands(): iterable
    {
        yield 'a missing import is own-file substrate' => [AssertionKind::SameFileSymbolAbsence, 2];
        yield 'a same-file sibling is own-file substrate' => [AssertionKind::SameFileReference, 2];
        yield 'call sites are the sharpest differential' => [AssertionKind::ChangedSignature, 3];
        yield 'a changed return contract asks the same question' => [AssertionKind::ChangedReturnContract, 3];
        yield 'a cross-file reference is dropped first' => [AssertionKind::NamedReference, 4];
    }

    #[DataProvider('everyKind')]
    public function testAFlaggedItemIsNeverDroppedWhateverItsKind(AssertionKind $kind): void
    {
        self::assertSame(
            ItemPriority::NEVER_DROPPED,
            $this->band(Lever::Flagged, $kind)
        );
    }

    /**
     * @return iterable<string, array{AssertionKind}>
     */
    public static function everyKind(): iterable
    {
        foreach (AssertionKind::cases() as $kind) {
            yield $kind->value => [$kind];
        }
    }

    public function testBandingUsesOnlyTheKindAndTheLever(): void
    {
        // The same kind and lever must band identically regardless of provenance or payload —
        // the enforcer never learns which resolver produced an item (freeze review 04). In v2 the
        // kind arrives on the assertion rather than the item; the rule is unchanged.
        $first = $this->claim(AssertionKind::SameFileReference, 'a');
        $second = $this->claim(AssertionKind::SameFileReference, 'b');

        $one = new BundleItem(
            Lever::Fetched,
            $first->id,
            new Provenance('app/One.php', 'upsertFromPlaid', 70, 90),
            'x',
            10,
        );
        $other = new BundleItem(
            Lever::Fetched,
            $second->id,
            new Provenance('app/Totally/Different.php'),
            'y',
            9999,
        );

        self::assertSame($this->priority->of($one, $first), $this->priority->of($other, $second));
    }

    public function testTheOwnFileBandOutranksTheCrossFileBand(): void
    {
        // This is the distinction ACP-01 existed to make possible: Exp 1's `upsertFromPlaid`
        // sibling must survive longer than the `PlaidAccount` model surface.
        self::assertLessThan(
            $this->band(Lever::Fetched, AssertionKind::NamedReference),
            $this->band(Lever::Fetched, AssertionKind::SameFileReference)
        );
    }

    private function band(Lever $lever, AssertionKind $kind): int
    {
        $claim = $this->claim($kind, 'a reason');

        return $this->priority->of(
            new BundleItem($lever, $claim->id, new Provenance('app/One.php'), 'payload', 10),
            $claim,
        );
    }
}
