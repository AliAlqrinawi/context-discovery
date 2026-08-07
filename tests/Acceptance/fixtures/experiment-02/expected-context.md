# Experiment 02 · Temporary trace logging

Transcribed from `docs/01-phase0/experiment-02.md` step 4 and `06-acceptance.md` §1.

Role in the set: **the deliberate control** — a precision test. Every real finding in this commit is
visible in the diff itself, so the correct output is an almost-empty bundle. "That is not the engine
failing; it is the engine being right."

## Operator inputs

    repo: <operator-set>
    budget: <operator-set>

## Expectations

**Almost empty. At most nothing.**

There are no `fetch-expected` and no `flag-satisfied` rows, and that absence is the expectation
rather than an omission: step 4 records "Near-zero. At most the `PlaidAccount` model to confirm the
trace helper's columns exist — and even that is low value because the code is disposable."

| mark | assertion_kind | expect | from the research |
|---|---|---|---|
| — | — | — | — |

## Checks

- **Precision: pulling the `PlaidAccount` model "just in case" fails the test.** No
  `named_reference` item may appear. The implementation spec is explicit that a speculative fetch
  here "is a precision failure, not caution".
- No premise fires: the trace-logging diff names no `Cache::lock`, no row lock, removes no trait and
  adds no persistence write.
- The item count is reported for a human to read against "almost empty".
