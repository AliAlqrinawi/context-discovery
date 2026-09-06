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
 *
 * The manifest also settles what counts as **this project's own source**: Composer installs
 * dependencies under `config.vendor-dir` (default `vendor`), so a placed path inside that directory
 * belongs to a dependency and a path outside it belongs to the project. No new input, no new pass —
 * the same `composer.json` the map already comes from (P9, ADR-A012, AA13).
 *
 * When the dependencies are installed, Composer's generated `autoload_psr4.php` is read as well and
 * its entries are **appended** — location metadata only, and only after the project's own, so a
 * prefix the project declares always tries the project's directories first (ADR-A014). It is
 * **parsed, never executed**: it is PHP from the repository under review, and `require` would run
 * that code outside the root-scoping every other read goes through. Its grammar makes parsing
 * enough — the file holds one literal array and two path variables.
 *
 * Nothing here decides ownership. A widened map makes a dependency class *locatable*; whether its
 * source may be fetched is still the path's answer, which is what keeps this change from undoing
 * ADR-A012 and ADR-A013.
 */
final class ComposerPsr4ClassLocator implements ClassLocator
{
    private const MANIFEST = 'composer.json';

    private const DEFAULT_VENDOR_DIRECTORY = 'vendor';

    /**
     * Composer writes this beside the autoloader it generates, relative to the vendor directory.
     */
    private const GENERATED_MAP = 'composer/autoload_psr4.php';

    /**
     * Namespace prefix to the directories that hold it, longest prefix first — PSR-4 resolves
     * against the most specific match.
     *
     * @var array<string, list<string>>
     */
    private readonly array $prefixes;

    /**
     * Composer's dependency directory, as the manifest declares it.
     */
    private readonly string $vendorDirectory;

    public function __construct(private readonly SourceRepository $source)
    {
        $manifest = $this->manifest();

        $this->vendorDirectory = $this->readVendorDirectory($manifest);
        $this->prefixes = $this->withInstalledPackages($this->readPrefixes($manifest));
    }

    public function isProjectSource(string $relativePath): bool
    {
        return !str_starts_with(trim($relativePath, '/'), $this->vendorDirectory . '/');
    }

    public function hasMappingFor(string $fullyQualifiedClass): bool
    {
        $class = ltrim($fullyQualifiedClass, '\\');

        if ($class === '') {
            return false;
        }

        foreach (array_keys($this->prefixes) as $prefix) {
            if ($prefix === '' || str_starts_with($class, (string) $prefix)) {
                return true;
            }
        }

        return false;
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
                $mapped = $directory === '' ? $relative : $directory . '/' . $relative;

                if ($this->source->exists($mapped)) {
                    return $mapped;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        $text = $this->source->text(self::MANIFEST);
        $decoded = $text === null ? null : json_decode($text, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * `config.vendor-dir` when the manifest sets it, Composer's default otherwise. A project that
     * relocates its dependencies is followed; one that does not needs no configuration.
     *
     * @param array<string, mixed> $manifest
     */
    private function readVendorDirectory(array $manifest): string
    {
        $configured = $manifest['config']['vendor-dir'] ?? null;

        return is_string($configured) && trim($configured, '/') !== ''
            ? trim($configured, '/')
            : self::DEFAULT_VENDOR_DIRECTORY;
    }

    /**
     * The project's own map, plus whatever Composer has installed — in that order, so the project
     * wins (ADR-A014).
     *
     * A generated entry for a prefix the project already declares is **appended after** the
     * project's directories rather than replacing them, which is both what "the project wins" means
     * and what Composer itself does: the first directory that holds the class answers.
     *
     * Absent, unreadable or unparseable metadata contributes nothing and raises nothing. That is
     * the same conservative fallback an unreadable `composer.json` already gets, and it is what a
     * bare checkout — no `vendor/` — relies on to behave exactly as it did before.
     *
     * @param array<string, list<string>> $projectPrefixes
     *
     * @return array<string, list<string>>
     */
    private function withInstalledPackages(array $prefixes): array
    {
        $generated = $this->source->text($this->vendorDirectory . '/' . self::GENERATED_MAP);

        if ($generated === null) {
            return $prefixes;
        }

        foreach ($this->parseGeneratedMap($generated) as $namespace => $directories) {
            foreach ($directories as $directory) {
                if (!in_array($directory, $prefixes[$namespace] ?? [], true)) {
                    $prefixes[$namespace][] = $directory;
                }
            }
        }

        return $prefixes;
    }

    /**
     * Composer's generated file, read as text.
     *
     * Every entry has the shape `'Prefix\\' => array($vendorDir . '/a', $baseDir . '/b'),` — the
     * two variables are the only ones the file defines, and `dirname()` and `array()` are the only
     * calls it makes. So the map is recovered by reading string literals: `$vendorDir` is the
     * configured vendor directory and `$baseDir` is the repository root, which is `''` in the
     * repository-relative paths this class deals in.
     *
     * Anything that does not match is skipped. A malformed file therefore degrades to fewer
     * entries, never to an exception, and the result is a pure function of the text (P8).
     *
     * @return array<string, list<string>>
     */
    private function parseGeneratedMap(string $text): array
    {
        if (preg_match_all('/\'((?:[^\'\\\\]|\\\\.)*)\'\s*=>\s*array\(([^)]*)\)/', $text, $entries, PREG_SET_ORDER) === false) {
            return [];
        }

        $prefixes = [];

        foreach ($entries as $entry) {
            $namespace = ltrim(str_replace(['\\\\', "\\'"], ['\\', "'"], $entry[1]), '\\');

            if ($namespace === '') {
                continue;
            }

            preg_match_all('/\$(vendorDir|baseDir)\s*\.\s*\'([^\']*)\'/', $entry[2], $paths, PREG_SET_ORDER);

            foreach ($paths as $path) {
                $root = $path[1] === 'vendorDir' ? $this->vendorDirectory : '';
                $directory = trim($root . '/' . ltrim($path[2], '/'), '/');

                if ($directory !== '' && !in_array($directory, $prefixes[$namespace] ?? [], true)) {
                    $prefixes[$namespace][] = $directory;
                }
            }
        }

        return $prefixes;
    }

    /**
     * @param array<string, mixed> $manifest
     *
     * @return array<string, list<string>>
     */
    private function readPrefixes(array $manifest): array
    {
        $decoded = $manifest;
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
