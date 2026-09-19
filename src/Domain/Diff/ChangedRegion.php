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
 * Each line also carries the post-image line it occupies — for an added line, its own number; for
 * a removed line, the number of the post-image line that now stands where it was. The parser knows
 * both for free, and one question needs them: whether a removed `return` left the same member the
 * added one belongs to. The current tree cannot answer that, because the removed line is not in it
 * (ADR-A023, M28). The arrays parallel `addedLines` / `removedLines` and default to empty for a
 * region built by hand; a reader that needs a position and finds none says nothing rather than
 * guessing.
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
     * @param list<int>    $addedAt      Post-image line of each added line, parallel to $addedLines.
     * @param list<int>    $removedAt    Post-image line each removed line was removed *before*,
     *                                   parallel to $removedLines.
     */
    public function __construct(
        public readonly int $firstLine,
        public readonly int $lastLine,
        public readonly array $addedLines,
        public readonly array $removedLines,
        public readonly array $addedAt = [],
        public readonly array $removedAt = [],
    ) {
    }
}
