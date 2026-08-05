<?php

declare(strict_types=1);

namespace ContextDiscovery\Adapters\Autoload;

use ContextDiscovery\Ports\ClassLocator;
use ContextDiscovery\Ports\SourceRepository;

/**
 * Fully-qualified class name to file path, via the repository's own PSR-4 map.
 *
 * The map is the input the spec names, so a class name resolves to a path **without an index**:
 * nothing is built, nothing is maintained, nothing survives the run (P9). Both `autoload` and
 * `autoload-dev` are read, because a changed file may reference either.
 *
 * A name the map cannot place — no matching prefix, an unreadable or absent `composer.json`, or a
 * file that is not there — resolves to null. The lever policy then flags it, and the reviewer is
 * told the contract is unverified rather than being told nothing (P10).
 *
 * Reads go through `SourceRepository`, so the root-scoping and symlink refusal that protect every
 * other read protect this one too.
 */
final class ComposerPsr4ClassLocator implements ClassLocator
{
    private const MANIFEST = 'composer.json';

    /**
     * Namespace prefix to the directories that hold it, longest prefix first — PSR-4 resolves
     * against the most specific match.
     *
     * @var array<string, list<string>>
     */
    private readonly array $prefixes;

    public function __construct(private readonly SourceRepository $source)
    {
        $this->prefixes = $this->readPrefixes();
    }

    public function pathFor(string $fullyQualifiedClass): ?string
    {
        $class = ltrim($fullyQualifiedClass, '\\');

        if ($class === '') {
            return null;
        }

        foreach ($this->prefixes as $prefix => $directories) {
            if ($prefix !== '' && !str_starts_with($class, $prefix)) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

            foreach ($directories as $directory) {
                $candidate = $directory === '' ? $relative : $directory . '/' . $relative;

                if ($this->source->exists($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, list<string>>
     */
    private function readPrefixes(): array
    {
        $manifest = $this->source->text(self::MANIFEST);

        if ($manifest === null) {
            return [];
        }

        $decoded = json_decode($manifest, true);

        if (!is_array($decoded)) {
            return [];
        }

        $prefixes = [];

        foreach (['autoload', 'autoload-dev'] as $section) {
            $map = $decoded[$section]['psr-4'] ?? null;

            if (!is_array($map)) {
                continue;
            }

            foreach ($map as $namespace => $paths) {
                $namespace = ltrim((string) $namespace, '\\');

                foreach ((array) $paths as $path) {
                    if (!is_string($path)) {
                        continue;
                    }

                    $prefixes[$namespace][] = trim($path, '/');
                }
            }
        }

        // Longest prefix first, then by name, so resolution is specific-first and identical on
        // every machine (P8).
        uksort($prefixes, static fn (string $a, string $b): int => strlen($b) <=> strlen($a) ?: strcmp($a, $b));

        return $prefixes;
    }
}
