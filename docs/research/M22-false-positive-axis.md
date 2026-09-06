# M22 · the false-positive axis

> **Classification: C — the experiment could not be completed.** Seven of eleven DIFF_PLUS_BUNDLE
> reviewers terminated with HTTP 429 (session rate limit) mid-run. Four complete pairs remain, which
> is not enough to establish the axis M21 recommended.
>
> Two results survive the truncation and are worth more than the missing cells: **K03 replicated a
> third time**, and **M21's headline false-positive rate did not replicate at all**.
>
> No production file changed.

## 1 · Objective

M21 found DIFF_ONLY detection at 0/9 with no headroom, but a defect *reported* on 5 of 6 controls —
the one axis that moved, and the one place a bundle could plausibly hurt. M22 measures it directly.

## 2 · Corpus

**11 control commits**, keyed NO, selected by criteria fixed first: CT1 no later commit fixes the
behaviour · CT2 not established as defective by an earlier milestone · CT3 produces a **non-empty**
bundle under the corrected harness · CT4 8–1000 changed lines with at least one PHP file.

Excluded by CT3 (empty bundles): `2c2a7b4`, `1fa1f66`, `3ef4d1a`. Excluded by **CT2**: `fdf15e5` —
M21 proved that "control" contains a real defect, so keying it NO again would have been dishonest.

Bundles range from **1 item / 25 tokens** to **76 items / 2423 tokens**, generated with M20's
corrected same-commit-tree harness (ADR-A022).

## 3 · Deviations, recorded

1. **The run is incomplete.** 11/11 DIFF_ONLY completed; **7/11 DIFF_PLUS_BUNDLE failed with HTTP
   429**, a session rate limit that resets on a wall clock. Missing: K05–K11. Nothing was
   substituted, inferred, re-run under a different model, or filled from M19/M20 answers on
   overlapping commits. The cells are recorded as `MISSING`.
2. **M21's answers were not reused** for the four overlapping commits, even though the protocol and
   diffs are identical, because reusing them would confound *milestone* with *condition*. Both arms
   were run fresh. That decision is what makes §6's contradiction visible.
3. **Both arms were launched in parallel** rather than sequentially. Different fresh agents per
   cell, so no contamination — but it is why the rate limit truncated the B arm specifically.

## 4 · Results — the 4 complete pairs

| Task | bundle | A | B | Outcome |
|---|---:|---|---|---|
| K01 | 1 / 25 | CANNOT_TELL | CANNOT_TELL | no change |
| K02 | 30 / 504 | CANNOT_TELL | **NO** | abstention → correct |
| K03 | 16 / 479 | CANNOT_TELL | **NO** | abstention → correct, **bundle-only evidence** |
| K04 | 76 / 1372 | CANNOT_TELL | CANNOT_TELL | no change |

| Measure (n = 4) | DIFF_ONLY | DIFF_PLUS_BUNDLE |
|---|---:|---:|
| correct control decisions | 0/4 | **2/4** |
| **false positives** | **0/4** | **0/4** |
| confident false positives | 0 | 0 |
| abstentions | 4/4 | 2/4 |

The bundle added **no** false positive on these four and converted two abstentions into correct
answers. **n = 4.** That is a direction, not a measurement, and it is stated as one.

## 5 · K03 — the third replication

DIFF_ONLY abstained; DIFF_PLUS_BUNDLE answered NO quoting `'password' => 'hashed',` — verified absent
from the diff, present in the bundle's `User::casts` slice.

| | milestone | harness | bundle | result |
|---|---|---|---|---|
| 1 | M19 K3 | at HEAD (defective) | 7 items / 299 tok | abstain → NO, same quote |
| 2 | M20 C1 | corrected | 16 / 479 | abstain → NO, same quote |
| 3 | **M22 K03** | corrected | 16 / 479 | abstain → NO, same quote |

Three independent reviewers, three milestones, one commit, the same transition and the same
bundle-sourced quotation. **This is the most reproducible result the scored series has produced** —
and it is a single commit, on a task with no defect in it, which is exactly how it should be
described.

## 6 · The result that matters most — M21 did not replicate

The DIFF_ONLY arm completed in full, so this is not affected by the truncation.

| | controls | defect reported | verified spurious |
|---|---:|---:|---:|
| **M21** | 6 | **5 · 83%** | 4 |
| **M22** | 11 | **1 · 9%** | **0** |

Same protocol, same reviewer model, same repository, same author — **different control commits**. And
M22's single YES (**K11**) is **verified real**, so the spurious rate on this set is **0/11**.

**M21's 5/6 is not a baseline.** The false-positive rate swung from 83% to 9% between two control
sets drawn from the same repository. Whatever that number measures, it is dominated by which commits
happen to be chosen, not by the condition — which means the axis M21 recommended as "the one number
that moved" **cannot support a bundle comparison at these sample sizes**. That finding does not
depend on the seven missing cells.

## 7 · Contested rows

**K11 · `ebbd1d6` — keyed NO, and the key is wrong.** The reviewer reported that
`GetEffectiveSeoAction::merge`'s per-page inheritance of `robots_noindex` is dead code. Verified: the
migration declares `$table->boolean('robots_noindex')->default(false)` — NOT NULL with a default — so
`$override?->robots_noindex` is never null once a page row exists, and `SeoSettingSeeder` creates
one. The global setting can never affect any page, while every other (nullable) field inherits
correctly.

**The key was not modified.** K11 is marked CONTESTED.

That is the **sixth** real defect blind reviewers have surfaced that a hand-written key did not
contain, across five scored milestones (M17, M19, M20, M21 ×2, M22). No other pattern in this series
is close to as reliable.

## 8 · Regression

`git status src/ bin/` empty — **no production file changed.** Full suite **1080 tests, 4842
assertions, 4 failures** (the same four pre-existing). M1 baseline **30/150 green**. All milestone
suites including M20's harness test **275/1298 green**. No test weakened. Contract locks:
**5 ports · 5 assertion kinds · 7 premises · 2 levers · `bundle_version` 1**.

## 9 · Determinism

All 11 bundles regenerated **3 times** through the corrected harness across JSON, Markdown and
stderr: **0 differences of 99 comparisons**. **Zero vendor items** in all 11. The corpus repository
was left clean with no worktree behind.

## 10 · Limitations

- **The experiment is 7 cells short of its design.** Any statement about the bundle's effect on false
  positives rests on **n = 4**, and none is made beyond direction.
- **The comparison arm is truncated non-randomly** — K05–K11 are the *later-launched* cells, which
  correlates with nothing in the corpus but is not a random dropout either.
- Keyed "no defect" remains **absence of a recorded fix plus author inspection**, and K11 shows again
  that this is not proof. The honest reading of the whole controls programme is that a "control
  commit" in a real repository is a commit whose defects nobody has found yet.
- LLM proxy, isolation by instruction, one reviewer per cell, no variance estimate.

## 11 · Remaining gaps

1. **The seven missing cells.** Cheap to finish once the limit resets; nothing else in M22 needs
   redoing, and the packets, key and harness are all committed and deterministic.
2. **The false-positive axis is unstable** — 83% vs 9% across two control sets. Measuring it usefully
   needs far more controls than any milestone here has used, or a different measure entirely.
3. **Six key misses in five milestones.** The most reliable finding of the series remains a finding
   about the *method*, not the tool.
4. **K03 is one commit.** Three replications of a single task is reproducibility, not generality.

## 12 · Recommended M23 — not started

1. **Finish M22's seven cells first**, unchanged, before anything else. The design is sound; only
   the execution was truncated. Until then no false-positive claim should be made in either
   direction.
2. **Then stop adding scored milestones on this repository.** M17–M22 have produced one reproducible
   positive result (K03, one commit, no defect present), a floor that is not about missing context
   (M21), and an axis that does not hold still (M22). A seventh milestone drawn from the same 47
   commits is unlikely to change any of that.
3. **Not an extraction rule**, and not a protocol change made mid-series. Nothing measured here
   passes the key-first gate.
4. **Worth putting to the operator directly:** the programme's central claim has now been tested six
   times and is supported by one commit. That is a legitimate place to decide whether to invest in a
   different corpus, a human reviewer, or a different question — a decision about the research, not
   one this milestone should take on its own.

Stopping at M22 as instructed.
