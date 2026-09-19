# Phase 0 answer keys · kept as a record, retired as a test

These four files are **hand-transcribed answer keys for commits this repository never had.**

Phase 0 analysed four real commits from a private Laravel + Plaid codebase. The research documents
describe them in prose and list, for each, the minimum context a senior reviewer would need. M10
transcribed those lists into `expected-context.md` files and built an acceptance harness,
`ExperimentKeyTest`, to grade the tool against them.

The harness could never run. The commits' diffs were never available to this repository — not
lost, never present — and the harness refused, correctly, to fabricate them: *"Fabricating a diff
would grade the tool against invented ground truth, which is the one thing the answer-key method
exists to prevent."* So it failed four tests, loudly, on every run from M10 through M27.

**ADR-A025 retired it.** A permanently red test is not a gate; it is noise that trains everyone to
read past red. The behaviour those keys describe is asserted by tests that actually run — every
premise, the zero-call-site negative, the changed-signature call sites, the named references — and
the one claim with no named home, experiment 02's *"pulling the model just in case is a precision
failure"*, now has `SelfContainedDiffTest`. The process contract the harness also carried lives in
`ProcessContractTest`.

The keys stay here because they are the record of what Phase 0 asked for. They are documentation.
Nothing reads them.

| Key | The commit it describes | Its headline |
|---|---|---|
| [experiment-01](experiment-01-expected-context.md) | `syncFromResponse` reconciliation | recall — four context-dependent findings, one a missing import |
| [experiment-02](experiment-02-expected-context.md) | trace logging | precision — the correct bundle is almost empty |
| [experiment-03](experiment-03-expected-context.md) | Plaid Item lifecycle | three premises no file can settle; config and schema stay flag-only (X3) |
| [experiment-04](experiment-04-expected-context.md) | `reactivate` | a real signature change, so the caller grep runs |
