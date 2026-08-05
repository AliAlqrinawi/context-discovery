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
}
