<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Diff;

/**
 * One file changed by the diff: its path, its changed regions, and the members whose signatures
 * the diff touched.
 *
 * The path is repository-relative. The file's full current text is not held here — it is loaded
 * separately as an analysis input and is never, by itself, bundle payload (ADR-A005).
 *
 * `isNew` records that the diff *created* this file, which the unified format states as
 * `--- /dev/null`. It matters because ADR-A005's rule bites hardest here: when a file is new, every
 * line of it is an added line, so its own `use` block, its enclosing member and its siblings are all
 * already in front of the reviewer. Fetching them returns the diff as context (ADR-A018).
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
        public readonly bool $isNew = false,
    ) {
    }

    /**
     * Whether this file is PHP, and therefore whether reading it as PHP means anything.
     *
     * A diff routinely carries files that are not: a migration is, a `.yml` is not. Tokenising the
     * second as the first still yields identifiers — `menu:\n  label: Menu` produces `Menu` in what
     * looks like a return-type position — so the question is asked once here rather than re-derived
     * by each caller (ADR-A018).
     */
    public function isPhp(): bool
    {
        return str_ends_with($this->path, '.php');
    }
}
