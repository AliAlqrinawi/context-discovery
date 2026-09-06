# M16 · G4 — when are two bundle items the same item?

> **Classification: A, under a narrow identity.** Exact duplicate items are safely redundant — where
> *exact* means every field a reviewer can see. ADR-A021 accepted; deduplication implemented in
> `BundleAssembler`. `experiment-16` 15 → 13 items, 328 → 278 tokens, 0 duplicates. **M7 is
> byte-identical at every budget**, because it contains none.

## 1 · Question and answer

> Does one identical slice per `(path, member)` always provide the correct reviewer context, or can
> multiple origins of the same member justify multiple copies?

**Neither, as stated.** `(path, member)` is the wrong unit — it destroys three keyed rows. And
multiple origins justify multiple copies **for flags, not for slices**, because only a flag's
provenance records its origin.

The answer is a third thing: **two items are the same item when every field a reviewer can see is
the same** — `lever`, `reason`, `assertion_kind`, provenance `path`, `member` and span, and
`payload`. That unit collapses the duplicates and breaks nothing.

## 2 · Phase 0 — where duplicates come from, and what they are

`BundleAssembler::itemsFor()` builds one item per slice, taking provenance from the **slice** and
`reason` from the assertion's `claim`. The claim names the subject, not the origin. So two
assertions with the same subject from two files produce items that differ in **no field at all** —
verified on `experiment-15`, where the two `OrderCalculator::total` items match byte for byte
including `reason`.

The asymmetry that decides the milestone:

| Lever | `provenance` names | Two origins produce |
|---|---|---|
| **flagged** | the **requesting** site — origin path, region span | **two different items** |
| **fetched** | the **declaring** site — slice path, member, span | **two identical items** |

**A fetched item's origin is not in the bundle at all** — not in the copy, and not in the first one
either. So keeping the duplicate cannot preserve origin information. The brief's question *"does the
second copy carry provenance a reviewer needs?"* becomes checkable rather than a matter of taste,
and the answer is **no, for fetched items only**.

*What provenance is missing from the schema:* for a fetched item, which changed region requested it.
Adding it would be a schema change, and it is missing from **single** items too — so it is a
separate question about item content, not a reason to keep duplicates. Recorded as a gap (§10).

## 3 · Answer key, written first

`tests/Acceptance/fixtures/experiment-16/answer-key.json` — eleven rows, written from the fixture
sources before the CLI was run and before any production code changed. The row table is in the
fixture's README.

Three rows expect collapse (R1 two production origins, R2 production + test, R3 twice in one file).
Six are guards that must **not** collapse (R4 different members, R5 different paths, R6 different
resolution routes, R7 identical text in two classes, R8 different spans, R10 two flags). R9 proves
framework source stays out.

**R2 is a deliberate M15 guard**: its answer must be the same as R1's *for the same reason* — every
field identical — and **not** because a test origin is worth less. M15's classification C stands;
origin is never consulted.

## 4 · Two fixture corrections, recorded

Both were made after the key was written, and neither changed a keyed expectation — the rows
described cases the first fixture did not actually produce.

1. **R9** did not place the framework (no generated map), so `Log::info` flagged as unplaceable
   instead of settling as framework-known. Fixed by adding `vendor/composer/autoload_psr4.php`.
2. **R6** put `helper()` inside the diff, so ADR-A019 correctly withheld the named-reference copy
   and the cross-route duplicate never appeared. Fixed by making `helper()` pre-existing. That
   ADR-A019 already prevents one class of cross-kind duplicate is itself worth recording.
   `ControllerB` also moved to its own namespace, because a same-namespace name is an **absence**,
   not a cross-file reference — the observation M12 first recorded.

## 5 · Baseline

Budget 8000: **15 items, 328 tokens, 0 dropped**, 4 diagnostics.

| Row | Keyed | Observed | |
|---|---|---|---|
| R1 · R2 · R3 | 1 `Calc::total` item | **3**, byte-identical, 75 tokens | ❌ **2 false positives** |
| R4 | `total` and `subtotal` | both | ✅ |
| R5 | `Calc::total` and `Formatter::total` | both | ✅ |
| R6 | 2 items, distinct kinds | **3**, three distinct kinds | ✅ (key understated) |
| R7 | `Alpha::run` and `Beta::run` | both, identical payloads | ✅ |
| R8 | `use` block [5,9] and `helper` [18,21] | both | ✅ |
| R9 | 0 items, 2 diagnostics, no vendor | exactly that | ✅ |
| R10 | 2 flags + 2 surface slices | exactly that | ✅ |
| X | contract unchanged | unchanged | ✅ |

Precision 13/15, **recall 13/13**. Duplicate items 2, duplicate tokens 50.

**So G4 is a real false positive under the key** — and it is worth saying why that conclusion is not
just "two copies look redundant": the two copies are indistinguishable in every field the schema
exposes, so a reviewer cannot read anything from the second that is not in the first.

## 6 · Boundary simulation

`boundary-simulation.php` reads a bundle the real binary produced and asks what each candidate
**identity** would keep. Each boundary is expressed as the key it collapses on — the question is not
"should duplicates go" but "what makes two items the same".

| | Identity | items | fetched | flags | tokens | rows broken |
|---|---|---:|---:|---:|---:|---|
| **B0** | none (current) | 15 | 13 | 2 | 328 | — (2 FP) |
| **B1** | `(path, member, span)` | 11 | 9 | 2 | 240 | **R6** |
| **B2** | payload text | 9 | 8 | **1** | 197 | **R6, R7, R10** |
| **B3** | slice, provenances merged | 11 | 9 | 2 | 240 | **R6** |
| **B4** | **the whole visible item** | **13** | **11** | **2** | **278** | **—** |
| **B5** | provenance only | 11 | 9 | 2 | 240 | **R6** |

**Only B4 breaks nothing**, and it removes exactly the two items the key marks as false positives.

**B1, B3 and B5 measure identically** because all three collapse on the slice's *location* — which
is precisely what R6 forbids. `ControllerA::helper` arrives three times with the same path, member,
span and text, under three different assertion kinds sitting in **two different `ItemPriority`
bands**. Collapsing them silently promotes or demotes the survivor and changes what lives through a
budget.

**B2 is the cautionary result.** Deduplicating on payload text collapses a **flag**: both
`Order::create` assumption statements are the same sentence, so the reviewer loses one of the two
lines they were being sent to. It is the cheapest boundary and the worst outcome — which is exactly
why the identity is derived from the key rather than from what is easy to write.

**Provenance impact: none.** B4 removes only items whose provenance is already present on a
surviving item, byte for byte. No provenance is lost, merged, or approximated.

## 7 · Decision and implementation

**Outcome A**, with the identity being the whole reviewer-visible item. ADR-A021 written before the
code.

Every field in the identity is forced by a row: R4 the member, R5 the path, R6 the kind and reason,
R7 the payload, R8 the span. Nothing is left over, and no field can be dropped without a guard going
red. `tokens` is a function of the payload and adds nothing.

**Where — decided from the architecture, not from convenience.**

- **Not resolution.** A resolver is a pure function of one assertion with no cross-assertion view;
  that property is what makes depth two structurally unreachable (P3). M14's surface guard is in the
  pipeline for the same reason.
- **Not the budget layer.** `BudgetEnforcer` records every drop as *"below budget priority"*, which
  would be **false** of a redundant copy, and it only fires when over budget — a duplicate would
  survive at 8000 and vanish at 500. Two behaviours, not one.
- **Assembly.** `BundleAssembler` is where resolved assertions *become* items, and where **ordering
  is already decided once so JSON and Markdown are two renderings of one list**. Identity is an item
  question and belongs beside the only other one.

The collapse runs **before** the sort, so the survivor is the first item resolved and the outcome is
a function of resolution order alone (P8).

**Is a skipped duplicate a silent omission?** No. P10 forbids dropping a *concern*; the surviving
item is byte-identical to the one skipped, so every fact, reason, provenance and byte still reaches
the reviewer. `BudgetEnforcer` records its drops because those items are *gone*. Here nothing is,
and a diagnostic announcing that an exact copy was not printed twice would be noise.

**Not `array_unique()`.** That reaches B4's answer by accident, states no rule, and would silently
change meaning the day a field is added to the schema.

## 8 · Result, and M7

`experiment-16`: **15 → 13 items, 328 → 278 tokens**, 0 byte-identical pairs, every guard row intact.
`experiment-15`: 19 → 18 items, 370 → 345 — the one duplicate `total` slice, and nothing else.

**M7's real pull request: unchanged, byte for byte, at 500, 2000 and 8000 on both streams.**

| | M14 / M15 | **M16** |
|---|---:|---:|
| items | 18 | **18** |
| fetched | 7 | **7** |
| flags | 11 | **11** |
| tokens | 606 | **606** |
| recall | 3/4 | **3/4** |
| precision | 5/18 floor, 5/10 ceiling | **unchanged** |
| duplicates | **0** | **0** |
| vendor items | 0 | **0** |

That is the honest headline and the honest caveat together: the defect is real and reproducible, and
**its measured cost on the one real input available is zero**. A reader who calls that premature has
a fair point. The counter is that it was 2 of 15 items on the fixture, and a known-redundant item is
harder to defend than its removal. ADR-A021 records both halves.

## 9 · Regression, budget, determinism

- **Full suite: 1052 → 1077 tests, 4707 → 4831 assertions, 4 failures** — the same four pre-existing
  `ExperimentKeyTest` failures (private Phase 0 fixtures absent).
- Milestone fixtures M1–M16: **272 tests, 1287 assertions, green**. The M1 baseline did not move:
  no scenario there names one member from two files.
- **One test failed on purpose and was updated.** M15's lock test asserted G4 *as it stood* — two
  identical slices — so that fixing it would have to be deliberate. It bit. That is what it was for,
  and it now records the closure and additionally pins the flag asymmetry.
- Determinism, 5 runs each on both streams, 1 distinct result every time: experiment-16 JSON @8000,
  Markdown @8000, JSON @150 (**drops forced**), and M7 JSON and Markdown @500.
- Budget: experiment-16 at 150 keeps 7 items / 134 tokens and records **6 drops**. Both flags
  survive (`ItemPriority::NEVER_DROPPED`), and the surviving fetched items still span all three
  assertion kinds — the bands are unchanged by the collapse.

## 10 · Remaining gaps

- **A fetched item does not say which region asked for it.** True of every fetched item, not just
  the ones that were duplicated, and only visible once duplicates are gone. Fixing it is a **schema
  change** and needs its own key: does a reviewer act differently knowing which line asked?
- **The retained flag's statement** (ADR-A016) is still the weakest text in the bundle.
- **No scored run.** Every open question here — whether the origin of a fetched slice is worth
  printing, whether test-origin surfaces are noise — ends at a reviewer reading a bundle, which
  Phase 1 has never measured.

## 11 · Is M17 unblocked?

Yes. M16 closes G4 and blocks nothing.

## 12 · Recommendation for M17

Not started, per the milestone.

1. **A scored run.** Three milestones in a row have ended by naming it. The tool now produces a
   defensible bundle on a real pull request; the untested claim — *"supplying the context improves a
   review"*, the central open question the requirements document names — has never been measured.
2. **The flag statement's wording**, in ADR-A017's spirit: change the text, change nothing else.
3. **Origin on a fetched item**, only if a scored run shows a reviewer wanting it. Doing it first
   would be adding a schema field on a hunch.
