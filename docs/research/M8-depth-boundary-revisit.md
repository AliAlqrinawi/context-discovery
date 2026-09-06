# M8 · Dynamic dispatch and the depth-boundary revisit

- **Status:** Research and decision complete. **No production code was changed.**
- **Date:** 2026-09-05
- **Decision:** [ADR-A015](../../../context-discovery-architecture/architecture/decisions/ADR-A015-depth-boundary-trigger-evaluated-and-not-met.md) — the trigger is **evaluated and declined**; the boundary stands
- **Fixture:** [`tests/Acceptance/fixtures/experiment-08/`](../../tests/Acceptance/fixtures/experiment-08/)

---

## 1 · The question

M7 left two gaps and one open door:

- **G2 (High)** — Eloquent dynamic dispatch flags as unresolved: 9 of 26 items on a real PR.
- **G5 (Medium)** — `$this->success()` disappears with no item and no diagnostic.
- ADR-A010's **revisit trigger** — *"an ADR-001-grade experiment whose hand-written answer key names a
  member the changed file's directly-referenced class does not declare"* — which M7's E5.4 appears to
  satisfy.

M8 had to decide whether the depth boundary should move.

## 2 · The trigger, verified

| Question | Answer |
|---|---|
| Directly-referenced class | `App\Http\Controllers\Controller` (`MenuPdfController.php:5`, `extends` at :11) |
| Located at | `app/Http/Controllers/Controller.php` — one hop, placeable |
| Does it declare `success`? | **No** — the file is `abstract class Controller { use ApiResponse; }` and declares zero functions |
| `success` is declared at | `app/Traits/ApiResponse.php:9` |
| Mechanism | **trait applied to the parent** — PHP-guaranteed, not inferred |
| Distance from the changed file | **three files** |

**The letter of the trigger is satisfied.**

## 3 · The minimal confirming experiment

`experiment-08` — a small fixture with **no framework and no `vendor/`**, so the PHP-inheritance
question cannot be confused with the Laravel one. One class per mechanism; the diff adds one method
to an existing file, keeping D1/D2 out of the measurement.

### Answer key (written before the run) and result

| id | subject | mechanism | hops | expected | actual | |
|---|---|---|---:|---|---|---|
| X8.1 | `Direct::run` | declared directly | 1 | FETCH | FETCH | correct |
| X8.2 | `Direct::slug` | direct, framework-sounding name | 1 | FETCH | FETCH | correct |
| X8.3 | `OneLevel::run` | inherited once | 2 | FETCH | **FLAG** | false negative |
| X8.4 | `TwoLevel::run` | inherited twice | 3 | FLAG | FLAG | correct |
| X8.5 | `WithTrait::greet` | trait on the class | 2 | FETCH | **FLAG** | false negative |
| X8.6 | `ViaParent::greet` | **trait on the parent — the E5.4 shape** | 3 | FLAG | FLAG | correct |
| X8.7 | `Dynamic::create` | `__callStatic`, declared nowhere | — | FLAG | FLAG | correct |
| X8.8 | `Direct::nope` | unknown member | — | FLAG | FLAG | correct |
| X8.9 | `Direct::runn` | typo | — | FLAG | FLAG | correct |
| X8.10 | `$this->instanceGreet()` | inherited, called on `$this` | 3 | FLAG | **absent** | silent omission |

`items 9 · fetched 2 · flagged 7 · dropped 0 · used_tokens 238`

## 4 · Why the trigger is declined

### 4.1 · The relaxation would not reach E5.4

`success` is three files away, behind an abstract parent *and* a trait. Row **X8.6** reproduces that
shape and flags today — and would still flag after a one-hop relaxation.

### 4.2 · E5.4's blocker is extraction, not depth

`$this->success(...)` is a `$this->method(` form, and `OwnFileAssertionExtractor` skips it because
`success` is not a member of the changed file. **No assertion is formed** — confirmed in M7's captured
output: zero items and zero diagnostics mention `success` or `ApiResponse`. A resolution bound cannot
help a reference resolution never sees. Row **X8.10** isolates this.

### 4.3 · One hop fixes none of the measured false positives

| M7 subject | Immediate parent | On the parent? | Actually at |
|---|---|---|---|
| `Setting::updateOrCreate` / `::where` / `::create` | `Model` | no | `Eloquent/Builder.php` |
| `MediaItem::where` / `::create` | `Model` | no | `Eloquent/Builder.php` |
| `User::create` | `Authenticatable` | no | `Eloquent/Builder.php` |

**0 of 9.**

### 4.4 · And across an entire real application

Every static call on a project class in `abouelsid-backend/app` — 190 sites:

| Category | Count |
|---|---|
| declared in the named class (already fetched) | 73 |
| **one hop to a project parent or own trait** | **0** |
| one hop to a **dependency** parent (`XResource::collection`) | 21 |
| Eloquent dynamic dispatch, 3+ hops | 96 |

The case X8.3 and X8.5 were written to represent **does not occur** in a real Laravel application of
this size.

## 5 · The three mechanisms, kept separate (Phase 5)

| Mechanism | Where the member is | Verdict |
|---|---|---|
| **PHP inheritance / traits** | a real declaration, 2–3 files away | *inherited declaration* — reachable in principle, absent in practice (0 real project-parent sites) |
| **Laravel/Eloquent dynamic dispatch** | **nowhere** — `__callStatic` forwards at runtime | *unverifiable within one file*. X8.7 proves it with no Laravel present: no depth reaches a member that is not declared |
| **Facade `@method static` / `@mixin`** | a declarative tag in the class's own file | *framework-known* — already handled by M3/M5, unchanged |

This separation is the milestone's main analytical result. The three were being discussed as one
problem; they have different causes and only one of them is about depth.

## 6 · Options evaluated

| | Option | Evidence | Precision | Tokens | Verdict |
|---|---|---|---|---|---|
| A | Keep ADR-A010 | measurements above | unchanged | 0 | **Chosen** |
| B | One additional hop | 0 of 9; 0 of 190 project sites | unchanged in practice | 0 | Rejected |
| C | Framework knowledge answers the member | — | **fails**: `Model` has no `@method`/`@mixin`, so nothing in one file separates `Package::create` from `Package::activatte` | — | Rejected |
| D | New extraction move | this is what E5.4/X8.10 need | — | — | Deferred — ADR-A003 gated, adjacent to G1 |
| E | Resolve **OQ6** — make the flag's statement true at depth one | ADR-A010 already names it | fixes the *truthfulness* of all 117 flags | 0 | **Recommended next**, needs its own decision |

None violates P3/X1/X2 by being *considered*; B and D would change it. No option was taken that
creates a worklist, a recursive resolver, or an ancestry graph.

## 7 · The finding that changed the analysis mid-flight

My first frequency scan reported **0** one-hop sites. It only counted *project* parents. Re-run
including dependency parents, there are **21**: `UserResource extends JsonResource`, and
`collection()` is declared exactly one hop away at
`vendor/laravel/framework/src/Illuminate/Http/Resources/Json/JsonResource.php:86`.

Under ADR-A012/A013 that parent is a dependency, so one hop would convert 21 false-positive flags into
21 cited settlements at **zero token cost** — a real precision gain.

It was **not** taken, and the reason is the point: this population was discovered *while measuring*,
after `experiment-08`'s key was written. The key asks for the project-parent case, which has zero real
occurrences; it contains no dependency-parent row. Writing a rule to fit something found after the key
is exactly the confirmation bias ADR-001's key-first rule exists to prevent. It is recorded as a new,
quantified evidence gap needing its own key-first experiment.

## 8 · Regression and validation

| Check | Result |
|---|---|
| Named cases — `Log::info`, `Str::slug`, `Arr::only`, `Package::active`, `Package::activatte`, `MissingGateway::resolve` | all unchanged |
| `Package::create` / `::where` / `::orderBy` / `::query` | all still flagged; no bundle growth |
| **vendor/framework source in bundle, 32 runs** (30 M1 + M7 + M8) | **0 items** |
| Determinism — experiment-08, 5× JSON + 5× Markdown + 5× at a drop-forcing budget | stdout, stderr, ordering, tokens, `dropped[]` byte-identical |
| M1 / M2 / M3 / M4 / M5 / M6 | OK — 75 / 10 / 34 / 15 / 15 / 18 tests |
| Full suite | **980 tests, 4501 assertions, 4 failures** — the unchanged Phase 0 gate |

## 9 · G1 was not folded in

M7's G1 — the model surface never firing when a model is used only as `Model::method()` — was **not
touched**. No extraction change was made, `Setting::updateOrCreate` and `MediaItem::where` still do
not become model-surface references, and no assertion kind was added. It remains a separate
ADR-A003-gated question.

## 10 · Outcome

The depth boundary stands. The milestone's value is not a fix but a **separation**: three blockers
that were being treated as one are now individually measured, and the one that looked most likely to
be about depth turns out not to be about depth at all.

**No production code was changed.** Created: `tests/Acceptance/fixtures/experiment-08/` and this
document. Modified: `docs/research/README.md`, and in the architecture repository
`decisions/ADR-A015-…md` (new), `README.md`, `evidence-gaps.md`.
