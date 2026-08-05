<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Flagging;

use ContextDiscovery\Discovery\Lever\PremiseCatalogue;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use InvalidArgumentException;

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
        PremiseCatalogue::CallSitesTruncated->value
            => 'ASSUMPTION: additional call sites exist beyond the search bound; not all verified',
    ];

    public function statementFor(Assertion $assertion): string
    {
        return self::STATEMENTS[$this->premiseFor($assertion)->value];
    }

    /**
     * A premise assertion names its own premise. Any other kind reaches the flag path because
     * resolution failed, which is the P10 case: an unresolved reference, or a caller search that
     * hit its bound.
     */
    private function premiseFor(Assertion $assertion): PremiseCatalogue
    {
        if ($assertion->kind !== AssertionKind::UnverifiablePremise) {
            return $assertion->kind === AssertionKind::ChangedSignature
                ? PremiseCatalogue::CallSitesTruncated
                : PremiseCatalogue::UnresolvedReference;
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
