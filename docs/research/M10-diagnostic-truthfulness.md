# M10 · Missing-PSR-4 diagnostic truthfulness

- **Status:** Complete. **One diagnostic corrected; the bundle is byte-identical.**
- **Date:** 2026-09-05
- **Decision:** [ADR-A017](../../../context-discovery-architecture/architecture/decisions/ADR-A017-diagnostics-name-the-state-they-observed.md)
- **Fixture:** [`tests/Acceptance/fixtures/experiment-10/`](../../tests/Acceptance/fixtures/experiment-10/)

---

## 1 · Objective

The last confirmed defect from M8/M9: the diagnostic

```
missing PSR-4 entry: App\Ghost\Missing::create named in app/Consumer.php
```

is emitted when the `App\` prefix **is** declared and only the file is missing. Scope: diagnostic
truthfulness, nothing else.

## 2 · The bug, established before any change

`ClassLocator::pathFor()` walks the declared prefixes; a prefix that matches but whose directories do
not hold the file falls through to the next one, and the method returns `null`. It therefore returns
`null` for two structurally different states, and the pipeline reported both with one message:

| Class | `pathFor()` | True state |
|---|---|---|
| `App\Models\Package` | `app/Models/Package.php` | C — present |
| `App\Ghost\Missing` | `null` | **B — prefix present, file absent** |
| `App\Foo\Bar\Missing` | `null` | **B — nested under a present prefix** |
| `Acme\Lib\Thing` | `null` | **B — second prefix present** |
| `Nowhere\Ghost\Missing` | `null` | A — no prefix |

In the first run of `experiment-10`, **three of four** `missing PSR-4 entry` lines were false.

## 3 · Answer key (written before the run)

| id | subject | state | expected diagnostic |
|---|---|---|---|
| M10.1 | `App\Ghost\Missing::create` | B | `class file not found` |
| M10.2 | `MissingNamespace\Ghost::create` | A | `missing PSR-4 entry` — **unchanged** |
| M10.3 | `App\Support\Helper::run` | resolved | none |
| M10.4 | `Acme\Lib\Thing::make` | B | `class file not found` |
| M10.5 | `App\Foo\Bar\Deep::go` | B | `class file not found` |
| M10.6 | `App\Models\Package::activatte` | C | `unresolved named_reference` — **unchanged** |
| M10.7 | all rows | — | assertion kind, lever, premise, provenance, ordering, tokens identical |
| M10.8 | `Log::info` | framework-known | `framework reference: …` — **unchanged** |

The key also fixed the design: **only the false message changes.** State A was already reported
truthfully, and collapsing A and B into one vague line would fix falsity by destroying attribution —
the move ADR-A009 rejects for premises.

## 4 · Result — all rows correct

```
class file not found: App\Ghost\Missing::create named in app/Consumer.php
missing PSR-4 entry: MissingNamespace\Ghost::create named in app/Consumer.php
class file not found: Acme\Lib\Thing::make named in app/Consumer.php
class file not found: App\Foo\Bar\Deep::go named in app/Consumer.php
unresolved named_reference: App\Models\Package::activatte in app/Consumer.php
framework reference: Illuminate\Support\Facades\Log::info declared at …Log.php:25 (@method static …)
```

**The bundle JSON is byte-identical before and after** — verified by `diff`. Exactly three stderr
lines changed, and they are exactly the three that were false.

## 5 · The change

| File | Change |
|---|---|
| `src/Ports/ClassLocator.php` | `hasMappingFor(string): bool` — does any prefix cover this name, regardless of the file |
| `src/Adapters/Autoload/ComposerPsr4ClassLocator.php` | implemented from the prefix set already in memory; **opens no file** |
| `src/Pipeline/DiscoverContext.php` | chooses between the two labels |
| `tests/Fakes/FakeClassLocator.php` | the same question for the in-memory fake |

**The one thing beyond wording**, reported as the milestone required: `Ports\ClassLocator` gains a
method. That is an *internal* interface — `03-interfaces.md` §1 states the ports are internal and
"may change without notice" — and ADR-A012 added `isProjectSource()` by the same route. The public
surface, the CLI contract and the bundle schema, is untouched.

## 6 · Regression

| Boundary | Result |
|---|---|
| `App\Ghost\Missing::create` — prefix present, file absent | `class file not found` |
| `MissingNamespace\Ghost::create` — no prefix | `missing PSR-4 entry` |
| `App\Contracts\MissingGateway::resolve` (M1 S08) | `class file not found` — what M1's key said the truth was |
| `App\Models\Package::activatte` — the typo | `unresolved named_reference`, still flagged |
| `Package::create` / `::where` / `::orderBy` / `::query` | unchanged |
| `Log::info`, `Str::slug`, `Arr::only`, `Package::active` | unchanged |

**M1 fixture:** exactly 3 of 30 baseline entries changed, all `S08`, all **stderr-only** — zero
non-stderr fields moved, asserted mechanically.

**M7's real pull request:** **no** diagnostic line changed and the bundle is byte-identical, because
that repository has its dependencies installed and produced no state-B reference. The change is as
narrow as intended.

Suites: M1 75 · M2 10 · M3 34 · M4 15 · M5 15 · M6 18 · M10 13 — all OK.

## 7 · Determinism

`experiment-10`, 5× JSON, 5× Markdown, and 5× at a drop-forcing budget: stdout, stderr, item
ordering, token counts and `dropped[]` byte-identical in every case.

## 8 · Full suite

| | M9 baseline | M10 | Δ |
|---|---|---|---|
| tests | 980 | **993** | +13 — exactly `ClassLocatorMappingTest` |
| assertions | 4501 | **4516** | +15 — same file |
| failures | 4 | **4** | unchanged — the Phase 0 gate |

## 9 · What did not change

`bundle_version` 1 · the bundle schema · assertion kinds · premises and their fixed statements ·
levers · provenance shape · item ordering · token calculation · extraction moves · resolution depth ·
Laravel/framework knowledge · Eloquent behaviour · inheritance resolution. **G1, G3 and G5 were not
touched.**

## 10 · Remaining gaps

| Gap | Status |
|---|---|
| **G1** · model surface never fires on `Model::method()` | open — ADR-A003 four-step gate |
| **G3** · new-file diffs duplicate the diff into the bundle (730 tokens on M7) | open — the parser already sees `new file mode` |
| **G5** · `$this->inheritedMember()` is silent | open — extraction, ADR-A003 gated |
| 94 Eloquent dynamic flags | closed as unresolvable, ADR-A016 |
| 21 one-hop dependency-parent sites | open — needs a key-first experiment (ADR-A015) |
| **D1** · `<?php` read as an unimported symbol | open — 6 items on M7, still unowned |
| **D2** · premise provenance is the file, not the member | open |

## 11 · Next

**G3** is now the cheapest remaining item and the largest single precision win left: on M7 it was
**730 tokens, 59 % of the payload**, and it contradicts ADR-A005 explicitly. It needs no new move —
`UnifiedDiffParser` already recognises `new file mode`; the own-file extractor simply does not consume
it. **D1** is adjacent and would fall out of the same work.
