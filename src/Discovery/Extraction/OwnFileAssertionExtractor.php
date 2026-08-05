<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Extraction;

use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedFile;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Ports\MemberSlicer;

/**
 * Reads the changed file's own text — the move that catches what forward-following cannot see.
 *
 * Two kinds come out of it (freeze review 04):
 *
 * - `SameFileSymbolAbsence` — a class name the region uses that the file's `use` block does not
 *   import. This is an **absence**: there is no reference to follow, so only reading the file's
 *   own imports as a structure reveals it. Experiment 1's missing `Log`.
 * - `SameFileReference` — a member of the changed file that the region calls as `$this->method(`.
 *   Research context type 1, not type 2: the source is the file already in hand, so it never
 *   reaches another file. Experiment 1's `upsertFromPlaid`.
 *
 * **Recognised positions.** A name counts as a class name in exactly the positions the frozen
 * forms table names for the cross-file case — `Name::member`, and a class name in a `new`,
 * `instanceof`, or type position (01-architecture.md §3.3). The two kinds are the same scan read
 * two ways: a recognised name that the `use` block imports is a cross-file `NamedReference` and
 * belongs to the other extractor; one it does not import is an absence. Any other syntactic form
 * yields nothing — no inference, no guessing (ADR-A003).
 *
 * Only added lines are scanned. Removed lines are not what the change asserts.
 */
final class OwnFileAssertionExtractor implements RegionAssertionExtractor
{
    /**
     * Names that are never a class reference: the language's own, and the scalar and pseudo types.
     */
    private const NOT_CLASS_NAMES = [
        'self', 'static', 'parent', 'class',
        'int', 'float', 'string', 'bool', 'array', 'object', 'mixed', 'callable', 'iterable',
        'void', 'never', 'null', 'false', 'true',
    ];

    public function __construct(private readonly MemberSlicer $slicer)
    {
    }

    /**
     * @return list<Assertion>
     */
    public function forRegion(ChangedFile $file, ChangedRegion $region, string $fileText): array
    {
        if ($region->addedLines === []) {
            return [];
        }

        $tokens = $this->tokenize(implode("\n", $region->addedLines));

        $known = array_merge(
            $this->importedNames($fileText),
            $this->declaredTypeNames($fileText),
        );

        $assertions = [];

        foreach ($this->classNamesIn($tokens) as $name) {
            if (in_array($name, $known, true)) {
                continue; // imported or declared here — a cross-file reference, not an absence.
            }

            $assertions[] = new Assertion(
                AssertionKind::SameFileSymbolAbsence,
                $name,
                $file->path,
                $region,
                sprintf('the region uses %s, which the file\'s use block does not import', $name),
            );
        }

        $members = $this->slicer->memberNames($fileText);

        foreach ($this->siblingCallsIn($tokens) as $name) {
            if (!in_array($name, $members, true)) {
                continue; // not a member of this file, so not a sibling this move can reach.
            }

            $assertions[] = new Assertion(
                AssertionKind::SameFileReference,
                $name,
                $file->path,
                $region,
                sprintf(
                    'the region calls the sibling member %s, whose contract the diff does not show',
                    $name,
                ),
            );
        }

        return $assertions;
    }

    /**
     * @param list<array{id:int|null,text:string}> $tokens
     *
     * @return list<string>
     */
    private function classNamesIn(array $tokens): array
    {
        $names = [];
        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            if ($tokens[$i]['id'] !== T_STRING) {
                continue;
            }

            $name = $tokens[$i]['text'];

            if (in_array(strtolower($name), self::NOT_CLASS_NAMES, true) || in_array($name, $names, true)) {
                continue;
            }

            if ($this->isClassNamePosition($tokens, $i)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @param list<array{id:int|null,text:string}> $tokens
     */
    private function isClassNamePosition(array $tokens, int $index): bool
    {
        $before = $this->previousSignificant($tokens, $index - 1);
        $after = $this->nextSignificant($tokens, $index + 1);

        if ($after !== null) {
            // `Name::member` — static or enum member access.
            if ($tokens[$after]['id'] === T_DOUBLE_COLON) {
                return true;
            }

            // `Name $parameter` — a parameter or property type.
            if ($tokens[$after]['id'] === T_VARIABLE) {
                return true;
            }
        }

        if ($before === null) {
            return false;
        }

        // `new Name`, `instanceof Name`.
        if ($tokens[$before]['id'] === T_NEW || $tokens[$before]['id'] === T_INSTANCEOF) {
            return true;
        }

        // `: Name` and `?Name` — a return or nullable type.
        return $tokens[$before]['id'] === null
            && ($tokens[$before]['text'] === ':' || $tokens[$before]['text'] === '?');
    }

    /**
     * `$this->method(` — the same-class sibling form.
     *
     * @param list<array{id:int|null,text:string}> $tokens
     *
     * @return list<string>
     */
    private function siblingCallsIn(array $tokens): array
    {
        $names = [];
        $total = count($tokens);

        for ($i = 0; $i < $total - 3; $i++) {
            if ($tokens[$i]['id'] !== T_VARIABLE || $tokens[$i]['text'] !== '$this') {
                continue;
            }

            $arrow = $this->nextSignificant($tokens, $i + 1);

            if ($arrow === null || $tokens[$arrow]['id'] !== T_OBJECT_OPERATOR) {
                continue;
            }

            $name = $this->nextSignificant($tokens, $arrow + 1);

            if ($name === null || $tokens[$name]['id'] !== T_STRING) {
                continue;
            }

            $open = $this->nextSignificant($tokens, $name + 1);

            if ($open === null || $tokens[$open]['id'] !== null || $tokens[$open]['text'] !== '(') {
                continue; // a property read, not a call.
            }

            if (!in_array($tokens[$name]['text'], $names, true)) {
                $names[] = $tokens[$name]['text'];
            }
        }

        return $names;
    }

    /**
     * The short names the file's `use` block brings into scope, including aliases.
     *
     * @return list<string>
     */
    private function importedNames(string $fileText): array
    {
        $useBlock = $this->slicer->useBlock($fileText);

        if ($useBlock === null) {
            return [];
        }

        $tokens = $this->tokenize($useBlock->text);
        $names = [];
        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            if ($tokens[$i]['id'] !== T_USE) {
                continue;
            }

            $next = $this->nextSignificant($tokens, $i + 1);

            if ($next !== null && ($tokens[$next]['id'] === T_FUNCTION || $tokens[$next]['id'] === T_CONST)) {
                continue; // `use function` / `use const` import no class.
            }

            $clause = [];

            for ($j = $i + 1; $j < $total; $j++) {
                if ($tokens[$j]['id'] === null && ($tokens[$j]['text'] === ';' || $tokens[$j]['text'] === ',')) {
                    $names = array_merge($names, $this->nameOfClause($clause));
                    $clause = [];

                    if ($tokens[$j]['text'] === ';') {
                        $i = $j;
                        break;
                    }

                    continue;
                }

                $clause[] = $tokens[$j];
            }

            $names = array_merge($names, $this->nameOfClause($clause));
        }

        return array_values(array_unique($names));
    }

    /**
     * The short name a single import clause introduces: the alias when one is given, otherwise the
     * last segment of the qualified name.
     *
     * @param list<array{id:int|null,text:string}> $clause
     *
     * @return list<string>
     */
    private function nameOfClause(array $clause): array
    {
        $alias = null;
        $last = null;
        $sawAs = false;

        foreach ($clause as $token) {
            if ($token['id'] === T_AS) {
                $sawAs = true;
                continue;
            }

            if ($token['id'] !== T_STRING && $token['id'] !== T_NAME_QUALIFIED) {
                continue;
            }

            $segments = explode('\\', $token['text']);
            $segment = (string) end($segments);

            if ($sawAs) {
                $alias = $segment;
                continue;
            }

            $last = $segment;
        }

        $name = $alias ?? $last;

        return $name === null || $name === '' ? [] : [$name];
    }

    /**
     * @return list<string>
     */
    private function declaredTypeNames(string $fileText): array
    {
        $tokens = $this->tokenize($fileText);
        $names = [];

        foreach ($tokens as $i => $token) {
            if (!in_array($token['id'], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
                continue;
            }

            $name = $this->nextSignificant($tokens, $i + 1);

            if ($name !== null && $tokens[$name]['id'] === T_STRING) {
                $names[] = $tokens[$name]['text'];
            }
        }

        return $names;
    }

    /**
     * @return list<array{id:int|null,text:string}>
     */
    private function tokenize(string $code): array
    {
        $tokens = [];

        foreach (@token_get_all('<?php ' . $code) as $token) {
            $tokens[] = is_array($token)
                ? ['id' => $token[0], 'text' => $token[1]]
                : ['id' => null, 'text' => $token];
        }

        return $tokens;
    }

    /**
     * @param list<array{id:int|null,text:string}> $tokens
     */
    private function nextSignificant(array $tokens, int $from): ?int
    {
        for ($i = $from, $total = count($tokens); $i < $total; $i++) {
            if (!$this->isSkippable($tokens[$i]['id'])) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param list<array{id:int|null,text:string}> $tokens
     */
    private function previousSignificant(array $tokens, int $from): ?int
    {
        for ($i = $from; $i >= 0; $i--) {
            if (!$this->isSkippable($tokens[$i]['id'])) {
                return $i;
            }
        }

        return null;
    }

    private function isSkippable(?int $id): bool
    {
        return $id !== null && in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG], true);
    }
}
