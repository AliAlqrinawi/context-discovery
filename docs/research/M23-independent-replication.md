# M23 · replication on an independent repository

> **Classification: C — the experiment cannot establish the question, because the corpus's controls
> are not controls.** At least four of six "no defect" commits contain a real defect, verified
> against the source. One was later fixed by a commit titled `bugs`, which my `fix:`-prefix control
> filter did not catch.
>
> **M22 did not replicate.** Correct control decisions went 1/11 → 6/11 on abouelsid; here they are
> **0/6 → 0/6**. But that comparison is not interpretable, because the key it is scored against is
> wrong.
>
> The finding that does survive: **the control-construction method does not transfer between
> repositories.** No production file changed.

## 1 · Objective

Replicate M22 on a repository none of M17–M22 touched.

## 2 · Repository selection — rule fixed before any corpus inspection

**R1** PHP/Laravel with `vendor/` installed · **R2** 100–600 commits · **R3** not abouelsid, not any
context-discovery repo · **R4** among those, the highest ratio of `fix:` commits to total.

**halaw won at 26/260 = 10.00%**, ahead of chrisweb 5.08%, ezlife_backend 4.29%,
kidneycustomerapp_backend 1.33%. The rule was applied before any diff, bundle or defect was seen.

**Independence is genuine.** Different domain (e-commerce/delivery), **Laravel ^10.10 vs 12**, 21
merge commits vs 0, and predominantly different authors — aya mazhar 120, ayamazhar83 63,
Ali Alqrinawi 35, abdullahessam 21, ayamazhar41 20, eslam shosha 1. **None Claude-authored.** No
commit, key, packet or reviewer answer from M17–M22 was reused.

## 3 · Corpus

11 tasks — 5 defect, 6 control — selected by criteria fixed first: **S1** a later real `fix:` commit
corrects a behaviour the target introduced · **S2** ≤ 500 changed lines · **S3** controls have no
later `fix:` commit touching their files · **S4** non-empty bundle under the corrected harness ·
**S5** no selection on expected outcome.

**3 of 5 defect tasks are STRONG (60%)**, each verified in the source before the key was frozen:

| | bad → fix | Defect |
|---|---|---|
| D1 | `1bc5470` → `271a9d0` | `canceledOrder` never restocks a cancelled PENDING order |
| D2 | `dfd6ac2` → `e4d92dd` | loads relation `category` (singular) where the model declares `categories` |
| D3 | `f7fe53b` → `b0fabba` | order placement resolves a city without checking the area is active |

D4 `395b9c3` → `72cd5a7` and D5 `5cc91c3` → `0b01ee7` are MODERATE. `d5d59ff` — a fourth STRONG
pair — was **excluded by S4** for producing an empty bundle, and that cost is recorded rather than
worked around.

Answer key frozen at sha256 `4d1ab29a14b09de6` before any reviewer ran.

## 4 · Deviations

1. **Two agents failed to launch** on a safety-classifier timeout (C2-A, D4-B) and were relaunched
   unchanged against the identical committed packets. All 22 cells completed.
2. **Four agent results carry a classifier-unavailable notice** (D5-A, C4-A, D5-B, C4-B). Their
   answers were read and verified like every other; none was treated differently.
3. **halaw's working tree has one modified file** (`composer.lock`), untouched throughout. The
   worktree harness does not disturb it — verified before and after.

## 5 · Results

| Measure | DIFF_ONLY | DIFF_PLUS_BUNDLE | Δ |
|---|---:|---:|---:|
| **keyed defect identified — 5 defect tasks** | **0/5** | **0/5** | 0 |
| Q1 = YES on a defect task | 3/5 | 5/5 | +2 |
| **correct control decisions** | **0/6** | **0/6** | **0** |
| defect reported on a control | 5/6 | 6/6 | +1 |
| confident (HIGH) reports on a control | 2 | 2 | 0 |
| abstentions | 3/11 | **0/11** | −3 |
| evidence-supported | 11/11 | 11/11 | 0 |

**Not one keyed defect was identified in either arm** — the third consecutive milestone in which the
keyed-detection rate is zero on both sides (M20, M21, M23).

## 6 · Why the comparison is not interpretable

**At least four of the six controls contain a real defect**, verified in the source:

- **C3** `28f2411` — the rewritten import drops `id => $row[0]` and `is_international => 0`.
- **C5** `b5b2859` — `'location' => 'nullable|required|max:255'`, mutually contradictory. **Later
  fixed** by `9ba34b3`, whose subject is **`bugs`**.
- **C6** `00095a9` — `ProductsExport::headings` lists `en, ar` while the query selects
  `name->ar, name->en`. The two name columns are exported under swapped headings.
- **C4** `1a55793` — the same ar/en misalignment, found independently by a different reviewer.

**S3 was "no later commit whose subject begins with `fix`".** halaw's authors title fixes `bugs`,
`modification for excel products`, and similar. The criterion that worked on abouelsid's descriptive
messages **does not transfer**, and it produced six "controls" of which most are not.

So the false-positive column — M22's headline — **cannot be computed here**. Reporting 5/6 or 6/6 as
"false positives" would be false: they are mostly true positives against a wrong key.

**The key was not modified.** C3, C4, C5 and C6 are marked **CONTESTED**.

## 7 · Reviewer discoveries, verified

Beyond the contested controls, on the **defect** tasks every YES named a defect *other* than the
key's — and two are verified real and still live:

- **D3, both arms, HIGH confidence** — `$request['sub_total'] += $product->getPrice();` in
  `OrderService::itemsOperations`, with **no multiplication by quantity**. Any line item with
  quantity > 1 is charged as a single unit. Verified at line 244 of the commit, and **never fixed in
  the repository's later history**. This is a live pricing defect in a production e-commerce system.
- **D4, bundle arm** — the new cancel and show endpoints perform **no ownership check**, so any
  authenticated caller can cancel or read another user's order by guessing its id. Plausible and
  serious; not independently verified here.

**One verified spurious claim, identical in both arms:** **D5** — that `Coupon` gains `SoftDeletes`
with no `deleted_at` migration. The `coupons` migration does contain it. Both reviewers named the
migration as their missing context and asserted the defect anyway — the same overreach M21's C05
showed, and the bundle did not correct it.

> That brings the running total to **eleven** distinct real defects surfaced by blind reviewers that
> hand-written keys did not contain, across six scored milestones. It remains the most reliable
> effect this programme has measured, and it is a finding about the **method**.

## 8 · The one comparison that survives

Abstentions fell **3 → 0**, and on D2, D4 and C2 the bundle arm committed where the diff-only arm
would not. That is consistent with M19, M20 and M22, where the bundle also reduced abstention.

It is worth **less** here than there, for a reason M21 already established: on these tasks
committing was not the same as being right — every bundle-arm YES on a defect task named something
other than the keyed defect. The bundle makes the reviewer decisive; this corpus gives no evidence
that it makes the reviewer correct.

**No K03-style result appeared.** No bundle-arm answer in M23 rests on evidence verifiable as
absent from the diff. The effect replicated three times on abouelsid's `4411454` has **no analogue
here.**

## 9 · Cost

Bundles: 2–40 items, 40–1719 tokens; mean 22 items / 1231 tokens. The largest bundle (D3, 40 items)
and the smallest (C5, 2 items / 40 tokens) both produced identical answers in the two arms, so no
relationship between bundle size and effect is visible.

## 10 · Regression

`git status src/ bin/` empty — **no production file changed.** Full suite **1080 tests, 4842
assertions, 4 failures** (the same four pre-existing). M1 baseline **30/150 green**. All milestone
suites **275/1298 green**. Contract locks: **5 ports · 5 kinds · 7 premises · 2 levers ·
`bundle_version` 1**.

## 11 · Determinism

All 11 halaw bundles regenerated **3 times**: **0 differences of 99 comparisons** across JSON,
Markdown and stderr. **Zero vendor items** in all 11. halaw left exactly as found — one pre-existing
modified file, no worktree behind.

## 12 · Limitations

- **The controls are not controls.** This is the milestone's central limitation and it invalidates
  its primary measure.
- **n = 11**, one reviewer per cell, no variance estimate. No statistical test computed.
- **Ground truth is weaker than M20's.** halaw's commit messages are terse (`fix : qty`), so
  establishing what a fix corrects took source reading rather than reading the message. Three tasks
  reached STRONG; on abouelsid four did, from richer messages.
- **The tool was built and tuned against abouelsid.** M3's facade rule, M14's model surface and
  ADR-A011's Laravel knowledge were all derived from Laravel 12 code. halaw is Laravel 10.
- LLM proxy; isolation by instruction; `vendor/` borrowed from the main checkout (ADR-A022's caveat).

## 13 · Remaining gaps

1. **Control construction does not generalise.** Any future scored milestone needs a control
   criterion that survives a repository whose authors do not write `fix:`.
2. **Keyed detection is 0/5 again**, on a third repository-corpus combination.
3. **Eleven key misses across six milestones.**
4. **The K03 effect did not appear outside abouetsid.** The programme's one reproducible positive
   result now has a visible boundary: it has been seen on one commit, in one repository.

## 14 · Recommended M24 — not started

1. **Do not run another scored milestone until the control problem is solved.** A defensible control
   needs either a repository with a clean, labelled history, or a construction that does not depend
   on commit subjects — synthetic controls derived from a known-good commit, say. That is a method
   milestone, not a measurement one.
2. **Not an extraction rule.** Nothing here passes the key-first gate, and M21's finding stands: most
   observed review failures are not context failures.
3. **The honest summary to put to the operator.** Across seven scored milestones the bundle has:
   never caused a regression; never produced a confident error attributable to it; consistently
   reduced abstention; and demonstrably supplied deciding evidence on **one commit in one
   repository, three times**. Attempting to replicate that on an independent repository produced
   **no analogue**. That is a real, small, and now visibly bounded result. Whether to continue is a
   research decision, and the evidence for continuing on this axis is weaker after M23 than before it.

Stopping at M23 as instructed.
