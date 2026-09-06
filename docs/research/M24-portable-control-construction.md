# M24 — Portable Control Construction

## 1 · Classification

**C — no sufficiently portable criterion was established. Do not run M25 scored.**

The criterion's negative half (**E1**, content survival) transfers cleanly and is mechanically
reproducible. Its positive half (**E2**, behavioural coverage) is **vacuous as pre-registered**: it
matches PHP keywords and common identifiers rather than the behaviour under review. `extends` alone
matches **10 of halaw's 11 test files**. **All six** halaw CONTROLs rest on a generic token; not one
rests on a specific symbol. Without a valid positive test, CONTROL collapses back to *"no recorded
correction"* — which is M23's failure restated, not solved.

Per the stopping rule the criterion was **not patched** after the failure was seen.

## 2 · Objective

> Can we define a repository-independent criterion for selecting control commits that provides
> sufficiently strong evidence that the selected commit is not a defect-fixing change, without
> relying on the repository's commit-message conventions?

## 3 · Pre-registered control criterion

Frozen at sha256 `c5388f099efc55b4` in `control-criterion.md` before any candidate was evaluated,
and applied mechanically by `classify.php`.

**E1 · content survival (negative evidence).** For every line the commit adds to a `.php` file that
is *substantive* — ≥ 15 characters trimmed, not a lone brace, `<?php`, `use`, blank or comment — ask
whether that exact text still exists anywhere at HEAD. All survive → E1 passes; any missing → E1
fails. Matching is on **content, never on messages**.

**E2 · behavioural coverage (positive evidence).** At HEAD, some file under a test root must
reference a symbol the commit added or changed. E1 alone establishes only *"no recorded
correction"*, and absence of a correction is not evidence of correctness; E2 was the requirement
that something positively asserts the behaviour.

Excluded by construction: `fix:`/`bug:` prefixes, branch names, researcher labels, "looks harmless",
bundle emptiness, reviewer agreement.

## 4 · States and evidence thresholds

| State | Threshold |
|---|---|
| **CONTROL** | E1 passes **and** E2 passes |
| **DEFECT** | E1 fails **and** the superseding commit's changed lines fall inside the same member — a content-linked correction |
| **CONTESTED** | anything else: E1 passes but E2 fails; E1 fails but supersession is a move/rename; evidence conflicts |
| **UNUSABLE** | merge, root, no substantive PHP addition, or > 500 changed lines |

Ambiguity resolves to CONTESTED, never CONTROL.

## 5 · Original repository results

| | commits | CONTROL | CONTESTED | UNUSABLE |
|---|---:|---:|---:|---:|
| **abouelsid** | 47 | **17** | 10 | 20 |

Of 27 usable: E1 passes 23 (85.2%), E2 passes 21 (77.8%).

## 6 · halaw results

| | commits | CONTROL | CONTESTED | UNUSABLE |
|---|---:|---:|---:|---:|
| **halaw** | 239 | **6** | 209 | 24 |

Of 215 usable: E1 passes 79 (**36.7%**), E2 passes 60 (**27.9%**), E1 fails 136.

The CONTROL rate is **36% on abouelsid against 2.5% on halaw** — a fourteenfold gap.

## 7 · Negative tests

The three M23 controls later shown to contain real defects:

| Commit | State | E1 | E2 | Result |
|---|---|---|---|---|
| `28f2411` (C3) | **CONTESTED** | fail (9 of 17 lines gone) | fail | **caught** |
| `b5b2859` (C5) | **CONTESTED** | fail (1 of 11 gone) | pass | **caught** |
| `00095a9` (C6) | **CONTESTED** | fail (2 of 88 gone) | fail | **caught** |

**All three are caught. None is classified CONTROL.** The pre-registered negative test passes.

But it passes **entirely through E1** — `b5b2859` passed E2 and was still caught only because its
contradictory rule was later edited. **The negative test therefore validates E1 and says nothing
about E2.** That distinction is what §8 turns on.

## 8 · Transferability

| Component | Depends on message style? | On branch conventions? | On author habits? | Independently verifiable? | Transfers? |
|---|---|---|---|---|---|
| **E1** content survival | no | no | **partly** — churn rate differs by team | yes, byte-exact | **yes** |
| **E2** behavioural coverage | no | no | **yes — decisively** | in principle | **no** |

**E1 transfers.** It ran identically on both repositories and never produced a false CONTROL. Its
pass rate differs (85% vs 37%) because halaw's code churns far more — that is signal about the
repositories, not a defect in the rule.

**E2 does not transfer, and worse, it is not sound anywhere.** It is a substring search. Where it
appears to work it is because the *project's naming style* happens to be distinctive:

| | CONTROLs | E2 matched a **specific** symbol | E2 matched a **generic** token |
|---|---:|---:|---:|
| abouelsid | 17 | 11 | 6 |
| **halaw** | **6** | **0** | **6** |

abouelsid names files `MenuPdfTest`, `FileUploadRollbackTest`, `CacheGroup` — so a substring search
lands on real evidence. halaw's touch generic names, so the same search lands on `extends`, `User`,
`update`, `create`. **The criterion did not escape convention-dependence; it relocated it from
commit messages to identifier naming.**

## 9 · Failure cases

1. **E2 is vacuous.** `extends` is a PHP keyword matching 10 of halaw's 11 test files. Six of
   abouelsid's 17 CONTROLs and **all six** of halaw's rest on such tokens. Every CONTROL this
   criterion produced is therefore suspect, including on the original repository.
2. **A "specific" match is still not coverage.** Even `MenuPdfTest` only proves a test *mentions* the
   symbol — not that it exercises the changed path, nor that it passes. The criterion never runs a
   test; running halaw's suite would need a database and fixtures not present offline.
3. **136 of halaw's 215 usable commits fail E1.** Most are moves and reformats rather than
   corrections, but the criterion as frozen does not separate the two — it defers that to a DEFECT
   test it never reaches. So `DEFECT` was assigned **zero times in either repository**, and the
   four-state scheme collapsed to three in practice.
4. **E1 cannot see an unfixed defect.** halaw's live `sub_total` bug (M23, verified, never
   corrected) survives to HEAD untouched, so E1 passes on its commit. Only E2 could have caught it,
   and E2 cannot.
5. **The negative test is weaker than it looks** — passed by E1 alone, so it does not license E2.

## 10 · Regression · Determinism

- **`src/` unchanged, `bin/` unchanged** — `git status --porcelain src/ bin/` returns nothing.
- No extraction, resolution, assembly or schema change; no ADR touched; no bundle regenerated.
- Full suite **1080 tests, 4842 assertions, 4 failures** — the same four pre-existing.
- Repositories clean: cli 0, abouelsid 0, halaw 1 (its pre-existing `composer.lock`, untouched). No
  worktree left behind in either.
- Classifier determinism: the negative test re-run **3 times, 0 differing outputs**. `classify.php`
  reads only git history and writes only JSON.

## 11 · Limitations

- **The criterion cannot establish that a commit is defect-free**, and this was stated before
  evaluation rather than discovered. History shows that a defect *was found*; it cannot show that
  none exists.
- **No CONTROL produced here is defensible**, because every one depends on E2.
- **DEFECT was never assigned**, so half the state machine is untested.
- One repository pair, one language, one framework. E1's pass-rate gap may not generalise.
- E2 could in principle be made sound — resolve the changed symbol to a fully-qualified name and
  require a test to reference *that*, then require the test to pass. Both halves are out of reach
  here: the first is the resolution work this programme deliberately bounds, the second needs a
  runnable database. **This is stated as a limitation, not adopted as a patch.**

## 12 · Decision for M25

**C — no sufficiently portable criterion was established; do not run M25 scored.**

E1 is worth keeping as a **filter** — it is sound, mechanical, convention-independent, and it caught
all three known-defective M23 controls. But a filter that removes candidates is not a criterion that
qualifies them. What M25 would need is positive evidence that a commit's behaviour is correct, and
this milestone did not produce it.

The honest position is that **"control" may not be constructible from repository history at all**.
A control requires evidence of absence of a defect; history records only defects that were found.
The two available escapes are outside this programme's current means: execute the test suite at each
commit and require the changed path to be covered and green, or have a second competent reviewer
adjudicate each candidate — which is researcher judgment, and the stopping rule excludes it.

Stopping at M24 as instructed.
