<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Flagging;

use ContextDiscovery\Discovery\Lever\PremiseCatalogue;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use InvalidArgumentException;
use LogicException;

/**
 * One fixed sentence per catalogue premise — text templates and nothing else (ADR-A009).
 *
 * A flag states an unverified premise; it does not assess risk or severity. The engine judges
 * nothing (P6, X4), so there is no composed, inferred or generated assumption text anywhere.
 */
final class AssumptionWriter
{
    /**
     * Verbatim from ADR-A009. Changing one of these changes what a scored run is comparing.
     */
    private const STATEMENTS = [
        PremiseCatalogue::SurroundingTransaction->value
            => 'ASSUMPTION: this code assumes a surrounding transaction; caller not checked',
        PremiseCatalogue::AtomicLockStore->value
            => 'ASSUMPTION: lock correctness depends on the deployed cache store being atomic',
        PremiseCatalogue::DataStateAfterBehaviourChange->value
            => 'ASSUMPTION: behaviour depends on whether pre-existing rows are affected in production; '
                . 'no backfill migration present',
        PremiseCatalogue::SchemaIndexSupport->value
            => 'ASSUMPTION: locked/filtered lookup assumes supporting schema indexes; migration not verified',
        PremiseCatalogue::UnresolvedReference->value
            => 'ASSUMPTION: named reference could not be resolved on disk; contract unverified',
        PremiseCatalogue::CallerSearchFailed->value
            => 'ASSUMPTION: callers of this signature could not be searched; scope unreadable',
        PremiseCatalogue::CallSitesTruncated->value
            => 'ASSUMPTION: additional call sites exist beyond the search bound; not all verified',
    ];

    public function statementFor(Assertion $assertion): string
    {
        return $this->statementForPremise($this->premiseFor($assertion));
    }

    /**
     * For the premise the caller has already identified.
     *
     * The truncation case is the one a resolver's *result* cannot express: a caller search that
     * hit `--max-call-sites` still returns slices, so the pipeline names the premise directly
     * rather than having it inferred from the assertion.
     */
    public function statementForPremise(PremiseCatalogue $premise): string
    {
        return self::STATEMENTS[$premise->value];
    }

    /**
     * A premise assertion names its own premise. Any other kind reaches this path because a lookup
     * **failed**, and each failing lookup states its own premise — never a shared one
     * (freeze review 06). "Named reference could not be resolved" is simply false of an unreadable
     * caller scope, and a fixed statement exists so a flag is exactly true.
     *
     * A caller search that merely hit its bound is not a failure and does not come through here;
     * the pipeline names `call-sites-truncated` explicitly, because a truncated search still
     * returns slices and so cannot be inferred from the assertion (freeze review 05).
     *
     * The own-file kinds cannot reach this path: the extractor skips a changed file whose text
     * cannot be read, so every own-file assertion already has its source in hand and any empty
     * result is a settled answer, not a failure.
     */
    private function premiseFor(Assertion $assertion): PremiseCatalogue
    {
        if ($assertion->kind === AssertionKind::NamedReference) {
            return PremiseCatalogue::UnresolvedReference;
        }

        if ($assertion->kind === AssertionKind::ChangedSignature) {
            return PremiseCatalogue::CallerSearchFailed;
        }

        if ($assertion->kind !== AssertionKind::UnverifiablePremise) {
            throw new LogicException(sprintf(
                'No failure premise exists for %s: the changed file is always in hand, so an empty '
                . 'own-file result is a settled answer rather than a failure (freeze review 06).',
                $assertion->kind->value,
            ));
        }

        $premise = PremiseCatalogue::tryFrom($assertion->subject);

        if ($premise === null) {
            throw new InvalidArgumentException(sprintf(
                'No such premise: "%s". The catalogue is closed — adding one requires an experiment, '
                . 'a catalogue entry and an ADR-A009 edit (ADR-A003).',
                $assertion->subject,
            ));
        }

        return $premise;
    }
}
