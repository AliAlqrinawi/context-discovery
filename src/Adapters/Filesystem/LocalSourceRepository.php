<?php

declare(strict_types=1);

namespace ContextDiscovery\Adapters\Filesystem;

use ContextDiscovery\Ports\SourceRepository;
use RuntimeException;

/**
 * Read-only, root-scoped access to a checkout on local disk.
 *
 * The tool never writes inside the repository under review, and never reads outside it. Every
 * path is resolved through realpath() before it is touched, so `../` traversal, an absolute
 * path, and a symlink that leaves the root all resolve to somewhere outside and are refused.
 * A refused path returns null rather than raising: the caller flags it, and a flag is a result
 * (P10).
 */
final class LocalSourceRepository implements SourceRepository
{
    private readonly string $root;

    /**
     * @throws RuntimeException when the repository root is not a readable directory. This is the
     *                          one input failure the tool cannot work around, and the CLI reports
     *                          it as "repository unreadable".
     */
    public function __construct(string $repositoryRoot)
    {
        $resolved = realpath($repositoryRoot);

        if ($resolved === false || !is_dir($resolved) || !is_readable($resolved)) {
            throw new RuntimeException(
                sprintf('Repository root is not a readable directory: %s', $repositoryRoot)
            );
        }

        $this->root = $resolved;
    }

    public function exists(string $relativePath): bool
    {
        $absolute = $this->resolve($relativePath);

        return $absolute !== null && is_file($absolute) && is_readable($absolute);
    }

    public function text(string $relativePath): ?string
    {
        $absolute = $this->resolve($relativePath);

        if ($absolute === null || !is_file($absolute) || !is_readable($absolute)) {
            return null;
        }

        $text = file_get_contents($absolute);

        if ($text === false) {
            return null;
        }

        // A CRLF checkout and an LF checkout must produce the same slices (P8). Only the pair is
        // rewritten; a lone carriage return inside a string literal is left alone.
        return str_replace("\r\n", "\n", $text);
    }

    /**
     * @return list<string> Repository-relative paths, sorted by byte value.
     */
    public function filesUnder(string $prefix, string $extension): array
    {
        $prefix = trim($prefix, "/\\");
        $suffix = str_starts_with($extension, '.') ? $extension : '.' . $extension;

        $base = $prefix === '' ? $this->root : $this->resolve($prefix);

        if ($base === null || !is_dir($base) || !is_readable($base)) {
            return [];
        }

        $found = [];
        $this->collect($base, $prefix, $suffix, $found);

        // strcmp ordering, not locale collation: the same bytes must sort the same way on every
        // machine, or the bounded caller search returns a different subset (P8).
        sort($found, SORT_STRING);

        return $found;
    }

    /**
     * @param list<string> $found
     */
    private function collect(string $absoluteDir, string $relativeDir, string $suffix, array &$found): void
    {
        $entries = scandir($absoluteDir);

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $absolute = $absoluteDir . DIRECTORY_SEPARATOR . $entry;

            // Symlinks are never followed: one can leave the root, and a symlinked directory can
            // make the walk loop forever.
            if (is_link($absolute)) {
                continue;
            }

            $relative = $relativeDir === '' ? $entry : $relativeDir . '/' . $entry;

            if (is_dir($absolute)) {
                $this->collect($absolute, $relative, $suffix, $found);
                continue;
            }

            if (is_file($absolute) && str_ends_with($entry, $suffix)) {
                $found[] = $relative;
            }
        }
    }

    /**
     * @return string|null The absolute path, or null when it is missing, unreadable, or resolves
     *                     outside the repository root.
     */
    private function resolve(string $relativePath): ?string
    {
        if ($relativePath === '') {
            return null;
        }

        if (str_starts_with($relativePath, '/') || str_starts_with($relativePath, '\\')) {
            return null;
        }

        if (preg_match('/^[A-Za-z]:/', $relativePath) === 1) {
            return null;
        }

        $resolved = realpath($this->root . DIRECTORY_SEPARATOR . $relativePath);

        if ($resolved === false) {
            return null;
        }

        if ($resolved !== $this->root && !str_starts_with($resolved, $this->root . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $resolved;
    }
}
