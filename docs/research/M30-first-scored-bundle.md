# M30 · The first bundle scored against a context key

> **Classification: a measurement, in-sample.** One real commit, `ec92403` (D1), scored under
> two vendor modes against experiment-05's fourteen-row key by the Phase 3 backend's scorer.
> Assertion precision **2/11 (18.2%)** with a readable `vendor/` and **2/25 (8.0%)** without;
> key recall **3/4** under both; token-weighted item precision **43.1%** and **29.5%**. Of the
> nineteen items M29 identified as spurious, fourteen land on an OMIT row by exact subject and
> five are unkeyed - the key has no row for the `Route` facade.
>
> No production file changed. Nothing was tuned. The key is now locked.

## 1 · Objective

Phase 6's first milestone (backend ADR-B002): produce a real measurement, not infrastructure
for one. The backend already held two succeeded runs of D1 - `vendor_mode=installed` (18 items ·
606 tokens, byte-identical to experiment-29's arm B) and `vendor_mode=none` (37 · 986, arm A) -
and the engine already ships a bundle key for that commit,
`tests/Acceptance/fixtures/experiment-05/answer-key.json`, written 2026-09-05 before the tool
ran. Score both runs against it.

## 2 · The scoring model

Two units, never mixed, because the bundle has two levels (ADR-A024).

**The assertion - the claim.** Matched to a key row by subject, in a fixed precedence applied
blindly: an exact subject; a row about a region (`origin_path_prefix`, used once, for
experiment-05's "test-only references"); a bare-class row matching a member of that class. The
verdict is **TP** when the row is `FETCH` or `FLAG`, **FP** when `OMIT`, **unkeyed** when no row
claims it. Assertion precision = TP / (TP + FP); unkeyed assertions are excluded and reported as
the count that finds key misses.

**The item - the evidence.** One of four verdicts: **signal** - a fetched slice whose provenance
path is a `FETCH` row's source (and whose member matches when the row names one), or a flag
under a `FLAG` row; **wrong-slice** - the right file, another member; **noise** - anything else
under a keyed assertion; **unkeyed**. Item precision = signal / (signal + wrong-slice + noise),
per kind, count-weighted and token-weighted.

A `FETCH` row is satisfied by a signal item *whichever assertion produced it*: ADR-A020 retains
the model surface beside the Eloquent-member flag, so `Setting`'s surface arrives under a
`Setting::where` assertion and still counts. A `FLAG` row is satisfied by a matched flagged
assertion. An `OMIT` row is violated by a matched assertion carrying items, and vacuous when
nothing matched it at all.

**M26's two numbers, named apart.** M26 §4.2 counted one true caller among twenty fetched items
and among fourteen call sites. In this vocabulary:

- **1/20 is the cost of the claim** - signal ÷ all items of the assertion; its token-weighted
  form is M26's "58% of the budget spent". It answers *how much of what this claim cost the
  reviewer was signal*, and it is the number ADR-A006's condition is about.
- **1/14 is the grep as a caller finder** - signal ÷ (signal + noise call sites), declarations
  excluded. An instrument number.

They are different questions. Neither is "the precision".

**Key recall** = satisfied `FETCH`+`FLAG` rows ÷ all such rows. It is bounded above by the
key's author - eleven key misses in six scored milestones say how tightly - and is never called
recall.

**Two labels, derived, never set.** IN-SAMPLE when the engine export ships the key file (path and
sha256) - a key an engine carries in its tests is a key it was developed against. KEY-FIRST when
no run by this engine existed at the commit when the key was written.

## 3 · The key

Experiment-05's fourteen rows, imported from `v0.2.0`'s own export, verbatim (`region`,
`reference`, `why`, `why_not_more`), sha256 `9ff7f7d7…`. The transcription into subjects was
written from the key's text alone and is recorded per row: E5.6 splits on " / "; E5.8 on ", ";
E5.9's four `Endroid\QrCode` classes take the namespaces the region imports; E5.12 is a region
row (`tests/`) plus its four named examples - "factories" is not a subject and was not
transcribed; E5.13 names no subject and can only be vacuous.

The key was **not** amended after scoring, and cannot be: it is locked with both run ids.

## 4 · Result

Both runs: engine `v0.2.0` (`e7c919d`), budget 8000, `--caller-scope app/`, `--max-call-sites
20`, PHP 8.4.6. Both labelled **IN-SAMPLE** - `v0.2.0` ships this exact key, and M11 and M12
were driven by it - and **KEY-FIRST**.

| | `installed` 18 · 606 | `none` 37 · 986 |
|---|---:|---:|
| assertions emitted / TP / FP / unkeyed | 11 / 2 / 9 / 0 | 30 / 2 / 23 / **5** |
| **assertion precision** TP/(TP+FP) | 2/11 = **18.2%** | 2/25 = **8.0%** |
| TP / emitted | 18.2% | 6.7% |
| **key recall** (FETCH+FLAG rows) | 3/4 · E5.4 missed | 3/4 · E5.4 missed |
| OMIT rows violated / vacuous | 3 / 7 | **6** / 4 |
| `named_reference` items: signal / wrong-slice / noise / unkeyed | 4 / 0 / 12 / 0 | 4 / 0 / 26 / 5 |
| `named_reference` item precision, count · tokens | 25.0% · 39.3% | 13.3% · 26.3% |
| `unverifiable_premise` item precision | 2/2 · 38/38 | 2/2 · 38/38 |
| **all kinds, count · tokens** | 6/18 = 33.3% · 261/606 = **43.1%** | 6/32 = 18.8% · 261/886 = **29.5%** |

The two TP assertions are the `surrounding-transaction` premise, from `MenuPdfController` and
from the test file, both matching E5.3. The four signal slices are `Setting::fillable`,
`Setting::scopeForGroup` (E5.1) and `MediaItem::fillable`, `MediaItem::scopeForPage` (E5.2),
223 tokens, identical in both runs. Everything the `none` run adds is noise or unkeyed.

### 4.1 · M29's classification, as a measurement

The nineteen items the `none` run carries beyond the `installed` run are the flags M29 called
spurious: nineteen `named_reference` assertions on dependency classes, one 20-token *"could not
be resolved on disk"* flag each. **Fourteen** of them matched an `OMIT` row **by exact subject**,
and each of those rows is **vacuous** under `installed`:

| OMIT row | reference | assertions matched under `none` | under `installed` |
|---|---|---|---|
| **E5.5** | `Illuminate\Support\Facades\Storage::disk` | #0, #8, #19, #25 - four origins | vacuous |
| **E5.8** | `Illuminate\Http\JsonResponse`, `Illuminate\Http\Request` | #3, #4 | vacuous |
| **E5.9** | `Endroid\QrCode` `Builder`, `Color`, `PngWriter`, `ErrorCorrectionLevel::High` | #6, #9, #10, #11 | vacuous |
| **E5.12** | `Storage::fake`, `UploadedFile::fake`, `UploadedFile`, `Sanctum::actingAs` | #20, #22, #24, #28 | vacuous |

That is 280 of the 380 tokens M29 counted. The key's author, on 2026-09-05, before the tool ran,
wrote that every one of these should be omitted; M29, sixteen days later, found that they
appeared only because the harness could not read `vendor/`. The two agree by subject, row for row.

The **other five** - `Route::prefix`, `::get`, `::post`, `::delete` from the route files, the
remaining 100 tokens - are spurious in exactly M29's sense, Illuminate facades the map settles,
but the key never wrote a row for the `Route` facade, so they are **unkeyed** (§5.1). M29's
classification and the key agree on 14 of 19; on the other 5 the key is silent, not in
disagreement.

## 5 · Three findings the numbers produced that were not asked for

Stated as scored; none was adjusted.

### 5.1 · The key has no row for the `Route` facade

Under `none`, assertions #13-#17 - `Route::prefix`, `::get`, `::post`, `::delete` from
`routes/admin.php` and `::get` from `routes/web.php` - match no row and are **unkeyed**. The key
covers `Storage::disk` (E5.5) as "framework-known" and covers the route files' reference to
`MenuQrService` (E5.11), but never wrote the `Route` facade down. Under `installed` these five
are settled by the map and emit nothing, which is why the key's author never saw them.

This is a key miss, found by the scorer's unkeyed count doing exactly what it exists for. The
key is locked; the row is not added. It waits for the next key, where the author will know to
look.

### 5.2 · E5.4 is missed under both vendor modes

`App\Traits\ApiResponse::success` / `::deleted` - the controller's whole return contract,
inherited through the abstract `Controller` from a trait - produces no assertion in either run.
Key recall is 3/4 either way. This is a **capability gap**, not a defect: reaching it needs
the trait behind the parent class, which is the traversal ADR-A010 declined at depth one. It goes
on the record here and waits for the held-out measurement; nothing is proposed.

### 5.3 · E5.7 is violated and E5.2 is satisfied by the same two items

`MediaItem::where` matches OMIT row E5.7 (the Eloquent member: "a Laravel reviewer needs
nothing"), so the assertion is an FP and its flag is noise. Its two *fetched* items -
`MediaItem::fillable` and `::scopeForPage` - are the signal that satisfies FETCH row E5.2 ("the
model's own surface carries what matters"). One assertion, one row violated and one satisfied.
The same shape holds for `Setting::where`/`::updateOrCreate` (E5.6) and E5.1.

That is ADR-A020's RETAIN decision, measured: the surface is the value, the retained flag is the
cost, and the key - written before ADR-A020 existed - already priced both. It is reported on one
line rather than resolved, because resolving it in either direction is a decision for the
engine's gate, not for the scorer.

## 6 · What this does not show

- **It is in-sample.** `v0.2.0` ships this key; M11 and M12 changed the engine to satisfy it.
  A precision number on a development-set key is a check that the engine still does what its
  tests say, not a measurement of the engine on unseen code.
- **One commit.** n = 1; no aggregate, no distribution, no claim about any other commit.
- **A development-set key, and a locked one.** Its `Route` gap and its silence on the RETAIN
  trade-off are now part of the record, not fixable.
- **No recall claim.** 3/4 is key recall against four rows a human wrote. It says nothing about
  what the human did not think of, and §5.1 shows the human did not think of at least one thing.
- **No comparison.** One engine. The run-to-run diff exists as terms (ADR-B002 §3), not as a
  number.
- **Nothing about reviewers.** M29's two open questions stay answered "no" (ADR-B002 §4).

## 7 · Regression

`git status src/ bin/` empty - **no production file changed.** Engine suite unchanged at 1196
tests. Backend suite 101 tests, 594 assertions. The scorer, the key tables and the transcription
are in the backend (`context-discovery-backend`, commits `443e3d2` … `865fbdb`); the decision is
ADR-B002 there; this document is the measurement.

## 8 · Remaining gaps

1. **A held-out key.** A commit no ADR cites, its key written before any run at that commit is
   looked at, with `Route` and inherited traits in the author's mind. That is the first number
   that would be a result.
2. **Key recall's ceiling.** §5.1 is the eleventh-plus key miss. A second author reading the
   diff before the key is frozen is cheap and has been recommended since M19.
3. **The run-to-run diff** waits for a second engine commit.
4. **E5.4** stays open as a capability gap until a held-out key says whether it recurs.
