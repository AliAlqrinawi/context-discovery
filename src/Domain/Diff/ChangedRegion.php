<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Diff;

/**
 * One changed region of a changed file — a hunk's added and removed lines, and the span they
 * occupy in the current file.
 *
 * Added and removed lines are kept apart because the two are read for different things: added
 * lines are what the change asserts, removed lines are where an old signature and a removed
 * `use` statement are found.
 *
 * Knows nothing about PHP semantics, assertions, or context.
 */
final class ChangedRegion
{
    /**
     * @param int          $firstLine    First line of the region in the current file (1-indexed).
     * @param int          $lastLine     Last line of the region in the current file (1-indexed).
     * @param list<string> $addedLines   Lines added by this hunk, without the leading '+'.
     * @param list<string> $removedLines Lines removed by this hunk, without the leading '-'.
     */
    public function __construct(
        public readonly int $firstLine,
        public readonly int $lastLine,
        public readonly array $addedLines,
        public readonly array $removedLines,
    ) {
    }
}
