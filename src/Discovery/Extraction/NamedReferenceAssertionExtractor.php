<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Extraction;

use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedFile;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Ports\MemberSlicer;

/**
 * Cross-file references the region names — a model, a client method, an enum.
 *
 * R2's recall is defined by a closed list of three forms (01-architecture.md §3.3), each earned by
 * a finding. Any other form produces **no** assertion: no inference, no guessing (ADR-A003). The
 * list is deliberately short, and widening it is an architecture change, not a bug fix.
 *
 * | Form                       | Example                              | Earned by |
 * |----------------------------|--------------------------------------|-----------|
 * | `Name::member`             | `PlaidItemStatus::REVOKED`           | Exp 4     |
 * | `$this->prop->method(`     | `$this->plaidClient->createLinkToken(` | Exp 4   |
 * | class name in `new`/type/static-call position | `PlaidAccount`     | Exp 1     |
 *
 * Every form resolves **through the file's own `use` block**. A name the block does not import is
 * not a cross-file reference at all — it is the absence case, and belongs to
 * `OwnFileAssertionExtractor`. So do same-file siblings (`$this->method(`): research context type
 * 1, not type 2. The two extractors partition the same scan and never both fire on one reference.
 *
 * Depth one: this reads the changed file only. The resolved file's own references are never read
 * (P3, P4), and there is no worklist here that could hold one.
 *
 * ---
 *
 * **Subject encoding.** A subject is `Fqcn` for a bare class reference and `Fqcn::member` when the
 * region names a member. `LeverPolicy` and `NamedReferenceResolver` split on the last `::`; a
 * fully-qualified name never contains one, so the split is unambiguous.
 */
final class NamedReferenceAssertionExtractor implements RegionAssertionExtractor
{
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

        $imports = $this->imports($fileText);

        if ($imports === []) {
            return [];
        }

        $tokens = $this->tokenize(implode("\n", $region->addedLines));
        $subjects = [];

        foreach ($this->memberAccesses($tokens, $imports) as $subject) {
            $subjects[] = $subject;
        }

        foreach ($this->propertyCalls($tokens, $imports, $fileText) as $subject) {
            $subjects[] = $subject;
        }

        foreach ($this->bareClassReferences($tokens, $imports) as $subject) {
            $subjects[] = $subject;
        }

        $assertions = [];

        foreach (array_values(array_unique($subjects)) as $subject) {
            $assertions[] = new Assertion(
                AssertionKind::NamedReference,
                $subject,
                $file->path,
                $region,
                sprintf(
                    'the region depends on %s, whose contract is defined in another file',
                    $subject,
                ),
            );
        }

        return $assertions;
    }

    /**
     * Form 1 — `Name::member`, a static or enum member access.
     *
     * @param list<array{id:int|null,text:string}> $tokens
     * @param array<string, string>                $imports
     *
     * @return list<string>
     */
    private function memberAccesses(array $tokens, array $imports): array
    {
        $subjects = [];
        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            if ($tokens[$i]['id'] !== T_STRING || !isset($imports[$tokens[$i]['text']])) {
                continue;
            }

            $operator = $this->nextSignificant($tokens, $i + 1);

            if ($operator === null || $tokens[$operator]['id'] !== T_DOUBLE_COLON) {
                continue;
            }

            $member = $this->nextSignificant($tokens, $operator + 1);

            if ($member === null || $tokens[$member]['id'] !== T_STRING) {
                continue; // `Foo::class` and `Foo::$var` name no member contract.
            }

            $subjects[] = $imports[$tokens[$i]['text']] . '::' . $tokens[$member]['text'];
        }

        return $subjects;
    }

    /**
     * Form 2 — `$this->property->method(`, where the property's declared type is imported.
     *
     * @param list<array{id:int|null,text:string}> $tokens
     * @param array<string, string>                $imports
     *
     * @return list<string>
     */
    private function propertyCalls(array $tokens, array $imports, string $fileText): array
    {
        $subjects = [];
        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            if ($tokens[$i]['id'] !== T_VARIABLE || $tokens[$i]['text'] !== '$this') {
                continue;
            }

            $arrow = $this->nextSignificant($tokens, $i + 1);
            $property = $arrow === null ? null : $this->nextSignificant($tokens, $arrow + 1);
            $secondArrow = $property === null ? null : $this->nextSignificant($tokens, $property + 1);
            $method = $secondArrow === null ? null : $this->nextSignificant($tokens, $secondArrow + 1);
            $open = $method === null ? null : $this->nextSignificant($tokens, $method + 1);

            if (
                $arrow === null || $tokens[$arrow]['id'] !== T_OBJECT_OPERATOR
                || $property === null || $tokens[$property]['id'] !== T_STRING
                || $secondArrow === null || $tokens[$secondArrow]['id'] !== T_OBJECT_OPERATOR
                || $method === null || $tokens[$method]['id'] !== T_STRING
                || $open === null || $tokens[$open]['id'] !== null || $tokens[$open]['text'] !== '('
            ) {
                continue;
            }

            $type = $this->declaredTypeOf($tokens[$property]['text'], $fileText);

            if ($type === null || !isset($imports[$type])) {
                continue; // an untyped or non-imported collaborator is not depth-one reachable.
            }

            $subjects[] = $imports[$type] . '::' . $tokens[$method]['text'];
        }

        return $subjects;
    }

    /**
     * Form 3 — a class name in a `new`, type, or static-call position.
     *
     * A name followed by `::` is form 1 and is skipped here, so one reference never yields two
     * assertions.
     *
     * @param list<array{id:int|null,text:string}> $tokens
     * @param array<string, string>                $imports
     *
     * @return list<string>
     */
    private function bareClassReferences(array $tokens, array $imports): array
    {
        $subjects = [];
        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            if ($tokens[$i]['id'] !== T_STRING) {
                continue;
            }

            $name = $tokens[$i]['text'];

            if (in_array(strtolower($name), self::NOT_CLASS_NAMES, true) || !isset($imports[$name])) {
                continue;
            }

            $after = $this->nextSignificant($tokens, $i + 1);
            $before = $this->previousSignificant($tokens, $i - 1);

            if ($after !== null && $tokens[$after]['id'] === T_DOUBLE_COLON) {
                continue; // form 1 already covers it.
            }

            $isType = $after !== null && $tokens[$after]['id'] === T_VARIABLE;
            $isConstruction = $before !== null
                && ($tokens[$before]['id'] === T_NEW || $tokens[$before]['id'] === T_INSTANCEOF);
            $isReturnType = $before !== null && $tokens[$before]['id'] === null
                && ($tokens[$before]['text'] === ':' || $tokens[$before]['text'] === '?');

            if ($isType || $isConstruction || $isReturnType) {
                $subjects[] = $imports[$name];
            }
        }

        return $subjects;
    }

    /**
     * The declared type of a property, read from the changed file's own text — a declaration or a
     * promoted constructor parameter, which tokenise the same way.
     */
    private function declaredTypeOf(string $property, string $fileText): ?string
    {
        $tokens = $this->tokenize($fileText);

        foreach ($tokens as $i => $token) {
            if ($token['id'] !== T_VARIABLE || $token['text'] !== '$' . $property) {
                continue;
            }

            $type = $this->previousSignificant($tokens, $i - 1);

            if ($type !== null && $tokens[$type]['id'] === T_STRING) {
                return $tokens[$type]['text'];
            }
        }

        return null;
    }

    /**
     * Short name to fully-qualified name, from the file's own `use` block. Aliases win.
     *
     * @return array<string, string>
     */
    private function imports(string $fileText): array
    {
        $useBlock = $this->slicer->useBlock($fileText);

        if ($useBlock === null) {
            return [];
        }

        $tokens = $this->tokenize($useBlock->text);
        $imports = [];
        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            if ($tokens[$i]['id'] !== T_USE) {
                continue;
            }

            $next = $this->nextSignificant($tokens, $i + 1);

            if ($next !== null && ($tokens[$next]['id'] === T_FUNCTION || $tokens[$next]['id'] === T_CONST)) {
                continue;
            }

            $clause = [];

            for ($j = $i + 1; $j < $total; $j++) {
                if ($tokens[$j]['id'] === null && ($tokens[$j]['text'] === ';' || $tokens[$j]['text'] === ',')) {
                    $imports = $this->withClause($imports, $clause);
                    $clause = [];

                    if ($tokens[$j]['text'] === ';') {
                        $i = $j;
                        break;
                    }

                    continue;
                }

                $clause[] = $tokens[$j];
            }

            $imports = $this->withClause($imports, $clause);
        }

        return $imports;
    }

    /**
     * @param array<string, string>                $imports
     * @param list<array{id:int|null,text:string}> $clause
     *
     * @return array<string, string>
     */
    private function withClause(array $imports, array $clause): array
    {
        $qualified = null;
        $alias = null;
        $sawAs = false;

        foreach ($clause as $token) {
            if ($token['id'] === T_AS) {
                $sawAs = true;
                continue;
            }

            if ($token['id'] !== T_STRING && $token['id'] !== T_NAME_QUALIFIED) {
                continue;
            }

            if ($sawAs) {
                $alias = $token['text'];
                continue;
            }

            $qualified = $qualified === null ? $token['text'] : $qualified . '\\' . $token['text'];
        }

        if ($qualified === null || $qualified === '') {
            return $imports;
        }

        $segments = explode('\\', $qualified);
        $imports[$alias ?? (string) end($segments)] = $qualified;

        return $imports;
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
