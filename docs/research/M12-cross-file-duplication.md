# M12 · Cross-file duplication

- **Status:** Complete. **Implemented.**
- **Date:** 2026-09-05
- **Decision:** [ADR-A019](../../../context-discovery-architecture/architecture/decisions/ADR-A019-context-the-diff-already-shows-is-not-fetched.md)
- **Fixture:** [`tests/Acceptance/fixtures/experiment-12/`](../../tests/Acceptance/fixtures/experiment-12/)

---

## 1 · Objective

M11 closed own-file duplication and left the cross-file half: **318 tokens** of `MenuQrService`'s
surface, fetched because `routes/web.php` names it, while the same diff creates
`app/Services/MenuQrService.php`.

## 2 · What the diff retains (Phase 1, measured)

| State | `isNew` | path | regions |
|---|---|---|---|
| created | **true** | new path | `1-N`, all lines added |
| modified | false | path | `start-end` per hunk, post-change numbering |
| deleted | false | **old** path | empty span (`lastLine < firstLine`), removed lines only; text absent from the checkout |
| renamed | — | — | a pure rename produces **no files at all** |

So the tool can ask two things and only two: *was this file created?* and *does a region contain this
span?* Both are arithmetic on data already held — no text matching, no inference.

## 3 · The trap, keyed before any code

The obvious rule — *the declaring file is in the diff, so do not fetch it* — is **unsafe**. A modified
file shows only its hunks. `experiment-12`'s `HiddenService.php` is changed by the diff, hunk
`@@ -26,6 +26,6 @@`, while the referenced `run()` is declared at **line 7**. A path-level rule would
suppress it and delete context the reviewer never saw.

## 4 · Evidence model

| | State | Shown? |
|---|---|---|
| **A** | modified, declaration **wholly inside** a region | yes |
| **B** | modified, declaration outside every region | **no** |
| **C** | created — every line is an added line | yes |
| **D** | modified, only unrelated content changed | **no** (a case of B) |
| **E** | not the declaring file, or not in the diff | **no** |

Containment must be **total**: overlapping-start, overlapping-end, straddling and spread-across-two-hunks
are all keyed as *not shown*.

## 5 · Answer key and results — all rows pass

| Row | Reference | State | Expected | Result |
|---|---|---|---|---|
| M12.1 | `NewService::run` | C | do not fetch | ✓ |
| M12.1b | `?NewService` bare surface — **M7's shape** | C | do not fetch | ✓ |
| M12.2 | `VisibleService::run` (line 12, hunk 11-16) | A | do not fetch | ✓ |
| **M12.3** | `HiddenService::run` (line 7, hunk 26-31) | **B** | **FETCH** | ✓ |
| M12.4 | same file, unrelated change | D | **FETCH** | ✓ |
| M12.5 | created file with docblock/comment/string/`::class` noise | C | no items | ✓ |
| M12.6 / M12.7 | two changed files, visible / hidden | C / B | covered by 1b / 3 | ✓ |
| M12.8 | `Untouched::go` | E | **FETCH** | ✓ |
| M12.9 | deleted file | boundary | record only | recorded |
| M12.X | schema, kinds, premises, levers, priority | — | unchanged | ✓ |

**Fixture: 7 items / 194 tokens → 2 items / 43 tokens**, and the two survivors are exactly the two the
key forbids suppressing.

**M12.9 · deleted files**, measured not invented: `isNew` false, old path retained, empty region span,
text absent from a post-change checkout, so the class cannot be placed and the reference flags with
ADR-A017's wording. `showsEntirely()` returns false for an empty span.

## 6 · Fixture design note

My first fixture put every class in one namespace and produced **no cross-file references at all** —
`NamedReferenceAssertionExtractor` resolves through the file's own `use` block, so a same-namespace
reference is not a cross-file reference. Restructured into `App\Services\` with imports.

That surfaced an adjacent observation, recorded not fixed: a same-namespace class reference is
currently reported as `same_file_symbol_absence` — *"the file's use block does not import it"* — which
is true of the text and false of PHP, since same-namespace classes need no import.

## 7 · Implementation

| File | Change |
|---|---|
| `src/Domain/Diff/Diff.php` | `showsEntirely(path, firstLine, lastLine)` — created file, or one region containing the span entirely |
| `src/Pipeline/DiscoverContext.php` | filters `NamedReference` slices; when all are withheld, the reference is settled with one diagnostic |

**Scoped to cross-file references.** Own-file slices are exempt, and the experiment shows why: M1's
`S10-missing-import-absence` has `record()` at line 7 inside hunk `@@ -4,4 +4,8 @@`, so a uniform rule
would suppress Experiment 1's headline finding.

**In the pipeline, not the resolver.** "Where does this live?" is resolution's question, unchanged.
"Does the reader already have it?" is a bundling question, and the pipeline is where the diff and the
slices meet. Nothing is filtered after assembly — the slice never becomes an item.

## 8 · The real pull request

| | M7 | M11 | **M12** |
|---|---:|---:|---:|
| items | 26 | 14 | **11** |
| fetched | 15 | 3 | **0** |
| flagged | 11 | 11 | **11** |
| tokens | 1231 | 536 | **218** |

**−82 % against M7.** The 318 tokens are gone: `MenuQrService.php` carries `new file mode 100644`, so
its surface is state C and safely withheld — answered from the diff's own evidence, exactly as
Phase 8 required rather than assumed.

Stated plainly: this PR's bundle is now **entirely flags**. That is a property of the pull request —
nearly every file in it is new, and what it references is either in the diff or Eloquent dynamic
dispatch (ADR-A016) — not of the rule.

## 9 · Budget

At 500, 2000 and 8000 the bundle is identical: 11 items / 218 used / **0 dropped** (M11: 500 → 13 /
346 / 1 dropped). The duplicates now disappear *before* budgeting rather than being dropped by it,
which is what Phase 10 asked for. Priority bands and drop semantics untouched.

## 10 · Regression and determinism

**M1: all 30 runs byte-identical.** `S10-missing-import-absence` and `S07`'s `Registry` surface both
survive; `Log::info` framework-known, `Package::activatte` still flagged, M10's diagnostics intact,
lexical noise still ignored, **0 vendor items** anywhere.

Suites: M1 75 · M2 10 · M3 34 · M4 15 · M5 15 · M6 18 · M10 13 · M11 8 · M12 14 — all OK.

Determinism: 5× JSON, 5× Markdown, 5× at a drop-forcing budget — stdout, stderr, ordering, tokens and
`dropped[]` byte-identical.

## 11 · Full suite

| | M11 | M12 | Δ |
|---|---|---|---|
| tests | 1001 | **1015** | +14 — exactly `DiffVisibilityTest` |
| assertions | 4528 | **4545** | +17 — same file |
| failures | 4 | **4** | unchanged Phase 0 gate |

## 12 · Remaining gaps

| Gap | Status |
|---|---|
| **G1** · model surface never fires on `Model::method()` | open — ADR-A003 gate; **now the largest remaining item** |
| **G5** · `$this->inheritedMember()` silent | open — extraction |
| **D2** · premise provenance is the file, not the member | open |
| Own-file slices in a modified file, hunk-visible | **new**, recorded in evidence-gaps |
| Same-namespace reference reported as a missing import | **new**, observed in §6 |
| 21 one-hop dependency-parent sites | open (A015) |
| Missing import in a created file | open (A018) |

## 13 · Next

Duplication is finished — ADR-A005 is now enforced at both sites, and the M7 bundle contains nothing
the reviewer already has. What remains is **recall**, not precision.

**G1** is the largest: on M7 the model surface never fired because real Laravel writes
`Setting::updateOrCreate(...)` and never `new Setting` or `: Setting`. It needs a *new extraction
form*, so ADR-A003's four-step gate applies — experiment first, and M7 is already half of that
evidence.

Second: the same-namespace false absence found in §6. It is cheap, it is a truthfulness defect of the
same family as M10's, and it inflates `same_file_symbol_absence` on any project that does not import
its neighbours.
