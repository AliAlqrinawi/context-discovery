<?php

declare(strict_types=1);

namespace ContextDiscovery\Ports;

/**
 * Read-only, root-scoped access to the repository under review.
 *
 * *Repository* here means the repository under review — the persistence-pattern ban on the name
 * is unaffected (02-project-structure.md §5).
 *
 * Implementations must never write inside the repository root, and must reject any resolved path
 * that escapes it. An unreadable or out-of-scope path becomes a flag, never a silent omission
 * (P10).
 */
interface SourceRepository
{
    public function exists(string $relativePath): bool;

    /**
     * @return string|null Null when the path is missing, unreadable, or outside the root.
     */
    public function text(string $relativePath): ?string;

    /**
     * @return list<string> Sorted lexicographically — required by P8. Directory iteration order
     *                      is filesystem- and platform-dependent, so without this the bounded
     *                      caller search could return a different subset on two machines.
     */
    public function filesUnder(string $prefix, string $extension): array;
}
