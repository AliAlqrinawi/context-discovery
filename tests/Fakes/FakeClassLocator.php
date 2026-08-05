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
}
