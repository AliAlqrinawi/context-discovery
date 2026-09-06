# Experiment 18 · why a bundle is empty — taxonomy

**Pre-registered.** Written and committed before the corpus was run and before any output was
examined. Categories are defined by the **pipeline decision** that produced the silence, not by how
the commit looks. A case that does not fit gets a new category rather than being forced into one.

The distinction the whole milestone turns on:

> **the tool decided no context was needed** (E1, E2, E3, E4 — a decision was reached)
> versus
> **the tool never had a signal to decide about** (E0 — nothing was recognised at all)

Those are different outcomes and are never aggregated into a single "empty" number.

## Categories

| | Name | Definition | Observable evidence |
|---|---|---|---|
| **E0** | **no assertion produced** | No extractor recognised anything in the changed lines. The tool never had a question to answer. | Zero assertions extracted; typically **zero diagnostics** too |
| **E1** | recognised, settled as needing nothing | References were recognised and resolved to a *successful negative* — the contract was found and cited, so ADR-A009 forbids stating a premise. Framework-known (ADR-A011) and declared dependency members (ADR-A013). | stderr `framework reference: … declared at …` or `dependency member: …` |
| **E2** | recognised, excluded by ownership | A dependency/vendor class was placed but its source is deliberately not fetched (ADR-A012). | stderr `dependency class: … surface not fetched` |
| **E3** | created-file suppression | The file is created by the diff, so own-file context is already in front of the reviewer (ADR-A018). | stderr `new file: … own-file context is in the diff, not fetched` |
| **E4** | visibility suppression | A slice was resolved but the diff already shows that exact span in full (ADR-A019). | stderr `already in the diff: … not fetched again` |
| **E5** | budgeted away | Assertions produced items, and every item was dropped by `BudgetEnforcer`. | `dropped` non-empty and `items` empty |
| **E6** | other, explicitly observed | Anything genuinely distinct. Must be described, not merely labelled. | stated per case |

A commit may exhibit several categories at once — E1 and E3 together, say. Each empty commit is
therefore given a **set** of observed categories and one **primary** category, where primary is the
first of E5, E4, E3, E2, E1, E0 that applies, so that a commit which produced *no signal at all* is
only ever called E0 when nothing else happened.

## Why E0 is scored as the interesting one

E1–E4 are decisions with an ADR behind them, each already tested by an earlier milestone: a bundle
that is empty for those reasons is the tool working. **E0 is the absence of a question.** It is the
only category where widening an extraction form could change the outcome — and M18 must not act on
that, only measure it.

## What is NOT a category

"Small commit", "test-only commit", "config commit" and "no defect present" are **change shapes**,
not pipeline decisions. They belong to §8's descriptive analysis and must never be used to explain a
silence.
