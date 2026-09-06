# M13 · G1 — the model surface and `Model::method()`

- **Status:** Experiment complete. **No production code was changed.**
- **Date:** 2026-09-05
- **Verdict:** implementation **is** justified — as a **resolution-time** rule, not a new extraction move
- **Fixture:** [`tests/Acceptance/fixtures/experiment-13/`](../../tests/Acceptance/fixtures/experiment-13/)

---

## 1 · Objective

M7 measured that Experiment 1's model-surface move — the best-evidenced move in the corpus — **never
fired** on a real Laravel pull request, because Laravel code writes `Setting::updateOrCreate(...)`
and never `new Setting` or `: Setting`. M13 asks whether `Model::method()` carries enough evidence to
request that surface, and what boundary stops the answer becoming *"extract every `Class::method()`"*.

## 2 · Hypothesis

> H — a class reached only through `Name::member` is enough evidence to request its surface.

Deliberately **not** assumed. The experiment was built to falsify it, and it did: H is true only
under a conjunction of three further facts, none of which is available where the move would live.

## 3 · The exact forms tested, and an unexpected discovery

The frozen closed-form list (`01-architecture.md` §3.3) reads:

| Form | Example | Earned by |
|---|---|---|
| `Name::member` | `PlaidItemStatus::REVOKED` | Exp 4 |
| `$this->prop->method(` | `$this->plaidClient->createLinkToken(` | Exp 4 |
| a class name in a `new`, type, **or static-call** position | `PlaidAccount` model surface | Exp 1 |

**Discovery D13-1 — the implementation diverges from the frozen list.** Row three says *static-call
position*; the extractor's own docblock repeats it word for word (line 24, and again at line 183);
and the code then excludes exactly that position:

```php
if ($after !== null && $tokens[$after]['id'] === T_DOUBLE_COLON) {
    continue; // form 1 already covers it.
}
```

The positions form 3 actually accepts are: followed by `T_VARIABLE`, preceded by `new`/`instanceof`,
preceded by `:` or `?`. **G1 is therefore a conformance gap, not a missing move.** That materially
changes which architectural gate applies — see §13.

**Discovery D13-2 — extraction cannot answer the question.** `RegionAssertionExtractor::forRegion()`
receives `(ChangedFile, ChangedRegion, string $fileText)` and the extractor holds only a
`MemberSlicer`. It has **no** `ClassLocator` and **no** `SourceRepository`. So *"is this an Eloquent
model?"*, *"is it project code?"* and *"does the member exist?"* are all unanswerable there. Any
boundary needing them cannot be an extraction rule.

## 4 · Fixture design

One **modified** file (so M11's and M12's new-file rules stay out of the measurement) referencing six
others: `Package`/`Setting` via static calls only, `Widget` via the accepted forms, `Registry` (a
non-model project class declaring `create`), `MissingGateway` (absent), `Log` (facade), plus docblock,
`@see`, comment, string-literal and `::class` noise. Verified before running that `Package` occurs in
code only in static-call position.

## 5 · Hand-written answer key

Written before the CLI ran. It states the wanted **outcome** per case and deliberately names **no**
boundary — the boundary is what the measurement then has to find.

| id | case | expected |
|---|---|---|
| G1.A | model via static call only | Package **surface** fetched + existing flag |
| G1.B | `create` / `updateOrCreate` | Package **and** Setting surfaces |
| G1.C | `query()` | one surface per model, not three |
| G1.D | `new`/type/return forms | Widget surface **unchanged** |
| **G1.E** | `Registry::create` | the `create` **member** only — **no surface** |
| **G1.F** | `MissingGateway::resolve` | exactly **one** flag |
| G1.G | `Log::info` | no item — M3 unchanged |
| G1.H | lexical noise | zero items |
| G1.X | everything else | schema, kinds, premises, levers, accounting unchanged |

## 6 · Baseline

`items 9 · fetched 3 (57 tok) · flagged 6 (119 tok) · total 176`

Six of eight checks pass. The two failures are **exactly G1**: `Widget`'s surface is fetched (it
appears in an accepted form) while `Package`'s and `Setting`'s are not.

## 7 · Results — four candidate boundaries measured

| Boundary | added | slices | tokens | what it adds |
|---|---:|---:|---:|---|
| **B0** today | 0 | 0 | 0 | — |
| **B1** frozen form 3, unconditional | 5 | 7 | 135 | Package+3, Setting+1, **Registry+3**, **MissingGateway EXTRA FLAG**, Log settled |
| **B2** B1, project source only | 3 | 7 | 135 | Package+3, Setting+1, **Registry+3** |
| **B3** only when the member does not resolve | 4 | 4 | 70 | Package+3, Setting+1, **MissingGateway EXTRA FLAG**, Log settled |
| **B4** placeable **and** project **and** member unresolved | **2** | **4** | **70** | Package+3, Setting+1 |

- **B1 fails G1.E and G1.F** — it gives `Registry` a surface it does not need and produces a *second*
  flag for one unknown reference.
- **B2 fails G1.E** — ownership does not distinguish a model from an ordinary project class.
- **B3 fails G1.F** — "the member did not resolve" is also true when the class does not exist.
- **B4 satisfies every row.**

**B4 was derived from the key, not from the data.** The key fixed the outcomes first; B4 is the
conjunction that produces them. It was then checked against input it was not built from — §9.

## 8 · Precision and recall

Against the key's eight testable rows: **B0 — 6/8** (recall of the two model surfaces: **0/2**).
**B4 — 8/8** (recall **2/2**), with **0 false positives**: no extra flag, no `Registry` surface, no
framework item, nothing from noise.

B1 introduces 2 false positives (Registry surface, MissingGateway flag); B2 introduces 1; B3
introduces 1.

## 9 · The real pull request, as independent validation

The same simulation on M7's Experiment 5 diff — input B4 was not designed against:

| Boundary | added | slices | tokens | classes |
|---|---:|---:|---:|---|
| B1 | 7 | 7 | 388 | Setting, MediaItem, User **+ Storage, Route, Sanctum, ErrorCorrectionLevel** (settled, but 4 extra diagnostics) |
| B2 | 3 | 7 | 388 | Setting, MediaItem, User |
| B3 | 5 | 7 | 388 | Setting, MediaItem, User + Storage, Route |
| **B4** | **3** | **7** | **388** | **Setting, MediaItem, User** |

Those three are exactly M7's answer-key rows **E5.1** (`Setting` surface, FETCH) and **E5.2**
(`MediaItem` surface, FETCH) — two of the three false negatives M7 recorded — plus `User` from the
test file. B4 would move M7's recall from **1/4 to 3/4**.

Projected bundle effect, both fixtures:

| | items | fetched | flagged | tokens |
|---|---|---|---|---|
| experiment-13 B0 | 9 | 3 | 6 | 176 |
| experiment-13 B4 | 13 (+4) | 7 (+4) | 6 | 246 (+70) |
| real M7 PR B0 | 11 | 0 | 11 | 218 |
| real M7 PR B4 | 18 (+7) | 7 (+7) | 11 | 606 (+388) |

The M7 bundle would nearly triple. That is **earned** context — the surfaces Experiment 1 requires —
but it is a real cost and M14 must state it rather than discover it.

## 10 · Budget, determinism, regression

**Budget** on the unmodified binary: at 500, 2000 and 8000 the bundle is identical (9 items, 176 used,
0 dropped). **Determinism:** 5× JSON and 5× Markdown byte-identical on stdout and stderr; the
simulation is byte-identical across 3 runs. **Vendor source in the bundle: 0 items.** The existing
model-surface fetch stays project-local — every added slice in B4 resolves under `app/`.

**Regression:** M1 75 · M2 10 · M3 34 · M4 15 · M5 15 · M6 18 · M10 13 · M11 8 · M12 14 — all OK, and
necessarily so: nothing was changed.

## 11 · Full suite

**1015 tests · 4545 assertions · 4 failures** — identical to the M12 baseline. The four are the
pre-existing Phase 0 gate (private fixtures absent). No test was added by M13.

## 12 · Discovered gaps, recorded not fixed

| # | Discovery |
|---|---|
| **D13-1** | The implementation excludes the static-call position that the frozen form table, and the extractor's own docblock, both include. G1 is a conformance gap |
| **D13-2** | Extraction cannot see the locator or the repository, so no model/ownership/existence test can live there |
| **D13-3** | A `surrounding-transaction` premise fires on the fixture from `create` + `updateOrCreate` in one region. Correct by ADR-A009's trigger, incidental here, and untouched |
| **D13-4** | B4's third condition overlaps ADR-A016's territory: "the member did not resolve" is the same fact OQ6 examined. A016 ruled it insufficient to *classify* the member; B4 uses it only to decide *what else to fetch*, which is a different question and does not reopen A016 |

## 13 · The answer

> **Does `Model::method()` provide enough evidence for a new extraction move?**
>
> **No — not on its own, and not as an extraction move at all.** `Name::member` is enough to know a
> class is *referenced*. It is not enough to know whether its surface is wanted: `Registry::create`
> and `MissingGateway::resolve` are the same shape and must not get one. The three facts that settle
> it — placeable, project-owned, member unresolved — are all resolution-time facts, and D13-2 shows
> extraction cannot reach them.
>
> **The exact boundary: request a class surface when, and only when, all three hold —**
> **(1)** the `ClassLocator` places the class, **(2)** the path is project source (ADR-A012), and
> **(3)** the referenced member does not resolve in that class's own file. **Otherwise nothing changes.**

## 14 · Recommendation for M14

**Implement B4** as a resolution-time fallback in `NamedReferenceResolver`, gated as follows.

Because of **D13-1** this is *not* ADR-A003's four-step gate for a new move: the frozen form list
already names the static-call position, so this brings the implementation **toward** the frozen
contract. But it widens observable behaviour, so it still needs its own ADR — the same standing as
ADR-A012's narrowing, in the opposite direction. **The gate M14 must pass:**

1. an ADR recording B4, the three conditions, and why B1/B2/B3 were rejected by measurement;
2. **G1.E and G1.F as regression tests** — a surface must never appear for a class whose member
   resolves, nor a second flag for an unknown class;
3. no new assertion kind, premise, lever or schema field — B4 reuses the existing bare-class subject
   and the existing surface path;
4. M1's 30 baseline runs re-measured, with every changed entry explained;
5. the M7 token increase (218 → ~606) stated in the ADR as the price of closing E5.1 and E5.2.

One question M14 should settle explicitly: whether the surface should be requested **in addition to**
the existing member flag, or **instead of** it. This experiment measured the former; the latter would
also remove 4 of M7's 9 Eloquent false positives and deserves its own key row.
