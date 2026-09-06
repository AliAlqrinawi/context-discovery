# M7 · Experiment 5 — the finished tool on a real Laravel pull request

- **Status:** Experiment complete. **No production code was changed.**
- **Date:** 2026-09-05
- **Classification:** **ACCEPTABLE WITH KNOWN GAPS**
- **Fixture:** [`tests/Acceptance/fixtures/experiment-05/`](../../tests/Acceptance/fixtures/experiment-05/)

---

## 1 · Objective

M0–M6 each proved one rule against a fixture built to isolate it. M7 asks the question none of them
could: **does the accumulated design produce a useful, precise bundle on a real pull request?**

This is also the commit Phase 0 deferred. `requirements.md`: *"the first thing to feed the finished
tool is the missing **sloppy** commit (Experiment 5) — because if the move-set is not actually
bounded, that is where it breaks."*

## 2 · The diff, and why it is realistic

`abouelsid-backend` @ `ec92403` — *"feat: menu PDF upload with permanent URL and QR code with logo"*.
A real feature PR from a real Laravel 12.64.0 application, taken as pushed: 9 files, 519 insertions,
7 of them PHP.

It was chosen because it exercises the whole surface at once without being manufactured to:

| Ingredient | Where |
|---|---|
| project model reference | `Setting`, `MediaItem`, `User` |
| project service reference | `MenuQrService` from the routes |
| Laravel facade static call | `Storage::disk`, `Route::get/post/delete/prefix` |
| framework dependency class | `JsonResponse`, `Request`, `UploadedFile` |
| third-party dependency | `Endroid\QrCode\{Builder,Color,PngWriter,ErrorCorrectionLevel}` |
| Eloquent dynamic dispatch | `Setting::updateOrCreate`, `Setting::where`, `MediaItem::where` |
| **chained calls** | `Setting::where(...)->value(...)`, `MediaItem::where()->where()->where()->first()` |
| **framework inheritance** | `$this->success()` / `$this->deleted()` — from `ApiResponse` via an abstract `Controller` |
| local methods | `storedPath()`, `payload()`, `logoPath()` |
| persistence writes | file delete + `storeAs` + `updateOrCreate`, **no transaction** |
| natural noise | routes, a seeder edit, 154 lines of tests |
| comments / docblocks / strings | throughout, incl. `'application/pdf'` and a QR-contract comment |

Two of the seven files are **new**, which is normal in a feature PR and turned out to matter (§7).

## 3 · Answer key

Written from the diff and the referenced sources **before the binary was run**, per ADR-001's
key-first rule. 14 rows: **3 FETCH, 1 FLAG, 10 OMIT**. Full text in
[`answer-key.json`](../../tests/Acceptance/fixtures/experiment-05/answer-key.json).

The four rows that ask for something:

| id | reference | verdict | why |
|---|---|---|---|
| E5.1 | `App\Models\Setting` surface | **FETCH** | `upload()` mass-assigns six columns; whether they are fillable is off-diff. Experiment 1's move exactly |
| E5.2 | `App\Models\MediaItem` surface | **FETCH** | the logo lookup filters on `page`/`section`/`key` and reads `->path` |
| E5.3 | surrounding-transaction | **FLAG** | `upload()` deletes a file, writes a file, then writes the DB, with no transaction and no cleanup |
| E5.4 | `App\Traits\ApiResponse::success` | **FETCH** | the controller's entire return contract, inherited two hops away |

## 4 · What the tool produced

```
items 26 · fetched 15 · flagged 11 · dropped 0 · used_tokens 1231 / 8000
28 stderr diagnostics
```

## 5 · Gap table

| id | Expected | Actual | Classification |
|---|---|---|---|
| E5.1 `Setting` surface | FETCH | **absent** | **FALSE NEGATIVE** |
| E5.2 `MediaItem` surface | FETCH | **absent** | **FALSE NEGATIVE** |
| E5.3 transaction premise | FLAG | FLAG (item 25) | **correct** |
| E5.4 `ApiResponse::success` | FETCH | **absent** | **FALSE NEGATIVE** (predicted, ADR-A010) |
| E5.5 `Storage::disk` | OMIT | OMIT + 4 diagnostics | **correct** |
| E5.6 `Setting::updateOrCreate` / `::where` | OMIT | **FLAG ×4** | **FALSE POSITIVE** (predicted, ADR-A010) |
| E5.7 `MediaItem::where` chained | OMIT | **FLAG ×1**, links silent | **FALSE POSITIVE** on the head |
| E5.8 `JsonResponse`, `Request` | OMIT | OMIT + diagnostics | **correct** |
| E5.9 `Endroid\QrCode\*` | OMIT | OMIT + 4 diagnostics | **correct** |
| E5.10 same-file siblings | OMIT | **FETCH ×6** | **OVER-FETCH** (predicted) |
| E5.11 `MenuQrService` surface | OMIT | **FETCH ×3** | **OVER-FETCH** (predicted) |
| E5.12 test-only references | OMIT | **FLAG ×4, FETCH ×3, premise ×1** | **FALSE POSITIVE / OVER-FETCH** |
| E5.13 comments / docblocks / strings | OMIT | OMIT | **correct** |
| E5.14 `<?php` read as a symbol | OMIT | **FETCH ×6** | **FALSE POSITIVE** (D1, predicted) |

## 6 · Measurements

| Measure | Value |
|---|---|
| **Precision** | **1 / 26 = 3.8 %** |
| **Recall** | **1 / 4 = 25 %** |
| False positives | 16 items (9 Eloquent flags, 6 `<?php`, 1 test premise) |
| False negatives | 3 (E5.1, E5.2, E5.4) |
| Over-fetch | 9 items / **730 tokens** — slices whose text is already in the diff |
| Actual tokens | 1231 |
| Ideal tokens (same estimator) | ~324 |
| **Token expansion ratio** | **3.8×** |
| **Framework / vendor source in bundle** | **0 items** ✅ |

Composition of the 1231 tokens: 730 duplicate the diff, 283 are the `<?php` defect, 180 are
false-positive flags, **19 are the one justified item**.

## 7 · The finding the fixtures could not have produced

**Experiment 1's model-surface move — the single most-earned move in the corpus — never fired.**

`NamedReferenceAssertionExtractor`'s form 3 recognises a class name in a `new`, type, or return-type
position. In this PR `Setting` and `MediaItem` appear **only** as `Setting::updateOrCreate(...)`,
`Setting::where(...)`, `MediaItem::where(...)` — verified: the diff contains no `new Setting`, no
`: Setting`, no `Setting $x` anywhere. So no bare-class assertion is formed, and the model surface
the answer key most wants is never fetched.

Every synthetic fixture wrote its models into type positions, so M1–M6 never saw this. It is a
**recall** defect in the interaction between two closed lists — the extractor's three forms and
ADR-A010's depth bound — and neither list is wrong on its own.

This is exactly the failure mode ADR-A003 predicted: *"If Experiment 5 needs a move the four
extractors cannot express, the tool produces a visibly incomplete bundle. That is the intended
failure mode: it sends the question back to the research repository rather than being patched away."*

**Recorded as an evidence gap. Not fixed in M7.**

## 8 · The finding that worked

Item 25 — `ASSUMPTION: this code assumes a surrounding transaction; caller not checked`, on
`MenuPdfController`. `upload()` deletes the old file, writes the new one, then writes the database,
with no transaction and no cleanup on failure.

That is a **real defect**, and the repository's own history proves it: commit `04328a5`,
*"fix: delete orphaned uploaded files when a DB write fails after upload"*, later fixed exactly this
class of bug across 13 files. The tool flagged, on the original commit, the bug the author would
come back and fix.

One qualification: the provenance is the file, not `upload()`, because a new-file diff is one region
(defect **D2**). Right finding, coarse localisation.

## 9 · Noise experiment

Added one unrelated real commit (`fdef4a9`, 10 files) **and** crafted lexical noise: a docblock with
`@see Log::warning()`, comments naming `Branch::create()` and `MediaItem::where()`, and string
literals containing `Setting::updateOrCreate`, `Illuminate\Support\Facades\Storage::disk` and
`Endroid\QrCode\Builder\Builder`.

| | baseline | with noise | change |
|---|---|---|---|
| diff bytes | 22 893 | 32 839 | **+43 %** |
| items | 26 | 27 | +1 |
| tokens | 1231 | 1263 | **+2.6 %** |

The one new item is the `<?php` defect on the new migration file. **The lexical noise produced
exactly zero items** — Experiment 2's precision requirement holds on real input, and the
comment/docblock/string guards M3–M5 added are doing their job.

## 10 · Budget experiment

| budget | items | fetched | flagged | used | dropped | premise kept |
|---|---|---|---|---|---|---|
| 500 | 18 | 7 | 11 | 497 | 8 | **yes** |
| 2000 | 26 | 15 | 11 | 1231 | 0 | yes |
| 8000 | 26 | 15 | 11 | 1231 | 0 | yes |

Drops follow `ItemPriority` exactly: the three `named_reference` slices first (190, 96, 32 tokens),
then same-file slices largest-first. All 11 flags survive — priority 1 is never dropped (P10). The
500-token bundle is an **ordered subsequence** of the 8000 one. No defect found; the mechanism
behaves as specified.

One observation worth recording: at 500 tokens the bundle is **11 flags and 7 slices**, and 9 of
those flags are false positives. Because flags are never dropped, false-positive flags crowd out
real context precisely when the budget is tightest.

## 11 · Determinism

Five runs JSON and five runs Markdown at budget 8000, plus five at budget 500. **stdout and stderr
byte-identical in every case**, including item ordering, token counts and `dropped[]` ordering.

## 12 · Regression

| Suite | Result |
|---|---|
| M1 acceptance | OK — 75 tests, 623 assertions |
| M2 boundary | OK — 10 tests, 70 assertions |
| M3 | OK — 34 tests, 77 assertions |
| M4 | OK — 15 tests, 68 assertions |
| M5 | OK — 15 tests, 41 assertions |
| M6 | OK — 18 tests, 29 assertions |
| **Full suite** | **980 tests, 4501 assertions, 4 failures** |

The four are the unchanged Phase 0 gate (private fixtures absent).

## 13 · Known limitations, ranked

| # | Gap | Severity | Why |
|---|---|---|---|
| **G1** | **The model surface never fires when a model is only used as `Model::method()`** | **Critical** | It loses the highest-value, best-evidenced context type on what appears to be the *common* Laravel shape. This is the difference between a useful bundle and a nearly empty one |
| **G2** | Eloquent dynamic dispatch flags as unresolved — 9 of 26 items | **High** | Every one is a false statement in front of the reviewer, and at low budgets they crowd out real context. Blocked by ADR-A010 |
| **G3** | New-file diffs duplicate the diff into the bundle — 730 tokens, 59 % of the payload | **High** | ADR-A005 explicitly rejects putting the diff back in the bundle. The own-file move does not know the file is new, and its reason text (*"whose contract the diff does not show"*) is provably false here |
| **G4** | D1 — `<?php` read as an unimported symbol, 6 items | **Medium** | Pure noise, cheap in tokens, trivially recognisable by a reader; but it makes `same_file_symbol_absence` useless as a signal, and Experiment 1's real missing-import finding would be lost in it |
| **G5** | Inheritance/trait members (`$this->success()`) produce nothing at all | **Medium** | A silent omission, which P10 rates worse than a flag. Blocked by ADR-A010 |
| **G6** | Test files are treated as production code | **Medium** | 10 of 26 items come from `MenuPdfTest.php`, including a transaction premise on a test |
| **G7** | D2 — premise provenance is the file, not the member | **Low** | The finding is right; only the pointer is coarse |
| **G8** | Chained-call links are invisible | **Low** | Measured, and on this diff it cost nothing: the heads carried the same subjects |

Two checklist items could **not** be tested here, honestly: the diff contains no genuine typo or
unknown class, and no project method whose name resembles a framework one. Those remain covered only
by the synthetic fixtures.

## 14 · Verdict

**ACCEPTABLE WITH KNOWN GAPS.**

What the accumulated design got right, on real input and without a single fixture to lean on:

- **zero** framework or vendor source in the bundle — the invariant M4/M5/M6 exist to hold;
- 28 dependency references settled with citations instead of flags;
- lexical noise produced nothing, and a 43 % larger diff cost 2.6 % more tokens;
- the budget dropped predictably and never dropped a flag;
- byte-identical output across ten runs;
- and it flagged a real bug the author later came back and fixed.

What makes it *not* simply acceptable is **G1**. A bundle whose precision is 1-in-26 is still
readable — a reviewer discards noise quickly — but a bundle that omits the model surface on the
commonest Laravel shape is missing the thing it exists to supply. G1, G2 and G3 together account for
almost everything wrong with this run, and none of them is a bug in a rule: each is a closed list
meeting real input for the first time.

Per ADR-A003 that outcome is the design working as intended — the question goes back to the research
repository, not into a patch.

## 15 · Evidence gaps recorded, not fixed

| Gap | What it would need |
|---|---|
| **G1** | A new extraction form — a class named in `Name::member` position also asks for the class's surface. This is a **new move**, so ADR-A003's four-step gate applies: experiment, requirement, ADR, wiring. This experiment is the first half of that evidence |
| **G2, G5** | ADR-A010's trigger: an ADR-001-grade key naming a member the changed file's directly-referenced class does not declare. E5.4 is exactly such a row — **the trigger may now be met**, and that is the single most consequential thing this experiment produced |
| **G3** | The own-file move needs to know a file is new. The parser already distinguishes it (`new file mode`); the extractor does not consume that |
| **G4, D2** | Pre-existing defects recorded in M0 §4.8; neither has an owner yet |
| **G6** | No frozen decision distinguishes test code from production code. The research never contemplated it |

## 16 · What was changed

**No production code.** `src/`, `bin/` and `composer.json` are untouched — verified by mtime: the
newest file under `src/` predates this experiment's answer key by three hours.

Created: `tests/Acceptance/fixtures/experiment-05/` (diff, noise diffs, answer key, captured
outputs, README) and this document. Modified: `docs/research/README.md`.
