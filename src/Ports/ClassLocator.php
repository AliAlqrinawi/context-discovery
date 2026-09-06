<?php

declare(strict_types=1);

namespace ContextDiscovery\Ports;

/**
 * Fully-qualified class name to file path, via the PSR-4 autoload map.
 *
 * Depth one, and no index: the map is read from the repository's composer.json so a class name
 * resolves to a path without anything being built or maintained (P9). The resolved file's own
 * references are never followed (P3, P4).
 */
interface ClassLocator
{
    /**
     * @return string|null Null when no PSR-4 entry covers the name. The assertion is then flagged
     *                     as `unresolved-reference`, never silently dropped (P10).
     */
    public function pathFor(string $fullyQualifiedClass): ?string;

    /**
     * Whether a placed path is part of **this project's own source**, as opposed to an installed
     * dependency's.
     *
     * The same map answers both questions, which is why they share a port: `pathFor()` says where a
     * class lives, this says whose it is. The distinction is what stops the bare-class *surface*
     * move — earned by Experiment 1's model and Experiment 4's enum, both project classes — from
     * being applied to a dependency, where it degenerates into the whole-file dump the spec forbids
     * (ADR-A012).
     *
     * No file is opened to answer it: the path alone is enough.
     */
    public function isProjectSource(string $relativePath): bool;

    /**
     * Whether any PSR-4 prefix covers this class name, regardless of whether the file it points at
     * exists.
     *
     * `pathFor()` returns null for two different states — no prefix covers the name, and a prefix
     * covers it but no file is there — and the two call for different diagnostics: the first is a
     * mapping the project has not declared, the second is a file that is not on disk. Reporting the
     * first when the truth is the second says the opposite of what happened (ADR-A017).
     *
     * Reads the map already in memory. No file is opened to answer it.
     */
    public function hasMappingFor(string $fullyQualifiedClass): bool;
}
