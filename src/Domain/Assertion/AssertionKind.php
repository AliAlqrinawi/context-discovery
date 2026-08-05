<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Assertion;

/**
 * The four kinds of thing a diff claims but cannot prove.
 *
 * This list is closed. A fifth kind requires a new experiment, a requirement entry, an ADR and a
 * Wiring change — in that order (ADR-A003). If a commit raises something these four cannot
 * express, the bundle must show the gap rather than absorb it.
 *
 * The backing values are the `assertion_kind` strings in the bundle schema
 * (03-interfaces.md §2), present so precision can be measured per move after the scored run.
 */
enum AssertionKind: string
{
    case SameFileSymbolAbsence = 'same_file_symbol_absence';
    case NamedReference = 'named_reference';
    case ChangedSignature = 'changed_signature';
    case UnverifiablePremise = 'unverifiable_premise';
}
