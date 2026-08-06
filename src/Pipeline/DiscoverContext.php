<?php

declare(strict_types=1);

namespace ContextDiscovery\Pipeline;

use ContextDiscovery\Assembly\BudgetEnforcer;
use ContextDiscovery\Assembly\BundleAssembler;
use ContextDiscovery\Discovery\Extraction\AssertionExtractor;
use ContextDiscovery\Discovery\Flagging\AssumptionWriter;
use ContextDiscovery\Discovery\Lever\LeverPolicy;
use ContextDiscovery\Discovery\Lever\PremiseCatalogue;
use ContextDiscovery\Discovery\Parsing\UnifiedDiffParser;
use ContextDiscovery\Discovery\Resolution\AssertionResolver;
use ContextDiscovery\Discovery\Resolution\CallerResolver;
use ContextDiscovery\Discovery\Resolution\NamedReferenceResolver;
use ContextDiscovery\Discovery\Resolution\OwnFileResolver;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Assertion\ResolvedAssertion;
use ContextDiscovery\Domain\Bundle\Bundle;
use ContextDiscovery\Domain\Bundle\Lever;
use ContextDiscovery\Ports\ClassLocator;
use ContextDiscovery\Ports\SourceRepository;
use LogicException;

/**
 * The single use case: diff in, context bundle out.
 *
 * The spec's responsibilities run in a fixed order, synchronously, passing values — no
 * dispatcher, no listeners, no queue, no retry loop, no branching on configuration beyond scope
 * and budget (ADR-A002). Given the same diff and repository state it produces the same bundle
 * (P8).
 *
 * Stage 5 never re-enters stage 3. Resolved sources are payload, never new input — that is the
 * depth-one guarantee, structural rather than configured (P3), and it is why no forward crawl can
 * develop here.
 *
 * Diagnostics leave through an injected sink rather than a stream, so the pipeline performs no
 * I/O of its own; the CLI decides that they go to stderr.
 */
final class DiscoverContext
{
    public function __construct(
        private readonly UnifiedDiffParser $parser,
        private readonly SourceRepository $source,
        private readonly AssertionExtractor $extractor,
        private readonly LeverPolicy $leverPolicy,
        private readonly ClassLocator $classLocator,
        private readonly OwnFileResolver $ownFileResolver,
        private readonly NamedReferenceResolver $namedReferenceResolver,
        private readonly CallerResolver $callerResolver,
        private readonly AssumptionWriter $assumptionWriter,
        private readonly BundleAssembler $assembler,
        private readonly BudgetEnforcer $budgetEnforcer,
    ) {
    }

    /**
     * @param callable(string): void $diagnostic Receives one line per unreadable path or
     *                                           unresolved reference. Never merged into the bundle.
     */
    public function run(string $diffText, int $budgetTokens, callable $diagnostic): Bundle
    {
        // 1 · Parse the diff into changed files, regions and member signatures.
        $diff = $this->parser->parse($diffText);

        // 2 · Load the full current text of each changed file. It is an analysis input; only
        //     slices that settle an assertion ever become payload (ADR-A005).
        foreach ($diff->files as $file) {
            if ($this->source->text($file->path) === null) {
                $diagnostic(sprintf('unreadable path: %s (no assertions extracted)', $file->path));
            }
        }

        // 3 · Extract what the diff asserts but cannot prove.
        $assertions = $this->extractor->extract($diff, $this->source);

        $resolved = [];

        foreach ($assertions as $assertion) {
            // 4 · Choose the lever, by cost. The single decision point (P2).
            $lever = $this->leverPolicy->leverFor($assertion, $this->classLocator);

            // 5b · Flag the expensive or unknowable.
            if ($lever === Lever::Flagged) {
                if ($assertion->kind === AssertionKind::NamedReference) {
                    // The PSR-4 map could not place it: a missing entry, or no composer.json at
                    // all. One line per unplaceable reference (01-architecture.md §5).
                    $diagnostic(sprintf(
                        'missing PSR-4 entry: %s named in %s',
                        $assertion->subject,
                        $assertion->originPath,
                    ));
                }

                $resolved[] = ResolvedAssertion::flagged(
                    $assertion,
                    $this->assumptionWriter->statementFor($assertion),
                );

                continue;
            }

            // 5a · Fetch the minimal slice.
            $slices = $this->resolverFor($assertion->kind)->resolve($assertion);

            // 5d · A successful negative: the lookup ran and the answer is "none". Nothing is
            //      unverified, so there is no premise to state and no item to emit. One
            //      diagnostic, so the harness can tell "searched, found none" from "never
            //      searched" (freeze review 05).
            if ($slices === [] && $this->lookupRan($assertion)) {
                $diagnostic($this->negativeFor($assertion));

                continue;
            }

            // 5c · A lookup that could not run is a *failure*, and failure flags (P10). Never a
            //      silent omission.
            if ($slices === []) {
                $diagnostic(sprintf(
                    'unresolved %s: %s in %s',
                    $assertion->kind->value,
                    $assertion->subject,
                    $assertion->originPath,
                ));

                $resolved[] = ResolvedAssertion::flagged(
                    $assertion,
                    $this->assumptionWriter->statementFor($assertion),
                );

                continue;
            }

            // The caller search is the one bounded lookup, and its bound is visible. The resolver
            // asks for one more than it may keep, so an overflow is certain rather than inferred
            // (P10, ADR-A006).
            if (
                $assertion->kind === AssertionKind::ChangedSignature
                && count($slices) > $this->callerResolver->bound()
            ) {
                $slices = array_slice($slices, 0, $this->callerResolver->bound());

                $diagnostic(sprintf(
                    'call sites truncated at %d for %s under %s',
                    $this->callerResolver->bound(),
                    $assertion->subject,
                    $this->callerResolver->scope(),
                ));

                $resolved[] = ResolvedAssertion::flagged(
                    $assertion,
                    $this->assumptionWriter->statementForPremise(PremiseCatalogue::CallSitesTruncated),
                );
            }

            $resolved[] = ResolvedAssertion::fetched($assertion, $slices);
        }

        // 6 · Assemble, attaching reason, lever and provenance to every item.
        $bundle = $this->assembler->assemble($resolved, $budgetTokens);

        // 7 · Enforce the budget, recording every drop.
        return $this->budgetEnforcer->enforce($bundle);
    }

    /**
     * Whether the resolver's lookup could run — asked of the resolver, never guessed here
     * (freeze review 05). Each resolver knows what its own empty result means; the pipeline only
     * routes the answer.
     */
    private function lookupRan(Assertion $assertion): bool
    {
        return match ($assertion->kind) {
            AssertionKind::SameFileSymbolAbsence,
            AssertionKind::SameFileReference => $this->ownFileResolver->lookupRan($assertion),
            AssertionKind::NamedReference => $this->namedReferenceResolver->lookupRan($assertion),
            AssertionKind::ChangedSignature => $this->callerResolver->lookupRan($assertion),
            // A premise is always flagged, so it never reaches a resolver at all.
            AssertionKind::UnverifiablePremise => false,
        };
    }

    /**
     * The one diagnostic class with no corresponding bundle item (AA11).
     */
    private function negativeFor(Assertion $assertion): string
    {
        return $assertion->kind === AssertionKind::ChangedSignature
            ? sprintf(
                'caller search for %s( under %s: 0 call sites',
                $assertion->subject,
                $this->callerResolver->scope(),
            )
            : sprintf(
                'own-file lookup for %s in %s: nothing to slice',
                $assertion->subject,
                $assertion->originPath,
            );
    }

    /**
     * Every kind and its destination, visible in one place. No probing, no fall-through
     * (freeze review O3, 03-interfaces.md §4).
     */
    private function resolverFor(AssertionKind $kind): AssertionResolver
    {
        return match ($kind) {
            AssertionKind::SameFileSymbolAbsence,
            AssertionKind::SameFileReference => $this->ownFileResolver,

            AssertionKind::NamedReference => $this->namedReferenceResolver,

            AssertionKind::ChangedSignature => $this->callerResolver,

            // A premise is stated, never resolved: LeverPolicy flags every one of them.
            AssertionKind::UnverifiablePremise => throw new LogicException(
                'A premise is flagged, never resolved.'
            ),
        };
    }
}
