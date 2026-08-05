<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Bundle;

/**
 * Where a bundle item came from — enough for a human to verify the slice by hand.
 *
 * Plain data, not Diff types (02-project-structure.md §3). Member and line span are optional:
 * a `use` block has no member name, and a flagged item may carry neither.
 */
final class Provenance
{
    public function __construct(
        public readonly string $path,
        public readonly ?string $member = null,
        public readonly ?int $firstLine = null,
        public readonly ?int $lastLine = null,
    ) {
    }
}
