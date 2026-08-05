<?php

declare(strict_types=1);

namespace ContextDiscovery\Pipeline;

use ContextDiscovery\Assembly\BudgetEnforcer;
use ContextDiscovery\Assembly\BundleAssembler;
use ContextDiscovery\Discovery\Extraction\AssertionExtractor;
use ContextDiscovery\Discovery\Flagging\AssumptionWriter;
use ContextDiscovery\Discovery\Lever\LeverPolicy;
use ContextDiscovery\Discovery\Parsing\UnifiedDiffParser;
use ContextDiscovery\Discovery\Resolution\AssertionResolver;
use ContextDiscovery\Discovery\Resolution\OwnFileResolver;
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
                $resolved[] = ResolvedAssertion::flagged(
                    $assertion,
                    $this->assumptionWriter->statementFor($assertion),
                );

                continue;
            }

            // 5a · Fetch the minimal slice.
            $slices = $this->resolverFor($assertion->kind)->resolve($assertion);

            // 5c · Resolution failure falls through to a flag. Never a silent omission (P10).
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

            $resolved[] = ResolvedAssertion::fetched($assertion, $slices);
        }

        // 6 · Assemble, attaching reason, lever and provenance to every item.
        $bundle = $this->assembler->assemble($resolved, $budgetTokens);

        // 7 · Enforce the budget, recording every drop.
        return $this->budgetEnforcer->enforce($bundle);
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

            // Unreachable until their extractors are wired: nothing emits these kinds yet, so the
            // pipeline cannot reach an arm it has no resolver for. Each becomes its resolver when
            // its milestone lands, and until then it fails loudly rather than pretending.
            AssertionKind::NamedReference => throw new LogicException(
                'NamedReference has no resolver wired yet (NamedReferenceResolver, milestone M6).'
            ),
            AssertionKind::ChangedSignature => throw new LogicException(
                'ChangedSignature has no resolver wired yet (CallerResolver, milestone M7).'
            ),

            // A premise is stated, never resolved: LeverPolicy flags every one of them.
            AssertionKind::UnverifiablePremise => throw new LogicException(
                'A premise is flagged, never resolved.'
            ),
        };
    }
}
