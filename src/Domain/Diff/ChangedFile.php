<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Diff;

/**
 * One file changed by the diff: its path, its changed regions, and the members whose signatures
 * the diff touched.
 *
 * The path is repository-relative. The file's full current text is not held here — it is loaded
 * separately as an analysis input and is never, by itself, bundle payload (ADR-A005).
 */
final class ChangedFile
{
    /**
     * @param list<ChangedRegion> $regions
     * @param list<ChangedMember> $members
     */
    public function __construct(
        public readonly string $path,
        public readonly array $regions,
        public readonly array $members,
    ) {
    }
}
