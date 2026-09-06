<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Fakes;

use ContextDiscovery\Ports\ClassLocator;

/**
 * An in-memory PSR-4 map. A name the map does not carry resolves to null, which is what makes a
 * reference unresolvable and therefore flagged (P10).
 */
final class FakeClassLocator implements ClassLocator
{
    /**
     * @param array<string, string> $map Fully-qualified class name to repository-relative path.
     */
    public function __construct(private array $map = [])
    {
    }

    public function pathFor(string $fullyQualifiedClass): ?string
    {
        return $this->map[$fullyQualifiedClass] ?? null;
    }

    /**
     * The in-memory map keys ARE the placeable names, so a name it carries is mapped and one it
     * does not carry is not. Enough to exercise both diagnostics (ADR-A017) without a PSR-4 parser.
     */
    public function hasMappingFor(string $fullyQualifiedClass): bool
    {
        return isset($this->map[$fullyQualifiedClass]);
    }

    /**
     * Composer's own convention, as the real locator applies it: a placed path inside the vendor
     * directory belongs to an installed dependency, anything else to the project (ADR-A012).
     */
    public function isProjectSource(string $relativePath): bool
    {
        return !str_starts_with(ltrim($relativePath, '/'), 'vendor/');
    }
}
