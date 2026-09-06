# M22 · the false-positive axis

> **Classification: A — a meaningful, sufficiently grounded measurement, and the first clean one the
> series has produced.** On 11 control commits with both arms complete:
>
> - **false positives: 0/11 in both conditions.** The bundle did not make the reviewer invent a defect.
> - **correct control decisions: 1/11 → 6/11**, with **zero** regressions.
> - **abstentions: 9/11 → 4/11.**
> - Two of the five improvements rest on evidence **absent from the diff**.
>
> And a result that cuts the other way: **M21's 83% false-positive rate did not replicate** — it came
> back as 0% here. No production file changed.

## 1 · Objective

M21 found detection at 0/9 with no headroom, but a defect *reported* on 5 of 6 controls. That was the
one axis that moved and the one place a bundle could plausibly **hurt**. M22 measures it.

## 2 · Corpus

**11 control commits**, keyed NO, criteria fixed first: **CT1** no later commit fixes the behaviour ·
**CT2** not established as defective by an earlier milestone · **CT3** produces a **non-empty** bundle
under the corrected harness · **CT4** 8–1000 changed lines with at least one PHP file.

Excluded by CT3 (empty bundles): `2c2a7b4`, `1fa1f66`, `3ef4d1a`. Excluded by **CT2**: `fdf15e5` —
M21 proved that "control" contains a real defect, so keying it NO again would have been dishonest.

Bundles span **1 item / 25 tokens** to **76 / 2423**; mean 24 items / 904 tokens, median 16 / 504.
All generated with M20's corrected same-commit-tree harness (ADR-A022).

## 3 · Deviations, recorded

1. **The first attempt was truncated.** Seven DIFF_PLUS_BUNDLE agents terminated with HTTP 429 (a
   session rate limit). They were **re-run unchanged after the reset**, with fresh agents and the
   identical committed packets. All 22 cells are now complete. Nothing was substituted, inferred,
   re-run on a different model, or filled from M19/M20 answers on overlapping commits.
2. **M21's answers were not reused** for overlapping commits, though the protocol and diffs are
   identical, because that would confound *milestone* with *condition*. Both arms were run fresh —
   which is what made §6's contradiction visible.
3. **Both arms launched in parallel.** Different fresh agents per cell, so no contamination; it is
   why the rate limit hit the B arm specifically.

## 4 · Results

| Measure (n = 11) | DIFF_ONLY | DIFF_PLUS_BUNDLE | Δ |
|---|---:|---:|---:|
| correct control decisions | **1/11 · 9%** | **6/11 · 55%** | **+5** |
| defect reported (YES) | 1/11 | 1/11 | **0** |
| **verified false positives** | **0/11** | **0/11** | **0** |
| confident false positives | **0** | **0** | 0 |
| abstentions | 9/11 · 82% | 4/11 · 36% | **−5** |
| evidence-supported | 11/11 | 11/11 | 0 |
| **regressions** | — | **0** | — |

**The answer to the milestone's question is no: the bundle does not increase false positives.** Both
arms reported exactly one defect, on K11, and it is verified real — so the spurious rate is **0/11**
either way. Confident false positives were zero on both sides.

The secondary result is that the bundle **halved abstention** and turned five of those into correct
answers, breaking nothing.

## 5 · Where the evidence came from

All 22 Q5 quotations verified present in their own material. **Bundle-only**, absent from the diff:

- **K03-B** — `'password' => 'hashed',` from the `User::casts` slice
- **K09-B** — `public readonly UploadedFile $image,`
- **K07-B** — a flag statement (on a task that did *not* change outcome)

So of five improvements, **two rest on evidence the diff could not have supplied**. The other three
(K02, K06, K08) flipped from abstention to a correct NO while quoting the diff — the reviewer became
decisive without citing new information, which M21 warned is not the same as understanding.

## 6 · M21 did not replicate — and this is the more important finding

| | controls | defect reported | verified spurious |
|---|---:|---:|---:|
| **M21** | 6 | **5 · 83%** | 4 |
| **M22 (DIFF_ONLY)** | 11 | **1 · 9%** | **0** |

Same protocol, same reviewer model, same repository, same author — **different control commits**.

**M21's 5/6 was not a baseline.** The false-positive rate swung from 83% to 0% between two control
sets drawn from one repository. Whatever that number measures, it is dominated by *which commits are
chosen*, not by the condition.

That matters for reading §4 honestly: with DIFF_ONLY at **0** false positives on this set, there was
**no headroom for the bundle to make things worse**. The "bundle does not increase false positives"
result is real but weak — it was measured against a floor of zero, and M21 shows another control set
could have produced a very different denominator.

## 7 · K03 — a third replication

DIFF_ONLY abstained; DIFF_PLUS_BUNDLE answered NO quoting `'password' => 'hashed',` — verified absent
from the diff, present in the bundle's `User::casts` slice.

| | milestone | harness | bundle | result |
|---|---|---|---|---|
| 1 | M19 K3 | at HEAD (defective) | 7 items / 299 tok | abstain → NO, same quote |
| 2 | M20 C1 | corrected | 16 / 479 | abstain → NO, same quote |
| 3 | **M22 K03** | corrected | 16 / 479 | abstain → NO, same quote |

Three independent reviewers, three milestones, one commit, the same transition and the same
bundle-sourced quotation. **The most reproducible result the scored series has produced** — and it is
one commit, on a task with no defect in it. **K09 is now a second instance of the same shape**, which
is the first time this effect has appeared on a commit other than `4411454`.

## 8 · Contested row

**K11 · `ebbd1d6` — keyed NO, and the key is wrong.** *Both* reviewers, independently, reported that
`GetEffectiveSeoAction::merge`'s per-page inheritance of `robots_noindex` is dead code. Verified: the
migration declares `$table->boolean('robots_noindex')->default(false)` — NOT NULL with a default — so
`$override?->robots_noindex` is never null once a page row exists, and `SeoSettingSeeder` creates
one. The global setting can never affect any page, while every other (nullable) field inherits
correctly via `firstNonEmpty()`.

**The key was not modified.** K11 is marked CONTESTED. The bundle carried **nothing** about
`robots_noindex` — zero mentions — so it neither helped nor hindered; the diff alone was sufficient,
and both arms used it.

That is the **sixth** real defect blind reviewers have surfaced that a hand-written key did not
contain, across five scored milestones. No other pattern in this series is close to as reliable.

## 9 · Cost

Mean **904 tokens / 24 items**; median **504 / 16**; range 25 → 2423.

The five improvements cost 504, 479, 185, 888 and 2115 tokens. The two with bundle-only evidence cost
**479** (K03) and **2115** (K09). The most expensive bundle in the corpus (K10, 2423 tokens) changed
nothing — both arms were already correct. No cost-benefit ratio is claimed from eleven tasks.

## 10 · Regression

`git status src/ bin/` empty — **no production file changed.** Full suite **1080 tests, 4842
assertions, 4 failures** (the same four pre-existing). M1 baseline **30/150 green**. All milestone
suites including M20's harness test **275/1298 green**. No test weakened. Contract locks:
**5 ports · 5 assertion kinds · 7 premises · 2 levers · `bundle_version` 1**.

## 11 · Determinism

All 11 bundles regenerated **3 times** through the corrected harness across JSON, Markdown and
stderr: **0 differences of 99 comparisons**. **Zero vendor items** in all 11. The corpus repository
was left clean with no worktree behind.

## 12 · Limitations

- **n = 11, one reviewer per cell.** No statistical test computed; none available. No variance
  estimate — K02's improvement might not survive a re-run.
- **The headline was measured against a floor of zero.** DIFF_ONLY produced no false positives here,
  so "the bundle does not increase them" is a weaker statement than it sounds.
- **Keyed "no defect" is absence of a recorded fix plus author inspection**, and K11 shows again that
  this is not proof. A "control commit" in a real repository is one whose defects nobody has found.
- **Three of five improvements cite only diff evidence** — decisiveness, not demonstrable
  understanding.
- LLM proxy; isolation by instruction, not sandboxing; one repository.

## 13 · Remaining gaps

1. **The false-positive axis is unstable** — 83% vs 0% across two control sets. Measuring it usefully
   needs far more controls than any milestone here has used.
2. **Six key misses in five milestones.** Still the most reliable finding of the series, and still a
   finding about the *method*.
3. **The positive effect is now two commits** (K03 ×3, K09 ×1), both on tasks with no defect present.
4. **M21's category-B finding stands untouched**: six of nine defects were visible in the diff and
   missed. Nothing in M22 addresses that, because M22 measures controls.

## 14 · Recommended M23 — not started

1. **Stop adding scored milestones on this repository.** M17–M22 have now produced: one reproducible
   positive effect on two commits, both defect-free; a detection floor that is not about missing
   context; and an axis that swings 83 points between control sets. A seventh milestone drawn from
   the same 47 commits is unlikely to change any of that.
2. **Not an extraction rule.** Nothing measured across six scored milestones passes the key-first
   gate, and M21 established that most observed review failures are not context failures at all.
3. **Put the decision to the operator.** The programme's central claim — *supplying the context
   improves a review* — has been tested six times. What survives is: the bundle **does not harm**
   (0 false positives, 0 regressions, 0 confident errors across M19/M20/M22), and it **reduces
   abstention**, with demonstrable bundle-sourced evidence on two commits. That is a real, modest,
   honestly-earned result. Whether it justifies further investment — a different corpus, a human
   reviewer, or a different question entirely — is a research decision, not one a milestone should
   take alone.

Stopping at M22 as instructed.
