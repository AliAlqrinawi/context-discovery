# Experiment 04 · Update Mode lifecycle hardening

Transcribed from `docs/01-phase0/experiment-04.md` step 4 and `06-acceptance.md` §1.

Role in the set: a **precision-leaning refactor with one high-value reverse-caller probe**. Most of
its key confirms rather than overturns; what rescues it is the `reactivate` signature change, "a
genuinely severe, genuinely context-only finding that a naked diff cannot reach".

It is also the declared **precision guard for the transaction trigger**: its key asks for no premise,
so if one fires here the trigger is wrong — and that goes back to the research repository rather than
being tuned in the code (ADR-A009, ADR-A003).

## Operator inputs

    repo: <operator-set>
    budget: <operator-set>

## Expectations

| mark | assertion_kind | expect | from the research |
|---|---|---|---|
| fetch-expected | named_reference | createLinkToken | "`PlaidClient::createLinkToken` — one method (the mode switch)" |
| fetch-expected | named_reference | REVOKED | "`App\Enums\PlaidItemStatus` — one small class"; the enum membership behind the new branch |
| fetch-expected | changed_signature | reactivate | "A caller search for `reactivate(` across `app/` — a grep, not a file fetch." A real signature change, so the grep runs here |

## Checks

- The reverse-caller item is present and carries **call-site provenance** — a path and a single line,
  not a member.
- No false extra fetches on the clean parts.
- **No premise is emitted.** This fixture is the precision guard for the transaction trigger.
