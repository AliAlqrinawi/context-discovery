<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Parsing;

use ContextDiscovery\Domain\Diff\ChangedFile;
use ContextDiscovery\Domain\Diff\ChangedMember;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Domain\Diff\Diff;
use InvalidArgumentException;

/**
 * Unified diff text to Domain\Diff.
 *
 * A pure class, not a port: no I/O, one implementation, no evidence of a second diff format
 * (freeze review O1). Given the same text it produces the same Diff — no clock, no randomness,
 * no filesystem (P8).
 *
 * Old signatures come from the hunk's removed lines. A rename is treated as two members and is
 * not tracked: the old name appears with no new signature, the new name with no old one
 * (01-architecture.md §3.3).
 *
 * Malformed input raises rather than returning a partial Diff — a half-parsed diff would produce
 * a bundle that looks complete and is not.
 */
final class UnifiedDiffParser
{
    private const PHP_EXTENSION = '.php';

    /**
     * @throws InvalidArgumentException when the text is not a readable unified diff.
     */
    public function parse(string $diffText): Diff
    {
        $lines = $this->splitLines($diffText);

        /** @var list<ChangedFile> $files */
        $files = [];
        /** @var list<ChangedRegion> $regions */
        $regions = [];

        $path = null;
        $fileOpen = false;
        $sawStructure = false;
        $hunk = null;

        foreach ($lines as $line) {
            if ($hunk !== null && ($hunk['remainingOld'] > 0 || $hunk['remainingNew'] > 0)) {
                $this->consumeHunkLine($hunk, $line);
                continue;
            }

            if ($hunk !== null) {
                $regions[] = $this->toRegion($hunk);
                $hunk = null;
            }

            if (str_starts_with($line, '@@')) {
                if (!$fileOpen) {
                    throw new InvalidArgumentException(
                        'Malformed diff: a hunk header appears before any file header.'
                    );
                }
                $hunk = $this->parseHunkHeader($line);
                continue;
            }

            if (str_starts_with($line, 'diff --git ')) {
                if ($fileOpen) {
                    $files[] = $this->toFile($path, $regions);
                }
                $path = null;
                $fileOpen = false;
                $regions = [];
                $sawStructure = true;
                continue;
            }

            if (str_starts_with($line, '--- ')) {
                if ($fileOpen) {
                    $files[] = $this->toFile($path, $regions);
                    $regions = [];
                    $fileOpen = false;
                }
                $path = $this->parsePath(substr($line, 4));
                $sawStructure = true;
                continue;
            }

            if (str_starts_with($line, '+++ ')) {
                // The new path wins; a deleted file keeps the old one, since /dev/null is not a path.
                $path = $this->parsePath(substr($line, 4)) ?? $path;
                $fileOpen = true;
                $sawStructure = true;
                continue;
            }

            // index, mode, similarity, rename and binary-notice lines carry nothing this tool reads.
        }

        if ($hunk !== null) {
            if ($hunk['remainingOld'] > 0 || $hunk['remainingNew'] > 0) {
                throw new InvalidArgumentException(
                    'Malformed diff: the final hunk ends before its line counts are satisfied.'
                );
            }
            $regions[] = $this->toRegion($hunk);
        }

        if ($fileOpen) {
            $files[] = $this->toFile($path, $regions);
        }

        if ($files === [] && !$sawStructure && trim($diffText) !== '') {
            throw new InvalidArgumentException(
                'Malformed diff: no file header, hunk header, or "diff --git" line was found.'
            );
        }

        return new Diff($files);
    }

    /**
     * @return list<string>
     */
    private function splitLines(string $diffText): array
    {
        if ($diffText === '') {
            return [];
        }

        // Normalised so a CRLF checkout and an LF checkout produce the same Diff (P8).
        return explode("\n", str_replace(["\r\n", "\r"], "\n", $diffText));
    }

    /**
     * Repository-relative, with git's a/ and b/ prefixes and any trailing timestamp removed.
     *
     * Paths git quotes because they contain unusual bytes are left as they arrive; such a path
     * simply will not resolve on disk and becomes a flag (P10), never a wrong slice.
     */
    private function parsePath(string $raw): ?string
    {
        $tab = strpos($raw, "\t");
        if ($tab !== false) {
            $raw = substr($raw, 0, $tab);
        }

        $raw = rtrim($raw);

        if ($raw === '' || $raw === '/dev/null') {
            return null;
        }

        if (str_starts_with($raw, 'a/') || str_starts_with($raw, 'b/')) {
            $raw = substr($raw, 2);
        }

        return $raw;
    }

    /**
     * @return array{newStart:int,newCount:int,remainingOld:int,remainingNew:int,added:list<string>,removed:list<string>}
     */
    private function parseHunkHeader(string $line): array
    {
        if (preg_match('/^@@ -(\d+)(?:,(\d+))? \+(\d+)(?:,(\d+))? @@/', $line, $m) !== 1) {
            throw new InvalidArgumentException(
                sprintf('Malformed diff: unreadable hunk header "%s".', $line)
            );
        }

        return [
            'newStart' => (int) $m[3],
            'newCount' => ($m[4] ?? '') === '' ? 1 : (int) $m[4],
            'remainingOld' => ($m[2] ?? '') === '' ? 1 : (int) $m[2],
            'remainingNew' => ($m[4] ?? '') === '' ? 1 : (int) $m[4],
            'added' => [],
            'removed' => [],
        ];
    }

    /**
     * @param array{newStart:int,newCount:int,remainingOld:int,remainingNew:int,added:list<string>,removed:list<string>} $hunk
     */
    private function consumeHunkLine(array &$hunk, string $line): void
    {
        // A context line whose content is empty may arrive as '' when trailing whitespace was
        // stripped in transit. Treating it as anything else would desynchronise the counts.
        $marker = $line === '' ? ' ' : $line[0];

        switch ($marker) {
            case '+':
                $hunk['added'][] = substr($line, 1);
                $hunk['remainingNew']--;
                break;
            case '-':
                $hunk['removed'][] = substr($line, 1);
                $hunk['remainingOld']--;
                break;
            case ' ':
                $hunk['remainingOld']--;
                $hunk['remainingNew']--;
                break;
            case '\\':
                // "\ No newline at end of file" belongs to neither side.
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf('Malformed diff: unreadable line "%s" inside a hunk.', $line)
                );
        }

        if ($hunk['remainingOld'] < 0 || $hunk['remainingNew'] < 0) {
            throw new InvalidArgumentException(
                'Malformed diff: a hunk contains more lines than its header declares.'
            );
        }
    }

    /**
     * @param array{newStart:int,newCount:int,remainingOld:int,remainingNew:int,added:list<string>,removed:list<string>} $hunk
     */
    private function toRegion(array $hunk): ChangedRegion
    {
        // A pure deletion occupies no lines in the current file, so lastLine < firstLine and the
        // span is empty. That is the honest reading; a one-line span would be a fiction.
        return new ChangedRegion(
            $hunk['newStart'],
            $hunk['newStart'] + $hunk['newCount'] - 1,
            $hunk['added'],
            $hunk['removed'],
        );
    }

    /**
     * @param list<ChangedRegion> $regions
     */
    private function toFile(?string $path, array $regions): ChangedFile
    {
        if ($path === null) {
            throw new InvalidArgumentException(
                'Malformed diff: a file section carries no usable path.'
            );
        }

        return new ChangedFile($path, $regions, $this->membersIn($path, $regions));
    }

    /**
     * Members are read from PHP files only. A `function` keyword in a JavaScript or Markdown file
     * is not a PHP member, and the whole tool targets one language (ADR-A001).
     *
     * @param list<ChangedRegion> $regions
     *
     * @return list<ChangedMember>
     */
    private function membersIn(string $path, array $regions): array
    {
        if (!str_ends_with($path, self::PHP_EXTENSION)) {
            return [];
        }

        /** @var array<string, string> $old */
        $old = [];
        /** @var array<string, string> $new */
        $new = [];
        /** @var list<string> $order */
        $order = [];

        foreach ($regions as $region) {
            foreach ($this->signaturesIn($region->removedLines) as $name => $signature) {
                $old[$name] ??= $signature;
                if (!in_array($name, $order, true)) {
                    $order[] = $name;
                }
            }

            foreach ($this->signaturesIn($region->addedLines) as $name => $signature) {
                $new[$name] ??= $signature;
                if (!in_array($name, $order, true)) {
                    $order[] = $name;
                }
            }
        }

        $members = [];
        foreach ($order as $name) {
            $members[] = new ChangedMember($name, $old[$name] ?? null, $new[$name] ?? null);
        }

        return $members;
    }

    /**
     * Named function declarations among one side's lines, keyed by member name.
     *
     * A declaration whose parameter list wraps across lines is joined until the parentheses
     * balance, so a wrapped signature is compared whole rather than truncated.
     *
     * @param list<string> $lines
     *
     * @return array<string, string>
     */
    private function signaturesIn(array $lines): array
    {
        $found = [];
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            if (!str_contains($lines[$i], 'function')) {
                continue;
            }

            $buffer = trim($lines[$i]);
            $last = $i;
            $result = $this->analyseSignature($buffer);

            while ($result === null && $last + 1 < $count) {
                $last++;
                $buffer .= ' ' . trim($lines[$last]);
                $result = $this->analyseSignature($buffer);
            }

            if (is_array($result)) {
                $found[$result['name']] ??= $result['signature'];
                $i = $last;
            }
        }

        return $found;
    }

    /**
     * The tokenizer, not a regular expression: only the token stream distinguishes a declaration
     * from the word "function" inside a comment, a string, a closure, or a `use function` import
     * (the reasoning of ADR-A004, applied to diff fragments rather than whole files).
     *
     * @return array{name:string,signature:string}|false|null
     *         An array when a complete signature was read; null when a declaration has begun but
     *         its parameter list is unfinished (join another line); false when the text holds no
     *         named declaration at all.
     */
    private function analyseSignature(string $text): array|false|null
    {
        // A diff fragment is not complete PHP. Lenient tokenizing is the point; a parse error
        // here would mean rejecting an ordinary hunk.
        $tokens = @token_get_all('<?php ' . $text);

        $offsets = [];
        $position = 0;
        foreach ($tokens as $index => $token) {
            $offsets[$index] = $position;
            $position += strlen(is_array($token) ? $token[1] : $token);
        }

        $total = count($tokens);

        for ($k = 0; $k < $total; $k++) {
            if (!is_array($tokens[$k]) || $tokens[$k][0] !== T_FUNCTION) {
                continue;
            }

            $previous = $this->previousSignificant($tokens, $k - 1);
            if ($previous !== null && is_array($tokens[$previous]) && $tokens[$previous][0] === T_USE) {
                continue; // `use function Foo\bar;` imports a function, it does not declare one.
            }

            $nameIndex = $this->nextSignificant($tokens, $k + 1);
            if ($nameIndex === null) {
                return null;
            }

            if ($tokens[$nameIndex] === '&') {
                $nameIndex = $this->nextSignificant($tokens, $nameIndex + 1);
                if ($nameIndex === null) {
                    return null;
                }
            }

            if (!is_array($tokens[$nameIndex]) || $tokens[$nameIndex][0] !== T_STRING) {
                continue; // an anonymous function or arrow function declares no member.
            }

            $name = $tokens[$nameIndex][1];

            $open = $this->nextSignificant($tokens, $nameIndex + 1);
            if ($open === null) {
                return null;
            }
            if ($tokens[$open] !== '(') {
                continue;
            }

            $depth = 0;
            $close = null;
            for ($q = $open; $q < $total; $q++) {
                if ($tokens[$q] === '(') {
                    $depth++;
                } elseif ($tokens[$q] === ')') {
                    $depth--;
                    if ($depth === 0) {
                        $close = $q;
                        break;
                    }
                }
            }

            if ($close === null) {
                return null; // the parameter list wraps; join another line.
            }

            $end = $offsets[$close] + 1;
            for ($q = $close + 1; $q < $total; $q++) {
                $token = $tokens[$q];
                if ($token === '{' || $token === ';') {
                    break; // the body, or the end of an abstract or interface declaration.
                }
                $end = $offsets[$q] + strlen(is_array($token) ? $token[1] : $token);
            }

            return [
                'name' => $name,
                'signature' => trim(substr('<?php ' . $text, 6, $end - 6)),
            ];
        }

        return false;
    }

    /**
     * @param list<array{0:int,1:string,2:int}|string> $tokens
     */
    private function nextSignificant(array $tokens, int $from): ?int
    {
        for ($i = $from, $total = count($tokens); $i < $total; $i++) {
            if (!$this->isSkippable($tokens[$i])) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param list<array{0:int,1:string,2:int}|string> $tokens
     */
    private function previousSignificant(array $tokens, int $from): ?int
    {
        for ($i = $from; $i >= 0; $i--) {
            if (!$this->isSkippable($tokens[$i])) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param array{0:int,1:string,2:int}|string $token
     */
    private function isSkippable(array|string $token): bool
    {
        return is_array($token)
            && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG], true);
    }
}
