<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Assertion;

/**
 * The five kinds of thing a diff claims but cannot prove.
 *
 * One kind per discovery move, so a kind always names its resolver and its priority band with no
 * extra field (freeze review 04). Same-file and named-collaborator are distinct context types in
 * the research (`docs/02-discovery/context-types.md`, types 1 and 2), and the kinds mirror that
 * taxonomy rather than collapsing it.
 *
 * This list is closed. A new kind for a new *move* requires a new experiment, a requirement entry,
 * an ADR and a Wiring change — in that order (ADR-A003). If a commit raises something these five
 * cannot express, the bundle must show the gap rather than absorb it.
 *
 * The backing values are the `assertion_kind` strings in the bundle schema
 * (03-interfaces.md §2), present so precision can be measured per move after the scored run.
 */
enum AssertionKind: string
{
    /** A symbol the region uses that the file's `use` block does not import — an absence. */
    case SameFileSymbolAbsence = 'same_file_symbol_absence';

    /** A member of the changed file itself, called as `$this->method(` — Exp 1's `upsertFromPlaid`. */
    case SameFileReference = 'same_file_reference';

    /** A class or member named by the region and defined in another file. Cross-file only. */
    case NamedReference = 'named_reference';

    /** A member whose arity or parameter shape the diff changed — Exp 4's `reactivate`. */
    case ChangedSignature = 'changed_signature';

    /**
     * A member whose declared signature is untouched but whose body now returns a different
     * *kind* of value — a collection where a single model or null now comes back.
     *
     * A separate kind rather than a widening of `ChangedSignature`, because the evidence differs:
     * that one reads the parameter list, this one reads the return expression against a closed
     * framework table. Both ask the same downstream question, so both resolve through
     * `CallerResolver`; keeping them apart is what lets precision be measured per move (ADR-A023).
     */
    case ChangedReturnContract = 'changed_return_contract';

    /** A premise from the closed catalogue that no file can settle (ADR-A009). */
    case UnverifiablePremise = 'unverifiable_premise';
}
