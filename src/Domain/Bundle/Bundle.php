<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Bundle;

/**
 * What we are handing the reviewer: the C-condition context.
 *
 * An ordered list of items plus token accounting against a budget, and every item dropped for
 * budget with its reason. Knows nothing about PHP, diffs, or the filesystem
 * (02-project-structure.md §3).
 *
 * An empty bundle is a result, not a failure — on a self-contained commit the correct output is
 * almost nothing (Experiment 2).
 */
final class Bundle
{
    /**
     * @param list<BundleItem>  $items      Ordered as specified in 03-interfaces.md §2.
     * @param list<DroppedItem> $dropped    Every budget drop. Empty when nothing was dropped;
     *                                      never omitted (P7).
     * @param int               $usedTokens Sum of the items' estimated tokens.
     */
    public function __construct(
        public readonly array $items,
        public readonly array $dropped,
        public readonly int $budgetTokens,
        public readonly int $usedTokens,
    ) {
    }
}
