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
}
