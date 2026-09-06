<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Diff;

/**
 * The parsed diff: what changed.
 *
 * Knows files, regions and member signatures. Knows nothing about PHP semantics, about context,
 * or about diff *text* — the text is turned into this by Discovery\Parsing\UnifiedDiffParser.
 *
 * Diff types never reference Assertion or Bundle types (02-project-structure.md §3).
 */
final class Diff
{
    /**
     * @param list<ChangedFile> $files
     */
    public function __construct(
        public readonly array $files,
    ) {
    }

    /**
     * Whether the diff already shows every line of a span, so a reader holding the diff has it.
     *
     * Two ways that can be true, and they are different evidence (ADR-A019):
     *
     * - the file was **created**, so every one of its lines is an added line;
     * - the file was **modified** and one changed region contains the span **entirely**.
     *
     * Containment must be total. A declaration that merely overlaps a region is half-shown, which is
     * not the contract, so it stays fetchable. And a file appearing in the diff proves nothing on its
     * own — a modified file shows only its hunks, and the span asked about may be nowhere near them.
     *
     * Line numbers on both sides are post-change: a region's are `@@ … +start,count @@`, and a
     * slice's come from the file as it exists in the checkout under review.
     */
    public function showsEntirely(string $path, int $firstLine, int $lastLine): bool
    {
        if ($firstLine > $lastLine) {
            return false;
        }

        foreach ($this->files as $file) {
            if ($file->path !== $path) {
                continue;
            }

            if ($file->isNew) {
                return true;
            }

            foreach ($file->regions as $region) {
                if ($region->firstLine <= $firstLine && $region->lastLine >= $lastLine) {
                    return true;
                }
            }
        }

        return false;
    }
}
