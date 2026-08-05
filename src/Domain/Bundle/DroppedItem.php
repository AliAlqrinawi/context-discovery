<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Bundle;

/**
 * An item removed from the bundle to stay within budget.
 *
 * Drops are visible, never silent. A design that drops context silently cannot be debugged, and a
 * silent omission is indistinguishable from "nothing needed" — which would corrupt the precision
 * measurement the bundle exists to enable (P7, P10).
 */
final class DroppedItem
{
    public function __construct(
        public readonly string $reason,
        public readonly string $note,
        public readonly int $tokens,
    ) {
    }
}
