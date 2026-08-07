<?php

declare(strict_types=1);

namespace ContextDiscovery\Assembly;

use ContextDiscovery\Domain\Bundle\Bundle;
use ContextDiscovery\Domain\Bundle\BundleItem;
use ContextDiscovery\Domain\Bundle\DroppedItem;

/**
 * Brings a bundle within its budget by dropping the lowest-priority items — visibly.
 *
 * The whole hypothesis is about doing well *under* condition B's cost, so a design with no budget
 * cannot demonstrate it, and a design that drops context silently cannot be debugged (P7). Every
 * drop is recorded with the reason the item was included in the first place.
 *
 * **Flagged items are never dropped.** If the flags alone exceed the operator's budget, the
 * bundle is emitted with `used_tokens` above `budget_tokens` rather than quietly shedding
 * concerns to make the arithmetic look right (P10; approved decision D4). The overage is visible
 * in the bundle itself — `usedTokens > budgetTokens` — and the CLI reports it on stderr. Emitting
 * an honest, over-budget bundle is a success; hiding a concern to satisfy a field rule is not.
 */
final class BudgetEnforcer
{
    /**
     * The note recorded against every budget drop, matching the bundle schema's sample.
     */
    private const DROP_NOTE = 'below budget priority';

    public function __construct(private readonly ItemPriority $priority)
    {
    }

    public function enforce(Bundle $bundle): Bundle
    {
        $items = $bundle->items;
        $dropped = $bundle->dropped;
        $used = $this->sum($items);

        while ($used > $bundle->budgetTokens) {
            $index = $this->nextToDrop($items);

            if ($index === null) {
                // Only never-dropped items remain. Stop, and let the bundle say so (D4).
                break;
            }

            $item = $items[$index];
            $dropped[] = new DroppedItem($item->reason, self::DROP_NOTE, $item->tokens);

            unset($items[$index]);
            $items = array_values($items);
            $used = $this->sum($items);
        }

        return new Bundle(
            items: $items,
            dropped: $dropped,
            budgetTokens: $bundle->budgetTokens,
            usedTokens: $used,
        );
    }

    /**
     * The next item to lose: the lowest-priority band present, and within that band the largest
     * estimate, so the fewest items are lost (01-architecture.md §3.4).
     *
     * Ties are broken toward the later item, so the same bundle always sheds the same items (P8).
     *
     * @param list<BundleItem> $items
     */
    private function nextToDrop(array $items): ?int
    {
        $dropIndex = null;
        $dropBand = 0;
        $dropTokens = -1;

        foreach ($items as $index => $item) {
            $band = $this->priority->of($item);

            if ($band === ItemPriority::NEVER_DROPPED) {
                continue;
            }

            if ($band > $dropBand || ($band === $dropBand && $item->tokens >= $dropTokens)) {
                $dropIndex = $index;
                $dropBand = $band;
                $dropTokens = $item->tokens;
            }
        }

        return $dropIndex;
    }

    /**
     * @param list<BundleItem> $items
     */
    private function sum(array $items): int
    {
        return array_sum(array_map(static fn (BundleItem $item): int => $item->tokens, $items));
    }
}
