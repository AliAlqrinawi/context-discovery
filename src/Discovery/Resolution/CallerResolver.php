<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Resolution;

use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Source\CallSite;
use ContextDiscovery\Domain\Source\SourceSlice;
use ContextDiscovery\Ports\CallSiteSearch;
use ContextDiscovery\Ports\SourceRepository;

/**
 * Call sites of a changed method, found by a bounded grep within one scope.
 *
 * Used for `ChangedSignature` and `ChangedReturnContract` assertions and nothing else. Both ask the
 * one question a bounded grep can answer — *who calls this member?* — one because the parameter list
 * moved, the other because the returned cardinality did. A caller question that is *not* either —
 * Experiment 1's "is this wrapped in a transaction?" — is flagged, never searched: `fetch-vs-flag.md`
 * names it the canonical flag case, and searching it would be exactly the reverse-graph machinery
 * the two-lever model exists to avoid (P2, ADR-A006).
 *
 * Bounded and non-recursive: one search, one scope, one bound, no following of what the call sites
 * themselves call.
 *
 * The search asks for one more than the bound so the caller can tell a truncated result from an
 * exact fit. Never more than the bound reaches the bundle.
 */
final class CallerResolver implements AssertionResolver
{
    public function __construct(
        private readonly CallSiteSearch $search,
        private readonly SourceRepository $source,
        private readonly string $scopePrefix,
        private readonly int $maxCallSites,
    ) {
    }

    /**
     * Whether the search could run at all — the distinction that decides what an empty result
     * means (freeze review 05).
     *
     * A scope holding PHP files was searched, so zero call sites is a **successful negative**: the
     * question was asked and answered "none". A scope holding none could not be searched, so an
     * empty result is a **lookup failure** and is flagged (P10).
     *
     * Experiment 4's minimum context is "a caller search for `reactivate(` across `app/`" — the
     * research treats that search as the answer, not as a partial one. Callers outside the scope
     * are a recorded under-build with a named trigger, not a premise.
     */
    public function lookupRan(Assertion $assertion): bool
    {
        return $this->serves($assertion)
            && $this->source->filesUnder($this->scopePrefix, 'php') !== [];
    }

    /**
     * The hard bound per changed signature. Its *value* is architectural assumption AA1; that
     * there is one is required by P7 and P10.
     */
    public function bound(): int
    {
        return $this->maxCallSites;
    }

    public function scope(): string
    {
        return $this->scopePrefix;
    }

    /**
     * @return list<SourceSlice> Up to `bound() + 1` entries; more than `bound()` means the search
     *                           was truncated, and the caller records that as a flag (P10).
     */
    public function resolve(Assertion $assertion): array
    {
        if (!$this->serves($assertion)) {
            return [];
        }

        return array_map(
            static fn (CallSite $site): SourceSlice => new SourceSlice(
                $site->path,
                null,
                $site->line,
                $site->line,
                $site->lineText,
            ),
            $this->search->callSites($assertion->subject, $this->scopePrefix, $this->maxCallSites + 1),
        );
    }

    /**
     * The two kinds whose question is "who calls this member?".
     *
     * `ChangedSignature` asks it because the parameter list moved; `ChangedReturnContract` because
     * the returned value's shape did. The search is the same either way — one grep, one scope, one
     * bound — so it is served here rather than duplicated.
     */
    private function serves(Assertion $assertion): bool
    {
        return $assertion->kind === AssertionKind::ChangedSignature
            || $assertion->kind === AssertionKind::ChangedReturnContract;
    }
}
