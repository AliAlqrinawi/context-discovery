# M21 · baseline defect detectability

> **Classification: A — the corpus is strong and the baseline is established.** The baseline is
> **near zero**: an isolated reviewer identified **0 of 9** keyed defects, **0 of 5** with STRONG
> ground truth, while raising a false positive on **5 of 6** controls.
>
> The decisive finding is not the zero. It is *why*: in **six of nine** defect tasks the keyed
> evidence was **literally present in the diff** and the reviewer did not use it. M20's floor is not
> an information-availability problem, and **more context cannot fix it.**
>
> No production file changed. No bundle was generated or supplied to anyone.

## 1 · Objective

> How often can an isolated reviewer correctly identify a real defect from the **diff alone**?

M20's comparison had no discriminating power because both arms scored zero. This establishes the
floor so the next bundle experiment — if there is one — can be interpreted.

## 2 · Corpus

15 tasks from bad-commit → later-real-fix pairs: **9 defect · 6 control**, **STRONG 5 · MODERATE 8 ·
WEAK 2**. All five STRONG tasks are defect tasks. Diff sizes span **8 to 2375 changed lines**,
deliberately, so *"the defect was buried"* could be separated from *"the defect was invisible"*.

Criteria S1–S4 fixed before scoring; `c48963d` excluded at 12,631 lines despite being a genuine
STRONG pair. Full table in `answer-key.md`. Twice the size of M20's corpus and with one more STRONG
task.

## 3 · Reviewer and protocol

One fresh language-model agent per task: no history, no repository, **no bundle**, no key, no other
reviewer's answers. M17's protocol, unchanged — the same five questions.

**The reviewer is an LLM proxy, not a human.** Isolation by instruction, not sandboxing.

## 4 · Results

| | |
|---|---:|
| correct decisions | **3/15 · 20%** |
| **keyed defect identified — 9 defect tasks** | **0/9** |
| **keyed defect identified — 5 STRONG tasks** | **0/5** |
| Q1 = YES on a defect task | 3/9 |
| abstained on a defect task | 6/9 |
| confidently wrong (HIGH + incorrect) | **0/15** |
| controls correctly classified | **0/6** |
| **false positives on controls** | **5/6 · 83%** |
| evidence-supported | 15/15 |
| confidence HIGH / MEDIUM / LOW | **0** / 9 / 6 |

Two shapes stand out and pull in opposite directions.

**On defect tasks the reviewer mostly abstains** — 6 of 9 CANNOT_TELL, five of those at LOW
confidence. Where it did answer YES (T04, T09, T10) it named a *different* defect each time.

**On controls it almost never abstains** — 5 of 6 YES. The same reviewer that will not commit when a
defect is present readily reports one when it is not. Nothing in the protocol distinguishes the two
situations for it.

**Not one answer was HIGH confidence.** The "confidently wrong" failure mode this series has watched
for since M17 did not occur, in either direction; the reviewer is uniformly hedged.

## 5 · STRONG-ground-truth results

| | T01 | T04 | T05 | T06 | T09 |
|---|---|---|---|---|---|
| detected | ✗ | ✗ | ✗ | ✗ | ✗ |
| missed | ✓ | ✓ | ✓ | ✓ | ✓ |
| abstained | ✓ | — | ✓ | ✓ | — |
| confidently wrong | — | — | — | — | — |

**0 of 5.** M20's floor reproduces on a corpus twice the size, with an extra STRONG task and a full
range of diff sizes.

## 6 · Diff-visibility analysis

Performed **after** scoring, as required.

| | | Tasks |
|---|---|---|
| **A** · evidence in the diff, defect detected | **0** | — |
| **B** · evidence in the diff, defect **missed** | **6** | T05 `PageContent::create` · T06 `$request->only([...])` · T07 the scope signature · T08 all three page defaults · T09 44 `Cache::tags` sites · T10 the validation rules |
| **C** · diff genuinely lacks what is needed | **3** | T01 (the update path is absent) · T03 (the write loop is absent) · T04(a) (the caller's transaction) |

For the **C** cases the category of missing information is: **a sibling action's null-filtering**
(T01, T03) and **a caller's transaction boundary** (T04a). To those the corpus adds two facts no code
carries at all — **which cache driver production runs** (T09) and **whether descriptions ought to be
optional** (T10).

**B dominates, two to one.** The dominant failure is not that the diff withheld the evidence; it is
that the reviewer read the evidence and drew no conclusion from it. That is the single most important
number in this milestone, because a context bundle can only address category **C**.

## 7 · Key misses and reviewer discoveries

**Two new real defects the key did not contain**, both verified against the source:

- **T10** — `MediaItemController::update(Request $request, int $id, UploadMediaAction $action)` takes
  `$id` and never uses it: `$action->execute(UploadMediaDTO::fromRequest($request))`. Every sibling
  controller passes `$id`. `PUT /media-items/{id}` does not target the identified record.
- **C06** — a **control**. `UpdateDishAction` got `Cache::forget('dashboard_stats')` and
  `UpdateTestimonialAction` did not, while the dashboard counts `Testimonial::where('is_active',
  true)`. Toggling `is_active` leaves the count stale for the 5-minute TTL. Verified from the diff's
  own file list.

**One verified false positive:** **C05** claimed a null `show_in_footer` would be written to a NOT
NULL column. `UpdateBranchAction::execute` wraps everything in `Arr::whereNotNull([...])`, so the
null is dropped. The reviewer named that exact file as its missing context (Q3) — **and asserted the
defect anyway.** Asserting past the boundary it had just identified is worth more attention than the
wrong answer itself.

**The composer constraint, a third time** (T04): `endroid/qr-code` 6.1.3 requires PHP `^8.4` against a
declared `^8.2`. Found independently in M17, M20 and now M21.

**Neither key was modified.** C06 is marked **contested**.

> That is now **five distinct real defects** surfaced by blind reviewers that hand-written keys did
> not contain, across **four** scored milestones. The pattern is more reliable than any effect this
> series has measured about the bundle.

## 8 · Confidence and abstention

Zero HIGH answers in fifteen. On defect tasks: 6 abstentions, 5 at LOW. On controls: 1 abstention.
The reviewer's confidence carries almost no information — it is MEDIUM whenever it commits and LOW
whenever it does not, regardless of whether it is right.

This matters for interpreting M19 and M20: their "abstention → NO" transitions were read as the
bundle helping the reviewer commit. M21 shows this reviewer commits readily on controls with no help
at all, so **commitment is not evidence of understanding.**

## 9 · Regression

`git status src/ bin/` empty — **no production file changed.** Full suite **1080 tests, 4842
assertions, 4 failures** (the same four pre-existing). M1 baseline **30/150 green**. All milestone
suites including the M20 harness test **275/1298 green**. No test weakened.

## 10 · Determinism

Corpus regenerated **3 times**: all 15 diffs and all 15 packets byte-identical each pass, **0
differences**. Task ordering and identifiers are fixed by `corpus.tsv` (15 rows, written before the
run). Answer-key checksum `6169326e960958f5`, unchanged from before scoring.

## 11 · Limitations

- **n = 15, one reviewer per task, one repository.** No statistical test computed or available.
- **The reviewer is a language model.** Every result may be a fact about this reviewer rather than
  about review.
- **One reviewer per task means no variance estimate.** T04's YES and T05's abstention might swap on
  a re-run; nothing here measures that.
- **Two controls are WEAK**, and one of them (C06) turned out to contain a real defect — so the
  false-positive rate of 5/6 is an upper bound; 4/6 is the defensible figure.
- **Scoring "keyed defect identified" is a judgement** made by the milestone author against a key
  written first. In all nine cases the reviewer's Q2 named something plainly different, so no call
  was close.

## 12 · Remaining gaps

1. **The floor is not about missing context.** Six of nine defects were visible and missed. This is
   the first evidence that the programme's central hypothesis — *supply the missing context and the
   review improves* — addresses a minority of the observed failures.
2. **The false-positive rate is the unmeasured cost.** 4–5 of 6 controls drew a spurious defect
   report. No milestone has ever measured what a bundle does to *that* number.
3. **Hand-written keys: five misses in four milestones.**
4. **Confidence is uninformative**, which undermines the abstention-based readings in M19 and M20.

## 13 · Recommended M22 — not started

The measured result points to outcome **2** of the three the brief anticipated: DIFF_ONLY detects
almost none, so investigate before claiming bundle usefulness.

1. **Do not rerun the bundle comparison on this corpus.** With DIFF_ONLY at 0/9 there is no headroom
   to measure, and M20 already demonstrated what a two-zero comparison yields.
2. **Measure the false-positive axis instead.** It is the one number that moved decisively here
   (5/6), it is cheap to measure, and it is the one place a bundle could plausibly *hurt* — a
   reviewer given a model surface might invent fewer imaginary defects, or more. That is a real,
   answerable question with headroom in both directions, which detection currently lacks.
3. **Consider a protocol change before a corpus change.** Q1 asks "is there a defect?" of a whole
   commit. Every miss in category B was a reviewer reading the right line and not connecting it to a
   consequence. Whether a protocol that asks about *a specific behaviour* changes that is testable,
   cheap, and independent of the bundle. Note this changes the instrument, so M17–M21 would no longer
   be directly comparable — a cost to weigh, not a decision to take here.
4. **Not an extraction rule.** Category C accounts for 3 of 9 tasks, and its missing information is
   a caller's transaction (ADR-A010), a sibling's null filter (P4/X1) and a deployed cache driver
   (X3) — all deliberate boundaries. Nothing here passes the key-first gate, and the correlation
   between diff size and abstention is not evidence of anything.

Stopping at M21 as instructed.
