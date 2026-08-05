<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Domain\Bundle;

use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Bundle\BundleItem;
use ContextDiscovery\Domain\Bundle\Lever;
use ContextDiscovery\Domain\Bundle\Provenance;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * P5: no item enters the bundle without a reason and a lever. An item with no reason is either
 * waste or an untraceable guess — both are defects, so construction is rejected rather than
 * warned about.
 */
#[CoversClass(BundleItem::class)]
final class BundleItemTest extends TestCase
{
    public function testAnItemWithAReasonAndALeverIsAccepted(): void
    {
        $item = new BundleItem(
            lever: Lever::Fetched,
            reason: 'changed call site depends on PlaidAccount::forItem() and official_name',
            assertionKind: AssertionKind::NamedReference,
            provenance: new Provenance('app/Models/PlaidAccount.php', 'forItem', 41, 58),
            payload: '<minimal slice>',
            tokens: 180,
        );

        self::assertSame(Lever::Fetched, $item->lever);
        self::assertSame(
            'changed call site depends on PlaidAccount::forItem() and official_name',
            $item->reason
        );
    }

    #[DataProvider('emptyReasons')]
    public function testAnItemWithoutAReasonIsRejected(string $reason): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty reason');

        new BundleItem(
            lever: Lever::Flagged,
            reason: $reason,
            assertionKind: AssertionKind::UnverifiablePremise,
            provenance: new Provenance('app/Services/Plaid/PlaidAccountService.php'),
            payload: 'ASSUMPTION: this code assumes a surrounding transaction; caller not checked',
            tokens: 14,
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function emptyReasons(): iterable
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
