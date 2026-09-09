<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Bundle;

use ContextDiscovery\Domain\Assertion\AssertionKind;
use InvalidArgumentException;

/**
 * One thing the diff asserts but cannot prove, as it appears in the bundle.
 *
 * **This is where P5 now lives.** Until v2 the reason sat on every item, so a member resolved into
 * twenty call sites repeated one sentence twenty times and the invariant was enforced twenty times
 * over. The reason belongs to the *claim*, not to each piece of evidence for it, so the guard moves
 * here and the items point back by `id` (ADR-A024).
 *
 * `subject` is the extractor's own structured datum — the member, class, symbol or premise the
 * assertion is about — never a substring recovered from the prose. Its meaning is fixed per kind:
 * a member name for the signature and return-contract kinds, a fully-qualified class or member for
 * a named reference, a symbol for an absence, a catalogue identifier for a premise.
 *
 * Origin is a path and a line span and nothing more: an assertion has no origin *member*, so none
 * is claimed. Scalars only — a Bundle type may not reference a Diff type
 * (02-project-structure.md §3).
 */
final class BundleAssertion
{
    /**
     * @param string $id     Derived from the assertion's identity — kind, subject, origin path and
     *                       first line — so it is stable across runs (P8) and carries no counter.
     * @param string $reason Human-readable statement of what is assumed. Required: an assertion
     *                       without one is waste or an untraceable guess, and both are defects
     *                       rather than warnings (P5, R4).
     *
     * @throws InvalidArgumentException if the id or the reason is empty.
     */
    public function __construct(
        public readonly string $id,
        public readonly AssertionKind $kind,
        public readonly string $subject,
        public readonly string $reason,
        public readonly string $originPath,
        public readonly ?int $originFirstLine = null,
        public readonly ?int $originLastLine = null,
    ) {
        if (trim($id) === '') {
            throw new InvalidArgumentException('A bundle assertion requires an id: items reference it.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException(
                'A bundle assertion requires a non-empty reason: the reference or assumption it '
                . 'states (P5). Without one it does not enter the bundle.'
            );
        }
    }
}
