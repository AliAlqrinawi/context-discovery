<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Extraction;

use ContextDiscovery\Discovery\Framework\FrameworkKnowledge;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedFile;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Ports\MemberSlicer;

/**
 * Emits `ChangedReturnContract` when a body still satisfies its declared signature but no longer
 * returns the same *kind* of value.
 *
 * The finding this exists for: `return $query->get();` becomes `return $query->first();` inside
 * `getAll(...): Collection`. Nothing in the signature moved, so `ChangedSignatureAssertionExtractor`
 * is silent by design — and every caller that treats the result as a collection is now wrong, off
 * screen, with no type error to catch it.
 *
 * **Deliberately narrow.** Four conditions must all hold, and any one of them failing yields
 * nothing rather than a guess (ADR-A003):
 *
 * 1. the region both **removes and adds a `return`** — an assignment or a renamed helper call is
 *    not a return contract and produces nothing;
 * 2. each return's **terminal call** is a name the framework table knows — a project's own
 *    `->fetchThings()` is unknown, so it is passed over rather than assumed;
 * 3. the two names sit in **different cardinality classes** — `get()` → `all()` is many-to-many and
 *    says nothing about the contract;
 * 4. the **added return itself** is the member's own — inside a member the slicer can name, and
 *    not inside a closure declared within it, so the assertion has a subject the caller search can
 *    look for and a claim that is true of that subject.
 *
 * The cardinality table itself lives in `FrameworkKnowledge`, beside the facade and `scope<Name>`
 * rules (ADR-A011): the framework supplies the naming fact, this extractor supplies none.
 *
 * Resolution is `CallerResolver`, the same bounded grep `ChangedSignature` uses. The question is
 * identical — *who calls this?* — so no new resolution machinery is introduced.
 */
final class ChangedReturnContractAssertionExtractor implements RegionAssertionExtractor
{
    public function __construct(
        private readonly MemberSlicer $slicer,
        private readonly FrameworkKnowledge $framework,
    ) {
    }

    /**
     * @return list<Assertion>
     */
    public function forRegion(ChangedFile $file, ChangedRegion $region, string $fileText): array
    {
        $old = $this->cardinalities($region->removedLines);
        $new = $this->cardinalities($region->addedLines);

        if ($old === [] || $new === []) {
            return []; // condition 1 and 2: a return on each side, both terminals known.
        }

        // Condition 3. Compared as sets so a region that rewrites several returns at once is
        // judged on whether the *set* of shapes moved, not on line pairing the diff never states.
        if ($old === $new) {
            return [];
        }

        // Condition 4. The subject must be the member whose own contract moved.
        $member = $this->memberOfChangedReturn($region, $fileText);

        if ($member === null) {
            return [];
        }

        return [
            new Assertion(
                AssertionKind::ChangedReturnContract,
                $member,
                $file->path,
                $region,
                sprintf(
                    'the body of %s now returns %s where it returned %s; its declared signature is '
                    . 'unchanged, so its call sites are not shown by the diff',
                    $member,
                    $this->describe($new),
                    $this->describe($old),
                ),
            ),
        ];
    }

    /**
     * The member enclosing the added return itself — not the one enclosing the start of the hunk.
     *
     * `$region->firstLine` is where the *hunk* begins, and git opens a hunk with context lines. That
     * start routinely sits on the blank line between two members, or inside the member above the one
     * that changed, so reading it as the changed line names no member or the wrong one.
     *
     * `--repo` is the post-image tree — the input contract in `03-interfaces.md` §1, which ADR-A023
     * made explicit because this is the first move to depend on it — so the added return's own line
     * number is recoverable by finding its text within the span the region already declares. No new
     * region data is needed, and a tree that is *not* the post-image yields silence, not a guess.
     *
     * Each recognised added return is tried in order, and a line whose text is not *uniquely* placed
     * within the span is passed over rather than guessed: sending the caller search after an
     * unrelated method is worse than saying nothing.
     */
    private function memberOfChangedReturn(ChangedRegion $region, string $fileText): ?string
    {
        $lines = explode("\n", $fileText);

        foreach ($region->addedLines as $added) {
            if ($this->classOf($added) === null) {
                continue;
            }

            $line = $this->uniqueLineOf($added, $lines, $region);

            if ($line === null) {
                continue;
            }

            // `memberOwningLine`, not `enclosingMemberName`: a return inside a `map()` callback
            // sits within `outer()` without being `outer()`'s return, and saying otherwise would
            // state something false about a method whose contract never moved.
            $member = $this->slicer->memberOwningLine($fileText, $line);

            if ($member !== null) {
                return $member;
            }
        }

        return null;
    }

    /**
     * The 1-indexed line within the region's span whose text is exactly this added line, or null
     * when the span holds no such line or more than one.
     *
     * Only the span is searched: an added line is by construction inside its own hunk, and looking
     * past it would let an identical statement elsewhere in the file answer for it.
     *
     * @param list<string> $lines
     */
    private function uniqueLineOf(string $text, array $lines, ChangedRegion $region): ?int
    {
        $found = null;
        $needle = rtrim($text, "\r");

        for ($number = $region->firstLine; $number <= $region->lastLine; $number++) {
            if (!isset($lines[$number - 1]) || rtrim($lines[$number - 1], "\r") !== $needle) {
                continue;
            }

            if ($found !== null) {
                return null;
            }

            $found = $number;
        }

        return $found;
    }

    /**
     * The distinct cardinality classes returned by one side of the region, sorted so two sides are
     * comparable as sets. Empty when no line returns a value the table recognises.
     *
     * @param list<string> $lines
     *
     * @return list<string>
     */
    private function cardinalities(array $lines): array
    {
        $classes = [];

        foreach ($lines as $line) {
            $class = $this->classOf($line);

            if ($class !== null) {
                $classes[] = $class;
            }
        }

        $classes = array_values(array_unique($classes));
        sort($classes);

        return $classes;
    }

    /**
     * The cardinality class this line returns, or null when it is not a return, not a call, or ends
     * in a name the framework table does not know.
     */
    private function classOf(string $line): ?string
    {
        $terminal = $this->terminalCall($line);

        return $terminal === null ? null : $this->framework->returnCardinalityOf($terminal);
    }

    /**
     * The last method called in a `return` statement *at the top level of the expression* — `first`
     * in `return $query->where(...)->first();`.
     *
     * Only a `return` line is considered, and only a call that is not itself an argument to another
     * call, because that is the value that leaves the method. In
     * `return $items->map(fn ($b) => $b->rel->first());` the value that leaves is `map`'s, not
     * `first`'s: the arrow function is a callback, and reading its finisher as the method's own is
     * the single-line form of the closure mistake condition 4 rejects.
     *
     * String literals are blanked before depth is counted, so a bracket inside a quoted argument
     * cannot shift the level. A line with no `return`, or a return of something that is not a
     * method call, yields null.
     */
    private function terminalCall(string $line): ?string
    {
        if (preg_match('/^\s*return\b/', $line) !== 1) {
            return null;
        }

        $text = $this->withoutStringLiterals($line);
        $found = preg_match_all(
            '/(?:->|::)\s*([A-Za-z_]\w*)\s*\(/',
            $text,
            $matches,
            PREG_OFFSET_CAPTURE,
        );

        if ($found === 0 || $found === false) {
            return null;
        }

        $terminal = null;

        foreach ($matches[1] as $index => $capture) {
            if ($this->depthAt($text, (int) $matches[0][$index][1]) === 0) {
                $terminal = (string) $capture[0];
            }
        }

        return $terminal;
    }

    /**
     * The line with every quoted literal emptied, so its contents cannot be read as syntax.
     */
    private function withoutStringLiterals(string $line): string
    {
        return preg_replace('/([\'"])(?:\\\\.|(?!\1).)*\1/', '$1$1', $line) ?? $line;
    }

    /**
     * How many parentheses and brackets are still open at this offset — zero means the call there
     * is an argument to nothing.
     */
    private function depthAt(string $text, int $offset): int
    {
        $before = substr($text, 0, $offset);

        return substr_count($before, '(') - substr_count($before, ')')
            + substr_count($before, '[') - substr_count($before, ']');
    }

    /**
     * @param list<string> $classes
     */
    private function describe(array $classes): string
    {
        return match (true) {
            $classes === ['many'] => 'a collection',
            $classes === ['one'] => 'a single value or null',
            $classes === ['scalar'] => 'a scalar',
            default => implode(' or ', $classes),
        };
    }
}
