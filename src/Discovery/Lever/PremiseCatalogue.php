<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Lever;

/**
 * The closed list of premises a file cannot settle (ADR-A009).
 *
 * Modelled as an enum so an unknown premise is not merely rejected but unconstructable: there is
 * no free-text and no model-generated assumption path, and the flag path is where judgement (X4)
 * and an LLM (X5) would otherwise creep back in.
 *
 * Adding a premise requires an experiment, a catalogue entry, and an edit to ADR-A009 — in that
 * order. If Experiment 5 raises a premise this list lacks, the bundle shows the gap rather than
 * papering over it.
 *
 * The first four are earned by findings; the last two follow from P10 and are recorded as
 * architectural assumptions AA5.
 */
enum PremiseCatalogue: string
{
    /** Exp 1 — reconciliation is safe only inside a transaction, and the transaction is the caller's. */
    case SurroundingTransaction = 'surrounding-transaction';

    /** Exp 3 — a cache lock is a cross-process mutex only if the deployed store is atomic. */
    case AtomicLockStore = 'atomic-lock-store';

    /** Exp 3 — dropping SoftDeletes makes pre-existing trashed rows visible again. */
    case DataStateAfterBehaviourChange = 'data-state-after-behaviour-change';

    /** Exp 3 — a locked, filtered lookup is only as good as the index behind it. */
    case SchemaIndexSupport = 'schema-index-support';

    /** P10 — a reference that could not be resolved is stated, never dropped. */
    case UnresolvedReference = 'unresolved-reference';

    /** P10, ADR-A006 — the caller search is bounded, and the bound is visible. */
    case CallSitesTruncated = 'call-sites-truncated';
}
