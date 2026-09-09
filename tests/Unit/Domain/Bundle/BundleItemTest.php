<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Domain\Bundle;

use ContextDiscovery\Domain\Bundle\BundleItem;
use ContextDiscovery\Domain\Bundle\Lever;
use ContextDiscovery\Domain\Bundle\Provenance;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * P5, as v2 states it: nothing is shown to a reviewer without a stated reason.
 *
 * The reason itself moved onto the assertion, so what an item must carry is the pointer to it. An
 * item naming no assertion is unjustified context — the same defect the old per-item `reason`
 * guard existed to reject — so construction fails rather than warns. `BundleTest` completes the
 * invariant by rejecting an item whose pointer resolves to nothing (ADR-A024).
 */
#[CoversClass(BundleItem::class)]
final class BundleItemTest extends TestCase
{
    public function testAnItemNamingAnAssertionAndALeverIsAccepted(): void
    {
        $item = new BundleItem(
            lever: Lever::Fetched,
            assertionId: 'a0123456789ab',
            provenance: new Provenance('app/Models/PlaidAccount.php', 'forItem', 41, 58),
            payload: '<minimal slice>',
            tokens: 180,
        );

        self::assertSame(Lever::Fetched, $item->lever);
        self::assertSame('a0123456789ab', $item->assertionId);
    }

    #[DataProvider('emptyIds')]
    public function testAnItemNamingNoAssertionIsRejected(string $assertionId): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must name the assertion');

        new BundleItem(
            lever: Lever::Flagged,
            assertionId: $assertionId,
            provenance: new Provenance('app/Services/Plaid/PlaidAccountService.php'),
            payload: 'ASSUMPTION: this code assumes a surrounding transaction; caller not checked',
            tokens: 14,
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function emptyIds(): iterable
    {
        yield 'empty string' => [''];
        yield 'spaces' => ['   '];
        yield 'newline' => ["\n"];
        yield 'tab' => ["\t"];
    }

    /**
     * The lever is not checkable at runtime because it is not forgeable: it is a required,
     * non-nullable enum parameter, so an item without one cannot be expressed. This test records
     * that the type system carries half of the P5 invariant.
     */
    public function testTheLeverIsRequiredByTheTypeSystem(): void
    {
        $constructor = (new \ReflectionClass(BundleItem::class))->getConstructor();
        self::assertNotNull($constructor);

        $lever = $constructor->getParameters()[0];

        self::assertSame('lever', $lever->getName());
        self::assertFalse($lever->isOptional());
        self::assertFalse($lever->allowsNull());
    }
}
