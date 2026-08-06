<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Resolution;

use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Source\SourceSlice;
use ContextDiscovery\Ports\ClassLocator;
use ContextDiscovery\Ports\MemberSlicer;
use ContextDiscovery\Ports\SourceRepository;

/**
 * Locates the class through the PSR-4 map and slices what the region actually named.
 *
 * **Depth one, structurally.** This reads the located file to slice a member and does nothing
 * else with it: it never reads that file's `use` block, never resolves its references, and hands
 * nothing back to extraction (P3, P4, X2). There is no worklist here that could hold a pending
 * reference, which is why depth two is unreachable rather than merely unconfigured.
 *
 * Two outcomes, per 01-architecture.md §3.3:
 *
 * - the region named a member (`Fqcn::member`) — slice that member, and only it;
 * - the region named the class alone — slice its members, the model or enum *surface* Experiment 1
 *   and Experiment 4 ask for.
 *
 * Anything unresolvable returns nothing: no PSR-4 entry, an unreadable file, or a member the
 * slicer cannot find. The pipeline then flags it as `unresolved-reference`, so a contract that
 * could not be checked is stated rather than dropped (P10).
 */
final class NamedReferenceResolver implements AssertionResolver
{
    public function __construct(
        private readonly ClassLocator $locator,
        private readonly SourceRepository $source,
        private readonly MemberSlicer $slicer,
    ) {
    }

    /**
     * Never. A named reference appears in the diff, so its source should exist: no PSR-4 entry, an
     * unreadable path or a missing member are all **lookup failures**, and each is flagged as
     * `unresolved-reference` (P10, freeze review 05). There is no successful-negative case here —
     * "the class simply has nothing" is not an answer the diff asked for.
     */
    public function lookupRan(Assertion $assertion): bool
    {
        return false;
    }

    /**
     * @return list<SourceSlice>
     */
    public function resolve(Assertion $assertion): array
    {
        if ($assertion->kind !== AssertionKind::NamedReference) {
            return [];
        }

        [$class, $member] = self::split($assertion->subject);

        $path = $this->locator->pathFor($class);

        if ($path === null) {
            return [];
        }

        $text = $this->source->text($path);

        if ($text === null) {
            return [];
        }

        return $member === null
            ? $this->surface($path, $text)
            : $this->member($path, $text, $member);
    }

    /**
     * A subject is `Fqcn` or `Fqcn::member`. A fully-qualified name never contains `::`, so the
     * split is unambiguous. `LeverPolicy` reads the same encoding.
     *
     * @return array{0: string, 1: string|null}
     */
    public static function split(string $subject): array
    {
        $separator = strrpos($subject, '::');

        return $separator === false
            ? [$subject, null]
            : [substr($subject, 0, $separator), substr($subject, $separator + 2)];
    }

    /**
     * @return list<SourceSlice>
     */
    private function member(string $path, string $text, string $member): array
    {
        $slice = $this->slicer->member($text, $member);

        return $slice === null ? [] : [$this->at($path, $slice)];
    }

    /**
     * The model or enum surface: the class's members, each as its own slice with its own
     * provenance. Not the whole file — the namespace, the `use` block and the class declaration
     * stay out (ADR-A005).
     *
     * @return list<SourceSlice>
     */
    private function surface(string $path, string $text): array
    {
        $slices = [];

        foreach ($this->slicer->memberNames($text) as $name) {
            $slice = $this->slicer->member($text, $name);

            if ($slice !== null) {
                $slices[] = $this->at($path, $slice);
            }
        }

        return $slices;
    }

    private function at(string $path, SourceSlice $slice): SourceSlice
    {
        return new SourceSlice($path, $slice->member, $slice->firstLine, $slice->lastLine, $slice->text);
    }
}
