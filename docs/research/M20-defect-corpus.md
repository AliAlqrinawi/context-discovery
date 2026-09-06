# M20 · a corpus with real defects, and a floor

> **Classification: B — the experiment exposes a real problem, and its evidence is insufficient for
> the intended conclusion.** Not because the corpus was weak: it is the strongest yet built — 4 of 7
> tasks with STRONG ground truth from later real fix commits, on a harness corrected first. It is
> insufficient because **both arms scored zero**. Neither DIFF_ONLY nor DIFF_PLUS_BUNDLE identified
> a single keyed defect on any STRONG task, and a comparison between two zeroes measures nothing.
>
> No production file changed.

## 1 · Objective and question

> Does DIFF_PLUS_BUNDLE improve a reviewer's ability to **correctly detect real defects**, compared
> with DIFF_ONLY?

M19 answered a weaker question — its corpus contained no defect at all. This one does.

## 2 · Pre-flight and the harness correction

All three trees clean. CLI `8cdbebb`, architecture `48fc016`, corpus `450d91f`.

**The M19 defect.** M18 and M19 passed `--repo` pointing at the corpus repository's *working tree at
HEAD* while `--diff` was a historical commit's diff, so source resolution used today's files. Six of
M18's 46 commits carry an `unreadable path` diagnostic for it; M19's K1 was compromised outright.

**The correction** — `harness/bundle-at-commit.sh` — checks the commit out into a detached
`git worktree` and points `--repo` there, removing it on exit. Recorded as **ADR-A022** and pinned by
`tests/Acceptance/HarnessResolvesAtCommitTreeTest.php`, which builds a throwaway two-commit
repository where a class *moves*, then asserts the old harness cannot see the reviewed file, the new
one can, and the corpus is left clean with no worktree behind.

**Measured effect.** On M19's compromised task `0a6e7d1`: **2 items / 221 tokens → 4 / 449**, and the
one remaining `unreadable path` is correct because that commit deletes the file. Across this corpus
the corrected harness produces materially larger bundles — `ec92403` moves from **18 items / 606
tokens to 37 / 986**.

> **M18's and M19's figures are therefore understated, not inflated.** Their conclusions are
> conservative rather than wrong in direction. Neither is reopened.

`src/` and `bin/` are untouched. No production change appeared necessary at any point.

## 3 · Corpus selection

Criteria fixed before selecting: **S1** a later real commit fixes a behaviour present in the target ·
**S2** ≤ 1000 changed lines and ≤ 35 files · **S3** controls have no later amendment · **S4** the
target produces a non-empty bundle under the corrected harness.

Excluded by S2, stated in advance and independent of outcome: `8054003` (2339 lines, 71 files) and
`4c6a83b` (2375 lines, 55 files), **both of which do have real later fixes**.

## 4 · Ground truth

| | Commit | Key | Strength | Fixed by | Defect |
|---|---|---|---|---|---|
| D1 | `ec92403` | YES | **STRONG** | `04328a5`, `2c2a7b4` | (a) file written and old one deleted before the DB write, no transaction or cleanup; (b) `permanent_url` returns the API domain |
| D2 | `fc573d2` | YES | **STRONG** | `11c0ced` | nullable descriptions leave the update path unable to clear a field |
| D3 | `e5e48ce` | YES | **STRONG** | `1fa1f66` | raw `featured`/`signature` query strings — `"false"` is truthy |
| D4 | `ec76dc4` | YES | **STRONG** | `dae67ab` | `PageContentSeeder` uses `create()`, so re-running breaks the unique constraint |
| D5 | `44726d0` | YES | MODERATE | `721ea3c` | three different default page sizes ship together |
| C1 | `4411454` | NO | MODERATE | — | control; `User::$fillable` verified to contain all three mass-assigned fields |
| C2 | `e770086` | NO | MODERATE | — | control |

**STRONG 4 · MODERATE 3 · WEAK 0** — 4 of 7, meeting the "at least half STRONG" standard.

## 5 · Reviewer isolation and protocol

Fourteen fresh language-model agents, one per (task, condition), no history, no repository access,
each pointed at a directory containing only its own packet. Diffs byte-identical between conditions,
verified by `cmp`. Packets grepped for key and milestone terms: clean.

**The reviewer is an LLM proxy, not a human.** Nothing here is evidence about human reviewers.
Isolation was enforced by instruction, not sandboxing.

M17's protocol, unchanged: the same five questions, none added, removed or reworded.

## 6 · Results

| Measure | DIFF_ONLY | DIFF_PLUS_BUNDLE | Δ |
|---|---:|---:|---:|
| correct decisions | 1/7 | **2/7** | +1 |
| **keyed defect identified — 5 defect tasks** | **0/5** | **0/5** | **0** |
| **keyed defect identified — 4 STRONG tasks** | **0/4** | **0/4** | **0** |
| high-confidence incorrect | 0 | 0 | 0 |
| **false positives** | **0** | **0** | 0 |
| abstentions | 6/7 | 4/7 | −2 |
| dependency identified (file level) | **1/7** | **0/7** | **−1** |
| evidence-supported | 7/7 | 7/7 | 0 |

## 7 · STRONG-ground-truth results — the headline

| | D1 | D2 | D3 | D4 |
|---|---|---|---|---|
| defect detected, DIFF_ONLY | ✗ | ✗ | ✗ | ✗ |
| defect detected, DIFF_PLUS_BUNDLE | ✗ | ✗ | ✗ | ✗ |
| CANNOT_TELL → YES caused by the bundle | no | no | no | no |

**Zero of four, in both conditions.** D1's reviewers both answered YES, so both score a "correct
decision" — but for a *different* defect (§9), not either keyed one.

This is the milestone's central result and it is negative in a specific way: **the experiment had no
power to discriminate.** With both arms at zero, "the bundle does not help detect defects" and "these
defects are undetectable by this reviewer whatever it is given" produce identical data. That is why
the classification is **B** and not A.

## 8 · Decisive cases

**D2 — the prediction held.** The key said in advance that the bundle *cannot* reach this defect:
nothing in the changed regions names `UpdateDishAction`, and the tool follows only references the
diff makes (P4/X1). The bundle is 2 flags / 40 tokens. Both conditions abstained. A miss that was
predicted before the run is a working model, not a failure.

**D1 — the bundle carried the mechanism and it did not land.** The bundle contains the
surrounding-transaction premise **twice**, in words that name defect (a) almost exactly. Both
reviewers went to `composer.lock` instead. This is the *second* milestone in which that flag was
present and unused (M17 §8 recorded the first).

**D4 — bundle-only evidence, wrong target.** The bundle carries the `PageContent` model surface;
DIFF_PLUS_BUNDLE quoted a *different* model's fillable list and asked for the **testimonials**
migration. The evidence it needed — the `(page, section, key)` unique index — is a schema fact X3
defers, so it was never reachable.

**D5 — the one regression.** DIFF_ONLY abstained; DIFF_PLUS_BUNDLE answered **NO** where the key says
YES. Abstention → incorrect. Its bundle is the second largest by flags (66 of 97 items) and its Q5
quote is from the diff, so this is not a case of misleading bundle content so much as a reviewer
becoming decisive without becoming right — precisely the effect the brief asked to be separated from
usefulness.

**C1 — the one improvement, and it replicates M19 exactly.** DIFF_ONLY abstained; DIFF_PLUS_BUNDLE
answered NO quoting `'password' => 'hashed',` — verified **absent from the diff** and present in the
bundle's `User::casts` slice. M19 measured the same commit with the *defective* harness and got the
same pair of answers and the same quote. The effect survives the harness correction.

But C1 is a **control with no defect**. It measures correctly concluding that nothing is wrong. That
is worth something; it is not defect detection.

**No false positives anywhere.** On both controls and across all five defect tasks, neither condition
invented a defect. The bundle did not make anyone confidently wrong.

## 9 · Unexpected discoveries

**The composer constraint, for the third time.** Both D1 reviewers independently reported that
`endroid/qr-code` 6.1.3 requires PHP `^8.4` while `composer.json` declares `^8.2`. Verified in M17
and unchanged. It is not in this key either — the key was written about the *code* defects the later
fixes establish, and this defect has no later fix.

That makes **three consecutive scored milestones in which blind reviewers reported a real problem a
hand-written key did not contain** (M17: this one; M19: `CacheGroup`'s un-refreshed index TTL; M20:
this one again). The key was not modified. The pattern is now the most consistent finding of the
whole scored series, and it is a finding about **the method**, not about the tool.

**A reviewer quoted a bundle artefact as evidence.** D3's DIFF_PLUS_BUNDLE cited
`class PageContentController extends \App\Http\Controllers\PageContentController {}` — a line from a
*subclass shim* in the diff — and named a file that is not where the defect is. Worth recording, not
worth acting on.

## 10 · Cost

| Task | items | fetched | flagged | tokens |
|---|---:|---:|---:|---:|
| D1 | 37 | 7 | 30 | 986 |
| D2 | 2 | 0 | 2 | 40 |
| D3 | 85 | 49 | 36 | 4694 |
| D4 | 66 | 50 | 16 | 3386 |
| D5 | 97 | 31 | 66 | 2700 |
| C1 | 16 | 4 | 12 | 479 |
| C2 | 30 | 26 | 4 | 504 |

Mean **1827 tokens / 47.6 items**; median **986 / 37**; max **4694 / 97**.

The one task where the bundle materially changed the result — **C1** — cost **479 tokens**, the
second cheapest in the set. The two most expensive bundles (D3 at 4694, D4 at 3386) changed nothing.
No cost-benefit ratio is claimed from seven tasks; the observation is only that expense and effect
were not aligned here.

## 11 · Regression

- `git status src/ bin/` empty — **no production file changed.**
- Full suite **1080 tests, 4842 assertions, 4 failures** — the same four pre-existing
  `ExperimentKeyTest` failures. Three tests added, none weakened.
- M1 baseline **30/150 green**. All milestone suites **275/1298 green**.
- Contract locks re-verified: **5 ports · 5 assertion kinds · 7 premises · 2 levers ·
  `bundle_version` 1**. Priority semantics, vendor exclusion and ordering untouched.
- **Zero vendor items** in all seven bundles.

## 12 · Determinism

Every candidate regenerated **3 times** through the corrected harness across JSON, Markdown and
stderr: **byte-identical, 0 differences**. Ordering and token accounting are covered by the JSON
comparison. No task exhibited budget pressure at 8000, so `dropped[]` was empty throughout and is
not exercised here — M18 covers the drop path on the one corpus commit that reaches it.

## 13 · Limitations

- **The floor is the limitation.** 0/4 against 0/4 on STRONG tasks means this design could not have
  detected an improvement even if one existed.
- **n = 7**, one repository, one author. No statistical test computed or available.
- **The reviewer is a language model**, and these defects may simply be beyond it: three of the four
  STRONG defects require knowledge no single file carries (a caller's transaction, a schema
  constraint, a sibling action's null-filtering).
- **D1 is scored "correct" for the wrong reason** in both conditions — a limitation of Q1 as a
  measure, not of the key.
- **`vendor/` is borrowed from the main checkout** by the corrected harness, because it is not
  committed. Sound for placement (ADR-A014) but not for a commit whose dependency set differed.
- Isolation by instruction, not sandboxing.

## 14 · Remaining gaps

1. **The floor itself.** Until a corpus exists where DIFF_ONLY detects *some* defects, no scored run
   can measure improvement. That is now the binding constraint, replacing coverage (M18).
2. **A flag that names a risk still does not produce action** — observed in M17 and again in D1.
3. **Hand-written keys have now missed a real problem three times out of three.**
4. **The schema facts three of the four STRONG defects turn on** — a transaction boundary, a unique
   index, a sibling's null filter — are all things X3 and ADR-A010 deliberately place out of reach.
   That is a coherent position, and this milestone is the first measurement of its cost.

## 15 · Recommended M21 — not started

1. **Break the floor before running another scored comparison.** Either find defects a diff-only
   reviewer *can* catch — so there is headroom to improve on — or accept that this reviewer cannot
   do the task and stop running the comparison. Establishing DIFF_ONLY's detection rate on a wider
   set of real defects is a cheap prerequisite and needs no bundle at all.
2. **Do not add an extraction rule.** Nothing here passes the key-first gate. D2's miss was
   *predicted* by the existing model; D4's needs a schema resolver X3 defers; D1's needs a caller
   ADR-A010 forbids. Each is a known, deliberate boundary, and one negative scored run is not the
   evidence required to move any of them.
3. **Consider whether the programme's question has been answered well enough.** Across M17, M19 and
   M20 the bundle has produced exactly one reproducible, evidence-traceable improvement — C1, twice,
   on a task with no defect in it. That is a real result and a small one, and it deserves being
   stated as such before more milestones are spent.

Stopping at M20 as instructed.
