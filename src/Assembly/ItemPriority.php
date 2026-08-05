<?php

declare(strict_types=1);

namespace ContextDiscovery\Assembly;

use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Bundle\BundleItem;
use ContextDiscovery\Domain\Bundle\Lever;

/**
 * The drop order, and nothing else.
 *
 * Used **only** by BudgetEnforcer, and only for dropping. It is **not** a relevance signal: the
 * engine assigns no severity, no score and no ranking (P6, X4). A lower band survives longer; it
 * does not mean an item matters more to a reviewer.
 *
 * Every band is a function of `assertionKind` and `lever` — two fields a BundleItem already
 * carries — so the enforcer never needs to know which resolver produced an item (freeze review
 * 04). Before the five kinds partitioned the five moves one-to-one, this was not computable.
 *
 * The order is stated in 01-architecture.md §3.4 and is reproduced, not invented, here.
 */
final class ItemPriority
{
    /**
     * Flags cost almost nothing, and a silent omission is indistinguishable from "nothing
     * needed" — which would corrupt the precision measurement (P10, `fetch-vs-flag.md`).
     */
    public const NEVER_DROPPED = 1;

    public const DROPPED_FIRST = 4;

    public function of(BundleItem $item): int
    {
        if ($item->lever === Lever::Flagged) {
            return self::NEVER_DROPPED;
        }

        return match ($item->assertionKind) {
            // Free on disk, and the substrate every other move builds on (R1, Exp 1).
            AssertionKind::SameFileSymbolAbsence,
            AssertionKind::SameFileReference => 2,

            // The recurring high-severity move and the sharpest A-vs-C differential (R3, Exp 1, 4).
            AssertionKind::ChangedSignature => 3,

            // Valuable and cheap, but the move Exp 2 shows must never be pulled speculatively.
            AssertionKind::NamedReference => self::DROPPED_FIRST,

            // Unreachable: LeverPolicy flags every premise, so a fetched one cannot exist. If one
            // ever does, protect it rather than drop it — dropping what cannot be classified is
            // the silent omission P10 forbids.
            AssertionKind::UnverifiablePremise => self::NEVER_DROPPED,
        };
    }
}
