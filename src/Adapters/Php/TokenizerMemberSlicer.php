<?php

declare(strict_types=1);

namespace ContextDiscovery\Adapters\Php;

use ContextDiscovery\Domain\Source\SourceSlice;
use ContextDiscovery\Ports\MemberSlicer;

/**
 * Member spans read with PHP's own tokenizer — no AST library, no regular expressions
 * (ADR-A004).
 *
 * The tokenizer is not a convenience here, it is the requirement. Only the token stream tells a
 * `use` import apart from a closure's `use (...)` clause and from a trait `use` inside a class
 * body, and only balanced brace counting over tokens survives braces that appear inside strings,
 * heredocs and match arms. A regular expression would produce wrong slices, and a wrong slice
 * corrupts the precision measurement the bundle exists to enable.
 *
 * A member that cannot be found returns null. The caller flags it; nothing is skipped silently
 * (P10).
 *
 * Slices carry no path: this port sees text, never a file. The resolver that knows the path
 * supplies it.
 */
final class TokenizerMemberSlicer implements MemberSlicer
{
    private const PATH_SUPPLIED_BY_CALLER = '';

    public function useBlock(string $fileText): ?SourceSlice
    {
        $tokens = $this->tokenize($fileText);
        $total = count($tokens);

        $firstLine = null;
        $lastLine = null;

        for ($i = 0; $i < $total; $i++) {
            if ($this->opensTypeDeclaration($tokens, $i)) {
                break; // imports precede the first type; anything later is a trait use.
            }

            if ($tokens[$i]['id'] !== T_USE) {
                continue;
            }

            $previous = $this->previousSignificant($tokens, $i - 1);

            if ($previous !== null && $tokens[$previous]['id'] === null && $tokens[$previous]['text'] === ')') {
                continue; // `function (...) use ($x)` closes over a variable, it imports nothing.
            }

            $end = $this->findSemicolon($tokens, $i);

            if ($end === null) {
                break;
            }

            $firstLine ??= $tokens[$i]['line'];
            $lastLine = $tokens[$end]['line'];
            $i = $end;
        }

        if ($firstLine === null || $lastLine === null) {
            return null;
        }

        return new SourceSlice(
            self::PATH_SUPPLIED_BY_CALLER,
            null,
            $firstLine,
            $lastLine,
            $this->textOfLines($fileText, $firstLine, $lastLine),
        );
    }

    public function member(string $fileText, string $memberName): ?SourceSlice
    {
        foreach ($this->declarations($this->tokenize($fileText)) as $declaration) {
            if ($declaration['name'] !== $memberName) {
                continue;
            }

            return new SourceSlice(
                self::PATH_SUPPLIED_BY_CALLER,
                $declaration['name'],
                $declaration['firstLine'],
                $declaration['lastLine'],
                $this->textOfLines($fileText, $declaration['firstLine'], $declaration['lastLine']),
            );
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function memberNames(string $fileText): array
    {
        $names = [];

        foreach ($this->declarations($this->tokenize($fileText)) as $declaration) {
            if (!in_array($declaration['name'], $names, true)) {
                $names[] = $declaration['name'];
            }
        }

        return $names;
    }

    public function enclosingMemberName(string $fileText, int $line): ?string
    {
        foreach ($this->declarations($this->tokenize($fileText)) as $declaration) {
            if ($line >= $declaration['firstLine'] && $line <= $declaration['lastLine']) {
                return $declaration['name'];
            }
        }

        return null;
    }

    public function memberOwningLine(string $fileText, int $line): ?string
    {
        $tokens = $this->tokenize($fileText);
        $nested = $this->nestedFunctionLines($tokens);

        if ($nested === null) {
            return null; // a body that will not close: the scope cannot be told, so nothing is said.
        }

        foreach ($nested as $span) {
            if ($line >= $span['firstLine'] && $line <= $span['lastLine']) {
                return null; // the line belongs to a closure, not to the member around it.
            }
        }

        return $this->enclosingMemberName($fileText, $line);
    }

    /**
     * The line span of every function body that opens *inside* another function body — the closure
     * passed to `map()`, the `DB::transaction()` callback, a method of an anonymous class declared
     * inside a member.
     *
     * The rule is positional and needs no list of framework callbacks: the first function body met
     * is a declaration's own, and every function keyword encountered before that body closes is
     * nested within it. Nesting deeper than one level needs no extra work, because the outermost
     * body's span already covers it.
     *
     * Depth is read from the token stream, never from the raw text, so a brace inside a string, a
     * comment or a heredoc cannot open or close a scope — the reason `token_get_all()` is used here
     * rather than counting characters (ADR-A004).
     *
     * @param list<array{id:int|null,text:string,line:int}> $tokens
     *
     * @return list<array{firstLine:int,lastLine:int}>|null Null when a body cannot be closed, which
     *                                                      the caller must read as "cannot tell".
     */
    private function nestedFunctionLines(array $tokens): ?array
    {
        $spans = [];
        $outermostEnd = null;

        for ($i = 0, $total = count($tokens); $i < $total; $i++) {
            if ($tokens[$i]['id'] !== T_FUNCTION) {
                continue;
            }

            $open = $this->functionBodyOpen($tokens, $i);

            if ($open === null) {
                continue; // an abstract or interface declaration has no body to be inside.
            }

            $close = $this->matchBrace($tokens, $open);

            if ($close === null) {
                return null;
            }

            if ($outermostEnd !== null && $i < $outermostEnd) {
                $spans[] = ['firstLine' => $tokens[$open]['line'], 'lastLine' => $tokens[$close]['line']];

                continue;
            }

            $outermostEnd = $close;
        }

        return $spans;
    }

    /**
     * The index of the `{` opening this function's body, or null when it declares none.
     *
     * Parentheses and brackets are counted so a closure's `use (...)` clause, a default value and an
     * attribute cannot be mistaken for the body.
     *
     * @param list<array{id:int|null,text:string,line:int}> $tokens
     */
    private function functionBodyOpen(array $tokens, int $from): ?int
    {
        $paren = 0;
        $bracket = 0;

        for ($i = $from, $total = count($tokens); $i < $total; $i++) {
            $id = $tokens[$i]['id'];
            $text = $tokens[$i]['text'];

            if ($id === T_ATTRIBUTE) {
                $bracket++;
                continue;
            }

            if ($id !== null) {
                continue;
            }

            match (true) {
                $text === '(' => $paren++,
                $text === ')' => $paren--,
                $text === '[' => $bracket++,
                $text === ']' => $bracket--,
                default => null,
            };

            if ($paren !== 0 || $bracket !== 0) {
                continue;
            }

            if ($text === ';') {
                return null;
            }

            if ($text === '{') {
                return $i;
            }
        }

        return null;
    }

    /**
     * Every named declaration at member level, in source order.
     *
     * Member level is one brace inside the enclosing type body, so a closure, a match arm, a
     * nested anonymous class and a constructor-promoted parameter are all too deep to be mistaken
     * for a member. Each declaration is skipped over once its span is known, which is also what
     * keeps the depth counter honest across a member's own braces.
     *
     * @param list<array{id:int|null,text:string,line:int}> $tokens
     *
     * @return list<array{name:string,firstLine:int,lastLine:int}>
     */
    private function declarations(array $tokens): array
    {
        $total = count($tokens);
        $depth = 0;
        $typeBodyDepth = null;
        $pendingType = false;
        $declarations = [];

        for ($i = 0; $i < $total; $i++) {
            $id = $tokens[$i]['id'];
            $text = $tokens[$i]['text'];

            if ($id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
                $depth++;
                continue;
            }

            if ($id === null && $text === '{') {
                $depth++;

                if ($pendingType) {
                    $typeBodyDepth = $depth;
                    $pendingType = false;
                }

                continue;
            }

            if ($id === null && $text === '}') {
                if ($typeBodyDepth !== null && $depth === $typeBodyDepth) {
                    $typeBodyDepth = null;
                }

                $depth--;
                continue;
            }

            if ($this->opensTypeDeclaration($tokens, $i)) {
                $pendingType = true;
                continue;
            }

            if ($depth !== ($typeBodyDepth ?? 0)) {
                continue;
            }

            $declaration = $this->declarationAt($tokens, $i, $typeBodyDepth !== null);

            if ($declaration === null) {
                continue;
            }

            $declarations[] = [
                'name' => $declaration['name'],
                'firstLine' => $tokens[$declaration['start']]['line'],
                'lastLine' => $tokens[$declaration['end']]['line'],
            ];

            $i = $declaration['end'];
        }

        return $declarations;
    }

    /**
     * @param list<array{id:int|null,text:string,line:int}> $tokens
     *
     * @return array{name:string,start:int,end:int}|null
     */
    private function declarationAt(array $tokens, int $index, bool $insideTypeBody): ?array
    {
        $id = $tokens[$index]['id'];

        if ($id === T_FUNCTION) {
            $previous = $this->previousSignificant($tokens, $index - 1);

            if ($previous !== null && $tokens[$previous]['id'] === T_USE) {
                return null; // `use function Foo\bar;` imports a function, it does not declare one.
            }

            $nameIndex = $this->nextSignificant($tokens, $index + 1);

            if ($nameIndex !== null && $tokens[$nameIndex]['id'] === null && $tokens[$nameIndex]['text'] === '&') {
                $nameIndex = $this->nextSignificant($tokens, $nameIndex + 1);
            }

            if ($nameIndex === null || $tokens[$nameIndex]['id'] !== T_STRING) {
                return null; // an anonymous function declares no member.
            }

            $end = $this->findFunctionEnd($tokens, $nameIndex);

            return $end === null
                ? null
                : ['name' => $tokens[$nameIndex]['text'], 'start' => $this->startOf($tokens, $index), 'end' => $end];
        }

        if (!$insideTypeBody) {
            return null;
        }

        if ($id === T_CONST) {
            $end = $this->findSemicolon($tokens, $index);

            if ($end === null) {
                return null;
            }

            // A typed constant puts the type between `const` and the name, so the name is the
            // last identifier before the assignment.
            $name = null;

            for ($j = $index + 1; $j < $end; $j++) {
                if ($tokens[$j]['id'] === null && $tokens[$j]['text'] === '=') {
                    break;
                }

                if ($tokens[$j]['id'] === T_STRING) {
                    $name = $tokens[$j]['text'];
                }
            }

            return $name === null
                ? null
                : ['name' => $name, 'start' => $this->startOf($tokens, $index), 'end' => $end];
        }

        if ($id === T_CASE) {
            $nameIndex = $this->nextSignificant($tokens, $index + 1);

            if ($nameIndex === null || $tokens[$nameIndex]['id'] !== T_STRING) {
                return null;
            }

            $end = $this->findSemicolon($tokens, $index);

            return $end === null
                ? null
                : ['name' => $tokens[$nameIndex]['text'], 'start' => $this->startOf($tokens, $index), 'end' => $end];
        }

        if ($id === T_VARIABLE) {
            $end = $this->findSemicolon($tokens, $index);

            return $end === null
                ? null
                : [
                    'name' => substr($tokens[$index]['text'], 1),
                    'start' => $this->startOf($tokens, $index),
                    'end' => $end,
                ];
        }

        return null;
    }

    /**
     * Walks back over the modifiers, type, attributes and docblock that belong to a declaration.
     *
     * Attribute and annotation lines immediately preceding a member are part of what a reviewer
     * must see, so they are inside the slice (ADR-A004). An ordinary `//` comment is not, and
     * stops the walk — it may belong to whatever came before.
     *
     * @param list<array{id:int|null,text:string,line:int}> $tokens
     */
    private function startOf(array $tokens, int $index): int
    {
        $start = $index;

        for ($i = $index - 1; $i >= 0; $i--) {
            $id = $tokens[$i]['id'];
            $text = $tokens[$i]['text'];

            if ($id === T_WHITESPACE) {
                continue;
            }

            if ($id === null && $text === ']') {
                $open = $this->matchAttributeStart($tokens, $i);

                if ($open === null) {
                    break;
                }

                $start = $open;
                $i = $open;
                continue;
            }

            if ($id === T_DOC_COMMENT) {
                $start = $i;
                continue;
            }

            if ($id === T_COMMENT || $id === T_OPEN_TAG || $id === T_CLOSE_TAG) {
                break;
            }

            if ($id === null && ($text === ';' || $text === '{' || $text === '}' || $text === ',')) {
                break;
            }

            $start = $i;
        }

        return $start;
    }

    /**
     * @param list<array{id:int|null,text:string,line:int}> $tokens
     */
    private function matchAttributeStart(array $tokens, int $close): ?int
    {
        $depth = 0;

        for ($i = $close; $i >= 0; $i--) {
            $id = $tokens[$i]['id'];
            $text = $tokens[$i]['text'];

            if ($id === null && $text === ']') {
                $depth++;
                continue;
            }

            if ($id === T_ATTRIBUTE) {
                $depth--;

                if ($depth === 0) {
                    return $i;
                }

                continue;
            }

            if ($id === null && $text === '[') {
                $depth--;

                if ($depth === 0) {
                    return null; // an array literal, not an attribute.
                }
            }
        }

        return null;
    }

    /**
     * @param list<array{id:int|null,text:string,line:int}> $tokens
     */
    private function findFunctionEnd(array $tokens, int $from): ?int
    {
        $paren = 0;
        $bracket = 0;

        for ($i = $from, $total = count($tokens); $i < $total; $i++) {
            $id = $tokens[$i]['id'];
            $text = $tokens[$i]['text'];

            if ($id === T_ATTRIBUTE) {
                $bracket++;
                continue;
            }

            if ($id !== null) {
                continue;
            }

            match (true) {
                $text === '(' => $paren++,
                $text === ')' => $paren--,
                $text === '[' => $bracket++,
                $text === ']' => $bracket--,
                default => null,
            };

            if ($paren !== 0 || $bracket !== 0) {
                continue;
            }

            if ($text === ';') {
                return $i; // an abstract or interface declaration has no body.
            }

            if ($text === '{') {
                return $this->matchBrace($tokens, $i);
            }
        }

        return null;
    }

    /**
     * @param list<array{id:int|null,text:string,line:int}> $tokens
     */
    private function findSemicolon(array $tokens, int $from): ?int
    {
        $paren = 0;
        $bracket = 0;
        $brace = 0;

        for ($i = $from, $total = count($tokens); $i < $total; $i++) {
            $id = $tokens[$i]['id'];
            $text = $tokens[$i]['text'];

            if ($id === T_ATTRIBUTE || $id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
                $id === T_ATTRIBUTE ? $bracket++ : $brace++;
                continue;
            }

            if ($id !== null) {
                continue;
            }

            match (true) {
                $text === '(' => $paren++,
                $text === ')' => $paren--,
                $text === '[' => $bracket++,
                $text === ']' => $bracket--,
                $text === '{' => $brace++,
                $text === '}' => $brace--,
                default => null,
            };

            if ($text === ';' && $paren === 0 && $bracket === 0 && $brace === 0) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param list<array{id:int|null,text:string,line:int}> $tokens
     */
    private function matchBrace(array $tokens, int $open): ?int
    {
        $depth = 0;

        for ($i = $open, $total = count($tokens); $i < $total; $i++) {
            $id = $tokens[$i]['id'];
            $text = $tokens[$i]['text'];

            if ($id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
                $depth++;
                continue;
            }

            if ($id !== null) {
                continue;
            }

            if ($text === '{') {
                $depth++;
                continue;
            }

            if ($text === '}') {
                $depth--;

                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null; // unbalanced: the member cannot be sliced, so it is flagged (P10).
    }

    /**
     * @param list<array{id:int|null,text:string,line:int}> $tokens
     */
    private function opensTypeDeclaration(array $tokens, int $index): bool
    {
        if (!in_array($tokens[$index]['id'], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
            return false;
        }

        $previous = $this->previousSignificant($tokens, $index - 1);

        if ($previous === null) {
            return true;
        }

        // `Foo::class` is a constant, and `new class` opens a body whose members are not this
        // file's members.
        return $tokens[$previous]['id'] !== T_DOUBLE_COLON && $tokens[$previous]['id'] !== T_NEW;
    }

    /**
     * @return list<array{id:int|null,text:string,line:int}>
     */
    private function tokenize(string $fileText): array
    {
        $tokens = [];
        $line = 1;

        foreach (@token_get_all($fileText) as $token) {
            if (is_array($token)) {
                $tokens[] = ['id' => $token[0], 'text' => $token[1], 'line' => $line];
                $line += substr_count($token[1], "\n");
                continue;
            }

            $tokens[] = ['id' => null, 'text' => $token, 'line' => $line];
        }

        return $tokens;
    }

    /**
     * @param list<array{id:int|null,text:string,line:int}> $tokens
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
     * @param list<array{id:int|null,text:string,line:int}> $tokens
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
        return $id !== null
            && in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
    }

    private function textOfLines(string $fileText, int $firstLine, int $lastLine): string
    {
        $lines = explode("\n", $fileText);

        return implode("\n", array_slice($lines, $firstLine - 1, $lastLine - $firstLine + 1));
    }
}
