<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Lever;

use ContextDiscovery\Discovery\Resolution\NamedReferenceResolver;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Bundle\Lever;
use ContextDiscovery\Ports\ClassLocator;

/**
 * The single decision point for fetch versus flag (P2).
 *
 * The rule is `fetch-vs-flag.md`'s, and nothing else: is the resolving source named, single and
 * depth-one on disk? Then fetch. Is it expensive (a reverse graph) or unknowable from files
 * (runtime configuration, production data state)? Then flag.
 *
 * There are no heuristics beyond that rule — no confidence, no weighting, no "probably useful".
 * Minimising tokens *is* choosing flag over fetch where fetching is expensive.
 */
final class LeverPolicy
{
    public function leverFor(Assertion $assertion, ClassLocator $locator): Lever
    {
        return match ($assertion->kind) {
            // Own-file: the source is the changed file, already on disk and already read.
            AssertionKind::SameFileSymbolAbsence,
            AssertionKind::SameFileReference => Lever::Fetched,

            // A bounded grep over one scope — the cheap side of reverse-caller. The expensive
            // "is there a transaction somewhere up the stack?" question is a premise, not a search.
            AssertionKind::ChangedSignature => Lever::Fetched,

            // Depth-one and named, but only if the PSR-4 map can place it. If it cannot, the
            // contract is unverified and that is stated rather than hunted (P10).
            // The subject is `Fqcn` or `Fqcn::member`; only the class part is a locatable name.
            AssertionKind::NamedReference => $locator->pathFor(
                NamedReferenceResolver::split($assertion->subject)[0]
            ) === null ? Lever::Flagged : Lever::Fetched,

            // No file settles a runtime or data-state fact. Flag only.
            AssertionKind::UnverifiablePremise => Lever::Flagged,
        };
    }
}
