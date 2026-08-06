<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Resolution;

use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Source\SourceSlice;
use ContextDiscovery\Ports\MemberSlicer;
use ContextDiscovery\Ports\SourceRepository;

/**
 * Fetches the changed file's own `use` block, the member enclosing the region, and named sibling
 * members — and nothing else from that file (ADR-A005).
 *
 * The full text is an analysis input, not payload. Reading the changed file is free; *including*
 * any part of it must be justified by an assertion, which is what keeps Experiment 2's bundle
 * almost empty.
 *
 * An absence resolves to two slices: the `use` block, because that is the structure that makes
 * the absence visible, and the enclosing member, because that is where the symbol is used. Both
 * are what Experiment 1's minimum-context list asks for — "the `use` block plus the full
 * `syncFromResponse` body" — and both are tagged `same_file_symbol_absence` by the acceptance
 * expectations (06-acceptance.md §1).
 *
 * Anything that cannot be sliced returns nothing, and the pipeline flags it (P10).
 */
final class OwnFileResolver implements AssertionResolver
{
    public function __construct(
        private readonly SourceRepository $source,
        private readonly MemberSlicer $slicer,
    ) {
    }

    /**
     * Whether the lookup could run at all — the distinction that decides what an empty result
     * means (freeze review 05).
     *
     * The changed file is the one source always in hand: the extractor skips a file whose text
     * cannot be read, so every own-file assertion that exists already had its source read. Any
     * empty result is therefore a **successful negative** — a file with no `use` block and no
     * enclosing member has genuinely nothing to show, which freeze review 05 names as the example.
     *
     * This is why the catalogue has no own-file failure premise: only two lookups can fail, the
     * named-reference locate and the caller-scope scan (freeze review 06).
     */
    public function lookupRan(Assertion $assertion): bool
    {
        return $this->source->text($assertion->originPath) !== null;
    }

    /**
     * @return list<SourceSlice>
     */
    public function resolve(Assertion $assertion): array
    {
        $text = $this->source->text($assertion->originPath);

        if ($text === null) {
            return [];
        }

        return match ($assertion->kind) {
            AssertionKind::SameFileSymbolAbsence => $this->useBlockAndEnclosingMember($assertion, $text),
            AssertionKind::SameFileReference => $this->sibling($assertion, $text),
            default => [],
        };
    }

    /**
     * @return list<SourceSlice>
     */
    private function useBlockAndEnclosingMember(Assertion $assertion, string $text): array
    {
        $slices = [];

        $useBlock = $this->slicer->useBlock($text);

        if ($useBlock !== null) {
            $slices[] = $this->at($assertion->originPath, $useBlock);
        }

        $member = $this->enclosingMember($assertion, $text);

        if ($member !== null) {
            $slice = $this->slicer->member($text, $member);

            if ($slice !== null) {
                $slices[] = $this->at($assertion->originPath, $slice);
            }
        }

        return $slices;
    }

    /**
     * The member enclosing the changed *region*, not merely its first line.
     *
     * A hunk carries leading context, so its first line is routinely outside any member — the
     * class's opening brace, or a blank line above a method. Scanning the span and taking the
     * first member found is what "the member enclosing each changed region" (ADR-A005) means for
     * a real diff.
     */
    private function enclosingMember(Assertion $assertion, string $text): ?string
    {
        $region = $assertion->originRegion;

        for ($line = $region->firstLine; $line <= $region->lastLine; $line++) {
            $member = $this->slicer->enclosingMemberName($text, $line);

            if ($member !== null) {
                return $member;
            }
        }

        return null;
    }

    /**
     * @return list<SourceSlice>
     */
    private function sibling(Assertion $assertion, string $text): array
    {
        $slice = $this->slicer->member($text, $assertion->subject);

        return $slice === null ? [] : [$this->at($assertion->originPath, $slice)];
    }

    /**
     * The slicer sees text, never a file, so it leaves the path empty. The resolver knows the path
     * and puts it on.
     */
    private function at(string $path, SourceSlice $slice): SourceSlice
    {
        return new SourceSlice($path, $slice->member, $slice->firstLine, $slice->lastLine, $slice->text);
    }
}
