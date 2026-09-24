<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Resolution;

use ContextDiscovery\Domain\Source\AncestorDeclaration;
use ContextDiscovery\Domain\Source\AncestryBoundary;
use ContextDiscovery\Ports\ClassLocator;
use ContextDiscovery\Ports\MemberSlicer;
use ContextDiscovery\Ports\SourceRepository;

/**
 * Walks `extends` and `use <Trait>` through **project files** to a fixed point, to say where a
 * member the calling class does not declare is declared — and never to fetch it.
 *
 * This is ADR-A010's D2 relaxed for verification only (ADR-A028, ADR-A029). D1 is untouched:
 * nothing this walk opens produces an assertion, and no body it finds enters the bundle. What it
 * returns is a *citation* — type, file, line — or the place it stopped.
 *
 * The rules, each from ADR-A028 §6 and each tested:
 *
 * - **project files only.** A parent or trait the locator places under the dependency directory
 *   is a boundary: named, never opened. A name the locator cannot place is a boundary. A file
 *   that cannot be read is a boundary.
 * - **to a fixed point, with a visited set.** No hop count is invented (ADR-A010 rejected one as
 *   AA1-shaped). A type seen twice is a boundary.
 * - **PHP's precedence, without inference.** At each type the traits it uses are checked before
 *   its parent, because a trait member overrides an inherited one. Two traits of one type both
 *   declaring the member is a boundary: PHP settles that with `insteadof`, and reproducing that is
 *   inference (P6). A trait `use` carrying an `insteadof`/`as` block is a boundary for the same
 *   reason. A trait's own traits are walked like a type's.
 * - **an abstract declaration is a declaration.** The citation lands on it.
 * - **interfaces are not walked.** They carry no bodies to cite.
 * - **a dependency trait stops the walk even when a project parent might declare the member.**
 *   A trait member overrides an inherited one, and which wins cannot be known without opening
 *   the dependency. Fail-closed: a boundary, not a guess.
 *
 * Three outcomes: an `AncestorDeclaration` (S1), an `AncestryBoundary` (S2), or **null** when the
 * ancestry was walked to its end inside project code and nowhere declares the member — or there
 * was no ancestry at all. Null means the existing `unresolved-reference` flag is exactly true.
 */
final class AncestryResolver
{
    public function __construct(
        private readonly ClassLocator $locator,
        private readonly SourceRepository $source,
        private readonly MemberSlicer $slicer,
    ) {
    }

    public function declarationOf(string $callingClass, string $originPath, string $member): AncestorDeclaration|AncestryBoundary|null
    {
        $text = $this->source->text($originPath);

        if ($text === null) {
            return null;
        }

        $visited = [$callingClass => true];
        $walked = [];
        $walkedParents = [];
        $current = $callingClass;
        $currentText = $text;

        while (true) {
            $shape = $this->shapeOf($currentText);

            if ($shape['conflictBlock']) {
                return new AncestryBoundary($callingClass, $member, AncestryBoundary::CONFLICT_BLOCK, $current, $walked);
            }

            // Traits first: a trait member takes precedence over an inherited one.
            $found = $this->inTraits($shape['traits'], $shape['namespace'], $shape['imports'], $member, $current, $callingClass, $walked, $visited, $walkedParents);

            if ($found instanceof AncestorDeclaration || $found instanceof AncestryBoundary) {
                return $found;
            }

            if ($shape['parent'] === null) {
                return null; // walked to the end of project code, or no ancestry: the existing flag is true
            }

            $parent = $this->qualify($shape['parent'], $shape['namespace'], $shape['imports']);

            if (isset($visited[$parent])) {
                return new AncestryBoundary($callingClass, $member, AncestryBoundary::CYCLE, $parent, $walked);
            }

            $visited[$parent] = true;
            $path = $this->locator->pathFor($parent);

            if ($path === null) {
                return new AncestryBoundary($callingClass, $member, AncestryBoundary::UNPLACEABLE, $parent, $walked);
            }

            if (!$this->locator->isProjectSource($path)) {
                return new AncestryBoundary($callingClass, $member, AncestryBoundary::DEPENDENCY, $parent, $walked);
            }

            $parentText = $this->source->text($path);

            if ($parentText === null) {
                return new AncestryBoundary($callingClass, $member, AncestryBoundary::UNREADABLE, $path, $walked);
            }

            $walked[] = $parent;
            $slice = $this->slicer->member($parentText, $member);

            if ($slice !== null) {
                return new AncestorDeclaration($callingClass, $member, $parent, $path, $slice->firstLine, false, null, $walkedParents);
            }

            $walkedParents[] = $parent;
            $current = $parent;
            $currentText = $parentText;
        }
    }

    /**
     * The traits a type uses, checked for the member. One declaring trait is a declaration; two
     * are a boundary; none means the traits' own traits are walked, then null.
     *
     * @param list<string>          $traitNames
     * @param array<string, string> $imports
     * @param list<string>          $walked
     * @param array<string, true>   $visited
     * @param list<string>          $walkedParents
     */
    private function inTraits(array $traitNames, string $namespace, array $imports, string $member, string $applier, string $callingClass, array &$walked, array &$visited, array $walkedParents): AncestorDeclaration|AncestryBoundary|null
    {
        $declaring = [];
        $texts = [];

        foreach ($traitNames as $name) {
            $trait = $this->qualify($name, $namespace, $imports);

            if (isset($visited[$trait])) {
                return new AncestryBoundary($callingClass, $member, AncestryBoundary::CYCLE, $trait, $walked);
            }

            $visited[$trait] = true;
            $path = $this->locator->pathFor($trait);

            if ($path === null) {
                return new AncestryBoundary($callingClass, $member, AncestryBoundary::UNPLACEABLE, $trait, $walked);
            }

            if (!$this->locator->isProjectSource($path)) {
                return new AncestryBoundary($callingClass, $member, AncestryBoundary::DEPENDENCY, $trait, $walked);
            }

            $text = $this->source->text($path);

            if ($text === null) {
                return new AncestryBoundary($callingClass, $member, AncestryBoundary::UNREADABLE, $path, $walked);
            }

            $walked[] = $trait;
            $texts[$trait] = [$path, $text];
            $slice = $this->slicer->member($text, $member);

            if ($slice !== null) {
                $declaring[] = new AncestorDeclaration($callingClass, $member, $trait, $path, $slice->firstLine, true, $applier, $walkedParents);
            }
        }

        if (count($declaring) > 1) {
            return new AncestryBoundary($callingClass, $member, AncestryBoundary::AMBIGUOUS, $applier, $walked);
        }

        if ($declaring !== []) {
            return $declaring[0];
        }

        // A trait can use traits. Each is walked like a type of its own.
        foreach ($texts as $trait => [$path, $text]) {
            $shape = $this->shapeOf($text);

            if ($shape['conflictBlock']) {
                return new AncestryBoundary($callingClass, $member, AncestryBoundary::CONFLICT_BLOCK, $trait, $walked);
            }

            $nested = $this->inTraits($shape['traits'], $shape['namespace'], $shape['imports'], $member, $trait, $callingClass, $walked, $visited, $walkedParents);

            if ($nested !== null) {
                return $nested;
            }
        }

        return null;
    }

    /**
     * The single-file facts the walk reads from a type's own text: its namespace, its imports,
     * the parent it extends, the traits its body uses, and whether a trait use carries a conflict
     * block this walk will not interpret.
     *
     * @return array{namespace: string, imports: array<string, string>, parent: ?string, traits: list<string>, conflictBlock: bool}
     */
    private function shapeOf(string $text): array
    {
        $tokens = [];

        foreach (@token_get_all($text) as $token) {
            $tokens[] = is_array($token) ? ['id' => $token[0], 'text' => $token[1]] : ['id' => null, 'text' => $token];
        }

        $namespace = '';
        $imports = [];
        $parent = null;
        $traits = [];
        $conflictBlock = false;
        $depth = 0;
        $inType = false;
        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            $token = $tokens[$i];

            if ($token['id'] === null) {
                if ($token['text'] === '{') {
                    $depth++;
                } elseif ($token['text'] === '}') {
                    $depth--;
                }

                continue;
            }

            if ($token['id'] === T_NAMESPACE && $depth === 0) {
                $namespace = $this->nameAfter($tokens, $i) ?? '';
                continue;
            }

            if ($token['id'] === T_USE && $depth === 0 && !$inType) {
                $this->importsFrom($tokens, $i, $imports);
                continue;
            }

            if (in_array($token['id'], [T_CLASS, T_TRAIT, T_ENUM, T_INTERFACE], true) && $depth === 0) {
                $inType = true;
                continue;
            }

            if ($token['id'] === T_EXTENDS && $depth === 0 && $parent === null) {
                $parent = $this->nameAfter($tokens, $i);
                continue;
            }

            if ($token['id'] === T_USE && $depth === 1 && $inType) {
                // `use A, B;` inside the type body. A `{` before the `;` is an insteadof/as block.
                for ($j = $i + 1; $j < $total; $j++) {
                    $t = $tokens[$j];

                    if ($t['id'] === null && $t['text'] === ';') {
                        break;
                    }

                    if ($t['id'] === null && $t['text'] === '{') {
                        $conflictBlock = true;
                        break;
                    }

                    if (in_array($t['id'], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                        $traits[] = $t['text'];
                    }
                }
            }
        }

        return ['namespace' => $namespace, 'imports' => $imports, 'parent' => $parent, 'traits' => $traits, 'conflictBlock' => $conflictBlock];
    }

    /**
     * @param list<array{id:int|null,text:string}> $tokens
     */
    private function nameAfter(array $tokens, int $from): ?string
    {
        for ($i = $from + 1, $total = count($tokens); $i < $total; $i++) {
            $id = $tokens[$i]['id'];

            if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                continue;
            }

            return in_array($id, [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true) ? $tokens[$i]['text'] : null;
        }

        return null;
    }

    /**
     * A top-level `use` statement: short name (or alias) to fully-qualified name. Group use and
     * `use function`/`use const` are skipped — neither names a type this walk could follow.
     *
     * @param list<array{id:int|null,text:string}> $tokens
     * @param array<string, string>                $imports
     */
    private function importsFrom(array $tokens, int $from, array &$imports): void
    {
        $qualified = null;
        $alias = null;
        $sawAs = false;

        for ($i = $from + 1, $total = count($tokens); $i < $total; $i++) {
            $t = $tokens[$i];

            if ($t['id'] === T_FUNCTION || $t['id'] === T_CONST) {
                return;
            }

            if ($t['id'] === null && ($t['text'] === ';' || $t['text'] === ',')) {
                if ($qualified !== null) {
                    $segments = explode('\\', $qualified);
                    $imports[$alias ?? (string) end($segments)] = ltrim($qualified, '\\');
                }

                if ($t['text'] === ';') {
                    return;
                }

                $qualified = null;
                $alias = null;
                $sawAs = false;
                continue;
            }

            if ($t['id'] === null && $t['text'] === '{') {
                return; // group use: not followed
            }

            if ($t['id'] === T_AS) {
                $sawAs = true;
                continue;
            }

            if (in_array($t['id'], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                if ($sawAs) {
                    $alias = $t['text'];
                } else {
                    $qualified = $t['text'];
                }
            }
        }
    }

    /**
     * A name as written in an `extends` or trait `use` clause, made fully qualified through the
     * file's own imports and namespace — the language's resolution rule, nothing more.
     *
     * @param array<string, string> $imports
     */
    private function qualify(string $name, string $namespace, array $imports): string
    {
        if (str_starts_with($name, '\\')) {
            return ltrim($name, '\\');
        }

        $segments = explode('\\', $name);

        if (isset($imports[$segments[0]])) {
            $segments[0] = $imports[$segments[0]];

            return implode('\\', $segments);
        }

        return $namespace === '' ? $name : $namespace . '\\' . $name;
    }
}
