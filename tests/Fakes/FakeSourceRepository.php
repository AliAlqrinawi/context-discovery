<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Fakes;

use ContextDiscovery\Ports\SourceRepository;

/**
 * An in-memory repository, so no unit test touches a real filesystem.
 */
final class FakeSourceRepository implements SourceRepository
{
    /**
     * @param array<string, string> $files Repository-relative path to text.
     */
    public function __construct(private array $files = [])
    {
    }

    public function exists(string $relativePath): bool
    {
        return isset($this->files[$relativePath]);
    }

    public function text(string $relativePath): ?string
    {
        return $this->files[$relativePath] ?? null;
    }

    /**
     * @return list<string>
     */
    public function filesUnder(string $prefix, string $extension): array
    {
        $suffix = str_starts_with($extension, '.') ? $extension : '.' . $extension;
        $prefix = trim($prefix, '/');

        $found = [];

        foreach (array_keys($this->files) as $path) {
            if (str_ends_with($path, $suffix) && ($prefix === '' || str_starts_with($path, $prefix . '/'))) {
                $found[] = $path;
            }
        }

        sort($found, SORT_STRING);

        return $found;
    }
}
