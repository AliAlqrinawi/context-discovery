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

                continue;
            }

            // A created file yields no own-file context: all of it is in the diff already
            // (ADR-A005, ADR-A018). Said once per file so the omission is visible, never silent
            // (P10, AA11).
            if ($file->isNew && $file->isPhp()) {
                $diagnostic(sprintf(
                    'new file: %s — own-file context is in the diff, not fetched',
                    $file->path,
                ));
            }
        }

        // 3 · Extract what the diff asserts but cannot prove.
        $assertions = $this->extractor->extract($diff, $this->source);

        $resolved = [];

        // ADR-A020 bookkeeping. `$bareClasses` are the classes some assertion already names on
        // its own, and those take the surface path in 5a; the member fallback stands down for them
        // rather than fetching the same members twice. Read from the whole assertion list before
        // the loop, so the answer does not depend on which reference the parser happened to reach
        // first (P8). `$surfaced` then holds one entry per class the fallback has served, so three
        // unresolved members on one model produce one surface.
        $bareClasses = [];

        foreach ($assertions as $assertion) {
            if ($assertion->kind === AssertionKind::NamedReference
                && NamedReferenceResolver::split($assertion->subject)[1] === null
            ) {
                $bareClasses[$assertion->subject] = true;
            }
        }

        $surfaced = [];

        foreach ($assertions as $assertion) {
            // 4 · Choose the lever, by cost. The single decision point (P2).
            $lever = $this->leverPolicy->leverFor($assertion, $this->classLocator);

            // 5b · Flag the expensive or unknowable.
            if ($lever === Lever::Flagged) {
                if ($assertion->kind === AssertionKind::NamedReference) {
                    // The map could not place it, for one of two reasons, and they are not the same
                    // thing to a reader: either the project declares no prefix that covers the name,
                    // or it declares one and there is no file at the end of it. Saying the first
                    // when the truth is the second states the opposite of what happened (ADR-A017).
                    // One line per unplaceable reference (01-architecture.md §5).
                    $diagnostic(sprintf(
                        '%s: %s named in %s',
                        $this->classLocator->hasMappingFor(
                            NamedReferenceResolver::split($assertion->subject)[0]
                        ) ? 'class file not found' : 'missing PSR-4 entry',
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

                // 5c-i · The member could not be accounted for, and the class is the project's
                //        own. Then the class's *surface* is what the region can be read against —
                //        ADR-A020, M13's boundary B4, the gap G1 that kept Experiment 1's move
                //        from ever firing on real Laravel code.
                //
                //        The flag above **stays**. `Package::activatte` — a typo — satisfies the
                //        same three conditions as `Package::create`, and ADR-A016 established that
                //        no available fact separates them; replacing the flag would report the
                //        typo as satisfied context, which is M0's risk R1 and the silence P10
                //        forbids. One assertion, two outcomes — the shape call-site truncation
                //        below has used since freeze review 06.
                $surface = $this->namedReferenceResolver->unresolvedMemberSurface($assertion);
                $class = NamedReferenceResolver::split($assertion->subject)[0];

                if ($surface !== [] && !isset($surfaced[$class]) && !isset($bareClasses[$class])) {
                    $surfaced[$class] = true;

                    // Same visibility rule as 5e: a member the diff already shows in full is not
                    // context (ADR-A019). No diagnostic — the flag has already spoken for this
                    // reference, and a second line would say nothing new.
                    $surface = array_values(array_filter(
                        $surface,
                        static fn ($slice): bool => !$diff->showsEntirely(
                            $slice->path,
                            $slice->firstLine,
                            $slice->lastLine,
                        ),
                    ));

                    if ($surface !== []) {
                        $resolved[] = ResolvedAssertion::fetched($assertion, $surface);
                    }
                }

                continue;
            }

            // 5e · A cross-file slice the diff already shows in full is not context: the reviewer
            //      is reading it. ADR-A005 — "the reviewer already receives the diff … duplicating
            //      it would double-count tokens". Judged per slice and only on the diff's own
            //      evidence: created file, or a region that contains the span entirely (ADR-A019).
            //
            //      Own-file slices are deliberately out of scope. Their whole point is material the
            //      hunk does not show, and for a modified file the enclosing member often sits
            //      inside its own region — suppressing that would delete Experiment 1's finding.
            if ($assertion->kind === AssertionKind::NamedReference && $slices !== []) {
                $needed = array_values(array_filter(
                    $slices,
                    static fn ($slice): bool => !$diff->showsEntirely(
                        $slice->path,
                        $slice->firstLine,
                        $slice->lastLine,
                    ),
                ));

                if ($needed === []) {
                    // Settled, not failed: the contract was found and the reader already holds it.
                    // Same shape as any other successful negative — no item, one line (freeze 05).
                    $diagnostic(sprintf(
                        'already in the diff: %s declared in %s; not fetched again',
                        $assertion->subject,
                        $slices[0]->path,
                    ));

                    continue;
                }

                $slices = $needed;
            }

            // The caller search is the one bounded lookup, and its bound is visible. The resolver
            // asks for one more than it may keep, so an overflow is certain rather than inferred
            // (P10, ADR-A006).
            if (
                ($assertion->kind === AssertionKind::ChangedSignature
                    || $assertion->kind === AssertionKind::ChangedReturnContract)
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
            AssertionKind::ChangedSignature,
            AssertionKind::ChangedReturnContract => $this->callerResolver->lookupRan($assertion),
            // A premise is always flagged, so it never reaches a resolver at all.
            AssertionKind::UnverifiablePremise => false,
        };
    }

    /**
     * The one diagnostic class with no corresponding bundle item (AA11).
     */
    private function negativeFor(Assertion $assertion): string
    {
        return match ($assertion->kind) {
            AssertionKind::ChangedSignature => sprintf(
                'caller search for %s( under %s: 0 call sites',
                $assertion->subject,
                $this->callerResolver->scope(),
            ),

            // Two settled answers, each with its own true sentence. Sharing one would restore the
            // free-text problem that fixed statements exist to prevent (freeze review 06).
            AssertionKind::NamedReference => $this->namedReferenceSettlement($assertion),

            AssertionKind::SameFileSymbolAbsence,
            AssertionKind::SameFileReference => sprintf(
                'own-file lookup for %s in %s: nothing to slice',
                $assertion->subject,
                $assertion->originPath,
            ),

            // Always flagged, so it never reaches a resolver and never a negative.
            AssertionKind::UnverifiablePremise => throw new LogicException(
                'A premise is flagged, never resolved.'
            ),
        };
    }

    /**
     * What settled a named reference, when the answer was "nothing to fetch".
     *
     * - the **framework declares the member** — cited to the tag that says so (ADR-A011);
     * - the member is **declared by an installed dependency**, cited to its line (ADR-A013);
     * - the class is an **installed dependency's**, so the surface move does not apply and its
     *   source is not this project's to dump (ADR-A012).
     */
    private function namedReferenceSettlement(Assertion $assertion): string
    {
        // The framework's own declaration first: its citation names the tag that documents the
        // member, which is more than the path alone can say (ADR-A011). It is also the only rule
        // that reaches a *project* facade, where ownership says nothing.
        $declaration = $this->namedReferenceResolver->frameworkDeclarationFor($assertion);

        if ($declaration !== null) {
            return sprintf('framework reference: %s declared at %s', $assertion->subject, $declaration);
        }

        $memberDeclaration = $this->namedReferenceResolver->dependencyMemberDeclarationFor($assertion);

        if ($memberDeclaration !== null) {
            return sprintf(
                'dependency member: %s declared at %s; source not fetched',
                $assertion->subject,
                $memberDeclaration,
            );
        }

        return sprintf(
            'dependency class: %s provided by %s; surface not fetched',
            $assertion->subject,
            $this->namedReferenceResolver->dependencyPathFor($assertion) ?? 'an installed package',
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

            AssertionKind::ChangedSignature,
            AssertionKind::ChangedReturnContract => $this->callerResolver,

            // A premise is stated, never resolved: LeverPolicy flags every one of them.
            AssertionKind::UnverifiablePremise => throw new LogicException(
                'A premise is flagged, never resolved.'
            ),
        };
    }
}
