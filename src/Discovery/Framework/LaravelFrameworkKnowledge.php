<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Framework;

/**
 * The two Laravel rules ADR-A010 leaves reachable — and no others.
 *
 * Both read **one file's text**, the file the `ClassLocator` already placed. Neither can do
 * otherwise: this class receives no path, no repository and no locator, so a second file is
 * unreachable by construction rather than by restraint (ADR-A010).
 *
 * | Rule | Evidence, in that one file | Outcome |
 * |---|---|---|
 * | **Facade** | the class extends `Illuminate\Support\Facades\Facade`, and its class docblock carries a `@method static … <member>(` tag | framework-known — no item, one diagnostic |
 * | **Local scope** | the class declares `scope<Ucfirst(member)>` | resolve to that member — a project-local slice, fetched |
 *
 * Deliberately **not** implemented, each blocked by ADR-A010 because it needs a second file:
 * `Model::create` / `::where` (`__callStatic` → `Eloquent\Builder`), `::orderBy` (`@mixin` →
 * `Query\Builder`), `::query` (plain `extends`), relation and instance members. Also not
 * implemented: `#[Scope]` attribute scopes, the second form `Model::hasNamedScope()` accepts
 * (`Model.php:1738`), which needs attribute parsing and has no fixture behind it.
 *
 * Every fact this class relies on is cited to `laravel/framework` v12.64.0, the version installed
 * in the M1 fixture:
 *
 * - `Facade::__callStatic()` (`Support/Facades/Facade.php:355`) proxies every static call to a
 *   container-resolved instance, so no member is declared on the facade class itself. The
 *   framework ships the surface as `@method static` tags instead — 31 of them on
 *   `Support/Facades/Log.php`, one of which is `info` (line 25).
 * - `Eloquent\Builder::__call()` consults `hasNamedScope($method)` (`Database/Eloquent/Builder.php:2232`)
 *   before anything else, and `Model::hasNamedScope()` is `method_exists($this, 'scope'.ucfirst($scope))`
 *   (`Database/Eloquent/Model.php:1738`) — a name test against the model's own class, which is why
 *   it fits inside the one-file bound.
 */
final class LaravelFrameworkKnowledge implements FrameworkKnowledge
{
    /**
     * The base class whose subclasses proxy static calls. Matched fully qualified, resolved through
     * the file's own `namespace` and `use` statements — never by the bare name, so an unrelated
     * class called `Facade` is not mistaken for this one.
     */
    private const FACADE_BASE = 'Illuminate\Support\Facades\Facade';

    private const SCOPE_PREFIX = 'scope';

    /**
     * The cardinality each name carries in the Eloquent / Collection **finisher position** this move
     * reads — the last call in a `return`.
     *
     * `many`   — a Collection or paginator
     * `one`    — a single model or null
     * `scalar` — a number or boolean
     *
     * The table is keyed on the member name and nothing else — no receiver type, no argument shape,
     * because neither is among the available facts (ADR-A016). Four names were removed for turning
     * on exactly those, each checked against Laravel's source:
     *
     * - `find` — Laravel declares the conditional return `($id is array ? Collection : TModel|null)`,
     *   so the class is the argument's, not the name's;
     * - `findOrFail` — the same conditional return, and its body delegates to `find($id, $columns)`
     *   on its first line. It was removed for the same reason as `find`, and keeping one without the
     *   other was the inconsistency the final review caught: `findOrFail($ids)` on an array already
     *   returns a Collection, so `findOrFail(...)` → `get()` claimed a shape change that never
     *   happened;
     * - `chunk` — `BuildsQueries::chunk()` returns `bool` and takes a callback, `Collection::chunk()`
     *   returns a Collection of chunks;
     * - `toArray` — many rows on a Collection, one model's attributes on a Model.
     *
     * **This is not a claim that every remaining entry is safe under any receiver, and ADR-A023 says
     * so in the open.** One is known not to meet that stricter standard: `Collection::get($key)`
     * returns a single element and `Cache::get($key)` a single value. `get` stays because it is the
     * finisher the move exists for, and because firing needs *both* sides table-known and in
     * different classes. Widening the table, or inferring a receiver, needs its own ADR.
     *
     * Unknown names return null and never produce an assertion: guessing is the inference ADR-A003
     * forbids, and losing a detection is the conservative direction — silence, not a wrong subject.
     */
    private const CARDINALITY = [
        'get' => 'many',        'all' => 'many',         'paginate' => 'many',
        'simplePaginate' => 'many', 'cursor' => 'many',  'pluck' => 'many',

        'first' => 'one',       'firstOrFail' => 'one',  'sole' => 'one',
        'firstWhere' => 'one',  'last' => 'one',

        'count' => 'scalar',    'exists' => 'scalar',    'doesntExist' => 'scalar',
        'sum' => 'scalar',      'avg' => 'scalar',       'max' => 'scalar',
        'min' => 'scalar',      'value' => 'scalar',
    ];


    /**
     * The cardinality class of an Eloquent/Collection finisher, or null when the name is unknown.
     *
     * A **closed table**, in the same spirit as the facade and `scope<Name>` rules above: the
     * framework supplies the naming fact, the extractor supplies none. Every entry is a documented
     * Eloquent or Collection terminal whose return cardinality is not in dispute.
     *
     * Unknown names return null, and an unknown name must never produce an assertion — a project's
     * own `->fetchThings()` says nothing about cardinality, and guessing would be the inference
     * ADR-A003 forbids.
     */
    public function returnCardinalityOf(string $member): ?string
    {
        return self::CARDINALITY[$member] ?? null;
    }

    public function declarationOf(string $member, string $classFileText): ?FrameworkDeclaration
    {
        if ($member === '' || !$this->extendsFacade($classFileText)) {
            return null;
        }

        return $this->methodTagFor($member, $classFileText);
    }

    public function sameFileMemberFor(string $member, string $classFileText): ?string
    {
        if ($member === '' || str_starts_with($member, self::SCOPE_PREFIX)) {
            // `Package::scopeActive()` is a direct call to the scope method, not a scope
            // invocation. If it exists the slicer already found it; inventing `scopeScopeActive`
            // would be a guess.
            return null;
        }

        $scope = self::SCOPE_PREFIX . ucfirst($member);

        return $this->declaresFunction($scope, $classFileText) ? $scope : null;
    }

    // ---------------------------------------------------------------- the facade rule

    /**
     * Whether this file's class extends the facade base, with the parent name resolved the way PHP
     * resolves it: through a matching `use` statement, or else relative to the file's `namespace`.
     */
    private function extendsFacade(string $text): bool
    {
        $tokens = $this->tokenize($text);
        $parent = $this->parentName($tokens);

        if ($parent === null) {
            return false;
        }

        if (str_starts_with($parent, '\\')) {
            return ltrim($parent, '\\') === self::FACADE_BASE;
        }

        $imports = $this->imports($tokens);
        $head = explode('\\', $parent)[0];

        if (isset($imports[$head])) {
            $rest = substr($parent, strlen($head));

            return $imports[$head] . $rest === self::FACADE_BASE;
        }

        $namespace = $this->namespaceOf($tokens);

        return ($namespace === '' ? $parent : $namespace . '\\' . $parent) === self::FACADE_BASE;
    }

    /**
     * The `@method static … <member>(` tag that documents the proxied member, with the line it sits
     * on. Only doc comments **before** the class declaration are read — the class docblock is where
     * a facade's surface is declared, and a tag inside a method's own docblock is prose.
     */
    private function methodTagFor(string $member, string $text): ?FrameworkDeclaration
    {
        foreach ($this->classDocComments($this->tokenize($text)) as [$comment, $firstLine]) {
            foreach (explode("\n", $comment) as $offset => $line) {
                if (
                    preg_match(
                        '/@method\s+static\s+[^(]*?\b' . preg_quote($member, '/') . '\s*\(/',
                        $line,
                        $match
                    ) !== 1
                ) {
                    continue;
                }

                return new FrameworkDeclaration($this->tagText($line), $firstLine + $offset);
            }
        }

        return null;
    }

    /**
     * The tag as written, without the docblock's leading `*` and surrounding space.
     */
    private function tagText(string $line): string
    {
        return trim(preg_replace('/^\s*\*\s?/', '', $line) ?? $line);
    }

    /**
     * @param list<array{id:int|null,text:string,line:int}> $tokens
     *
     * @return list<array{0:string,1:int}> doc comment text and its first line, in file order
     */
    private function classDocComments(array $tokens): array
    {
        $comments = [];

        foreach ($tokens as $token) {
            if ($token['id'] === T_CLASS) {
                break; // past the class declaration, a docblock documents a member, not the surface.
            }

            if ($token['id'] === T_DOC_COMMENT) {
                $comments[] = [$token['text'], $token['line']];
            }
        }

        return $comments;
    }

    // ---------------------------------------------------------------- shared reading

    /**
     * @param list<array{id:int|null,text:string,line:int}> $tokens
     */
    private function parentName(array $tokens): ?string
    {
        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            if ($tokens[$i]['id'] !== T_EXTENDS) {
                continue;
            }

            $next = $this->nextSignificant($tokens, $i + 1);

            if ($next === null) {
                return null;
            }

            return in_array(
                $tokens[$next]['id'],
                [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED],
                true
            ) ? $tokens[$next]['text'] : null;
        }

        return null;
    }

    /**
     * @param list<array{id:int|null,text:string,line:int}> $tokens
     */
    private function namespaceOf(array $tokens): string
    {
        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            if ($tokens[$i]['id'] !== T_NAMESPACE) {
                continue;
            }

            $next = $this->nextSignificant($tokens, $i + 1);

            if ($next !== null && in_array($tokens[$next]['id'], [T_STRING, T_NAME_QUALIFIED], true)) {
                return $tokens[$next]['text'];
            }
        }

        return '';
    }

    /**
     * Short name to fully-qualified name, from this file's own `use` statements. Aliases win.
     *
     * @param list<array{id:int|null,text:string,line:int}> $tokens
     *
     * @return array<string, string>
     */
    private function imports(array $tokens): array
    {
        $imports = [];
        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            if ($tokens[$i]['id'] !== T_USE) {
                continue;
            }

            $next = $this->nextSignificant($tokens, $i + 1);

            if ($next === null || $tokens[$next]['id'] === T_FUNCTION || $tokens[$next]['id'] === T_CONST) {
                continue;
            }

            $qualified = null;
            $alias = null;
            $sawAs = false;

            for ($j = $next; $j < $total; $j++) {
                $id = $tokens[$j]['id'];

                if ($id === null && ($tokens[$j]['text'] === ';' || $tokens[$j]['text'] === '{')) {
                    $i = $j;
                    break;
                }

                if ($id === T_AS) {
                    $sawAs = true;
                    continue;
                }

                if (!in_array($id, [T_STRING, T_NAME_QUALIFIED], true)) {
                    continue;
                }

                if ($sawAs) {
                    $alias = $tokens[$j]['text'];
                    continue;
                }

                $qualified = $qualified === null ? $tokens[$j]['text'] : $qualified . '\\' . $tokens[$j]['text'];
            }

            if ($qualified === null || $qualified === '') {
                continue;
            }

            $segments = explode('\\', $qualified);
            $imports[$alias ?? (string) end($segments)] = $qualified;
        }

        return $imports;
    }

    /**
     * Whether this text declares a function of exactly this name. A `@method` tag naming it is a
     * docblock, not a declaration, so comments are skipped.
     */
    private function declaresFunction(string $name, string $text): bool
    {
        $tokens = $this->tokenize($text);
        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            if ($tokens[$i]['id'] !== T_FUNCTION) {
                continue;
            }

            $next = $this->nextSignificant($tokens, $i + 1);

            if ($next !== null && $tokens[$next]['id'] === T_STRING && $tokens[$next]['text'] === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{id:int|null,text:string,line:int}>
     */
    private function tokenize(string $code): array
    {
        $tokens = [];

        foreach (@token_get_all($code) as $token) {
            $tokens[] = is_array($token)
                ? ['id' => $token[0], 'text' => $token[1], 'line' => $token[2]]
                : ['id' => null, 'text' => $token, 'line' => 0];
        }

        return $tokens;
    }

    /**
     * @param list<array{id:int|null,text:string,line:int}> $tokens
     */
    private function nextSignificant(array $tokens, int $from): ?int
    {
        for ($i = $from, $total = count($tokens); $i < $total; $i++) {
            $id = $tokens[$i]['id'];

            if ($id === null || !in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                return $i;
            }
        }

        return null;
    }
}
