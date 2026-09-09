<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Bundle;

use InvalidArgumentException;

/**
 * What we are handing the reviewer: the C-condition context.
 *
 * The claims the diff makes, the evidence gathered for each, what the run cost, and everything
 * dropped for budget. Knows nothing about PHP, diffs, or the filesystem
 * (02-project-structure.md §3).
 *
 * An empty bundle is a result, not a failure — on a self-contained commit the correct output is
 * almost nothing (Experiment 2).
 *
 * **The orphan guard is the point of assertions being first-class.** Every item names an assertion,
 * and every named assertion must exist here. A bundle that cannot honour that is rejected on
 * construction rather than serialised: an item pointing at nothing is context with no stated
 * reason, which is precisely what P5 forbids and what the old per-item `reason` field was there to
 * prevent.
 */
final class Bundle
{
    /**
     * @param list<BundleAssertion> $assertions  Every claim, each carrying its own reason (P5).
     * @param list<BundleItem>      $items       Ordered as specified in 03-interfaces.md §2.
     * @param list<Diagnostic>      $diagnostics A machine-readable mirror of stderr. Never counted
     *                                           toward `usedTokens` (freeze review L2, ADR-A024).
     * @param list<DroppedItem>     $dropped     Every budget drop. Empty when nothing was dropped;
     *                                           never omitted (P7).
     * @param int                   $usedTokens  Sum of the *items'* estimated tokens, and of
     *                                           nothing else.
     *
     * @throws InvalidArgumentException if any item names an assertion the bundle does not carry.
     */
    public function __construct(
        public readonly array $assertions,
        public readonly array $items,
        public readonly array $diagnostics,
        public readonly array $dropped,
        public readonly RunMetadata $run,
        public readonly int $usedTokens,
    ) {
        $known = [];

        foreach ($assertions as $assertion) {
            $known[$assertion->id] = true;
        }

        foreach ($items as $item) {
            if (!isset($known[$item->assertionId])) {
                throw new InvalidArgumentException(sprintf(
                    'Orphan bundle item: no assertion "%s" exists for the item at %s. Every item '
                    . 'must be justified by an assertion the bundle carries (P5, ADR-A024).',
                    $item->assertionId,
                    $item->provenance->path,
                ));
            }
        }
    }

    /**
     * Echoes `--budget`. Kept as an accessor because the number moved into `run` in v2 while the
     * pipeline still asks the bundle what it was measured against.
     */
    public function budgetTokens(): int
    {
        return $this->run->budgetTokens;
    }
}
