# M11 · G3 + D1 — new-file diff duplication

- **Status:** Complete. **Implemented.**
- **Date:** 2026-09-05
- **Decision:** [ADR-A018](../../../context-discovery-architecture/architecture/decisions/ADR-A018-a-created-file-is-input-not-context.md)
- **Fixture:** [`tests/Acceptance/fixtures/experiment-11/`](../../tests/Acceptance/fixtures/experiment-11/)

---

## 1 · Objective

Close M7's **G3** — 730 tokens, 59 % of a real pull request's bundle, were slices of files the diff
had just created — and **D1**, the `<?php` open tag reported as a missing import.

## 2 · Root causes, measured from the code

### A claim from M9/M10 that turned out to be wrong

M9 and M10 both recommended G3 as cheap on the grounds that *"the parser already sees `new file
mode`"*. **It does not.** `UnifiedDiffParser` line 99 says in as many words: *"index, mode,
similarity, rename and binary-notice lines carry nothing this tool reads."* Three signals were
available and all three were discarded:

| Signal | What the parser did |
|---|---|
| `--- /dev/null` | `parsePath()` returns null, then `+++ b/path` overwrites it — the null is dropped |
| `@@ -0,0 +1,N @@` | the old *start* (`$m[1]`) is never captured; only the count, as a countdown |
| `new file mode 100644` | explicitly ignored |

So G3's root cause is not "the extractor ignores a flag" but "the flag is never recorded".

### D1, at the token layer

Both extractors tokenise `'<?php ' . $regionText`. When the region itself opens with `<?php` — which
happens only for a created file, since `addedLines` holds `+` lines only — the literal re-reads as
`<`, `?`, `T_STRING(php)`, and `isClassNamePosition()` treats the preceding `?` as the `?Name`
nullable-type form. Confirmed by tokenising it directly.

### A third defect, found while measuring

The `.php` filter lived in `UnifiedDiffParser::membersIn()` only, so **non-PHP files reached PHP
extraction**: `config/thing.yml` produced `own-file lookup for label…` and `…for Menu…`, because
`: Menu` reads as a return type — the same rule as D1. Not predicted by the key; keyed by the
milestone (*"No PHP symbol extraction should occur"*), so fixed here.

## 3 · Answer key (written before implementation)

| id | case | expectation |
|---|---|---|
| M11.1 | created PHP file | its own path must not appear in fetched paths; no `same_file_*` items for it |
| M11.2 | external references from it | `Package` surface still fetched; `Log::info` still framework-known; Eloquent flags unchanged |
| M11.3 | modified PHP file | own-file behaviour **unchanged** — `Existing::helper` still fetched |
| M11.4 | created non-PHP file | no extraction, no diagnostics |
| M11.5 | `<?php` | zero items |
| M11.6 | lexical noise in a new file | zero items |
| M11.7 | everything else | schema, kinds, premises, levers, priority, accounting unchanged |

The key also fixed the invariant: **the changed diff is input, not fetched context.**

## 4 · Results — all ten checks pass

| | before | after |
|---|---:|---:|
| items | 8 | **5** |
| fetched | 6 | **3** |
| tokens | 186 | **97** |
| items whose provenance is the created file | 5 (129 tok, **69 %**) | **0** |
| items reasoned *"the region uses php"* | 2 | **0** |
| assertions from `config/thing.yml` | 2 | **0** |

M11.4 failed on the first attempt — the new-file diagnostic fired for the YAML file too. The
extension question was moved onto `ChangedFile::isPhp()` so both callers ask it once.

## 5 · Implementation

| File | Change |
|---|---|
| `src/Domain/Diff/ChangedFile.php` | `$isNew` (defaulted `false`) and `isPhp()` |
| `src/Discovery/Parsing/UnifiedDiffParser.php` | records `--- /dev/null` as `isNew`, per file, reset between files |
| `src/Discovery/Extraction/AssertionExtractor.php` | skips non-PHP files entirely; skips the own-file extractor for created files |
| `OwnFile` / `NamedReference` / `UnverifiablePremise` extractors | do not prepend a second `<?php` |
| `src/Pipeline/DiscoverContext.php` | one diagnostic per created PHP file |

Prevented at the source: the assertion is never created, so nothing is resolved, fetched or dropped.
Nothing is filtered after assembly.

## 6 · M7's real pull request

| | M7 | M11 | |
|---|---:|---:|---|
| items | 26 | **14** | −12 |
| fetched | 15 | **3** | −12 |
| flagged | 11 | **11** | unchanged |
| used tokens | 1231 | **536** | **−695, −56 %** |
| `same_file_symbol_absence` | 6 | **0** | all were D1 |
| `same_file_reference` | 6 | **0** | all were duplication |
| items added | — | **0** | |
| vendor source | 0 | **0** | |

M7 counted 730 duplicated tokens; 695 are gone. The remaining **318** are `MenuQrService`'s surface,
fetched from `routes/web.php` — a file the same diff also creates. That is **cross-file** duplication,
explicitly out of M11's scope and unkeyed by any experiment.

## 7 · Budget

| budget | items | fetched | flagged | used | dropped | premise |
|---|---:|---:|---:|---:|---:|---|
| 500 | 13 | 2 | 11 | 346 | 1 | kept |
| 2000 | 14 | 3 | 11 | 536 | 0 | kept |
| 8000 | 14 | 3 | 11 | 536 | 0 | kept |

(M7: 500 → 18 / 497 / 8 dropped; 2000 and 8000 → 26 / 1231 / 0.) Priority bands, drop order and
accounting are unchanged — there is simply less to drop, and no `same_file_*` item survives at any
budget.

## 8 · Regression

**M1 fixture: all 30 runs byte-identical.** Every M1 diff modifies an existing file, so nothing there
should move, and nothing did — including `S10-missing-import-absence`, which still produces its item.

Suites: M1 75 · M2 10 · M3 34 · M4 15 · M5 15 · M6 18 · M10 13 · M11 8 — all OK.
`Log::info`, `Str::slug`, `Arr::only`, `Package::active` settled as before; `Package::activatte` still
flagged; `MissingGateway` keeps M10's wording; lexical noise still ignored; no vendor source anywhere.

## 9 · Determinism

`experiment-11` 5× JSON, 5× Markdown, 5× at a drop-forcing budget, and M7's diff 5× at budget 500 —
stdout, stderr, ordering, token counts and `dropped[]` byte-identical throughout.

## 10 · Full suite

| | M10 | M11 | Δ |
|---|---|---|---|
| tests | 993 | **1001** | +8 — exactly `NewFileExtractionTest` |
| assertions | 4516 | **4528** | +12 — same file |
| failures | 4 | **4** | unchanged Phase 0 gate |

## 11 · Remaining gaps

| Gap | Status |
|---|---|
| **G1** · model surface never fires on `Model::method()` | open — ADR-A003 gate |
| **G2 / G5** | closed as unresolvable (A016) / open extraction question |
| **Cross-file duplication** — a collaborator the same diff also changed | **new**, 318 tokens on M7, unkeyed |
| **Missing import in a created file** no longer reported | **new**, recorded in evidence-gaps with a trigger |
| **D2** · premise provenance is the file, not the member | open |
| 21 one-hop dependency-parent sites | open (A015) |

## 12 · Next

**Cross-file duplication** is now the largest remaining precision item and is the direct successor to
this one: the same ADR-A005 principle, one level out. A collaborator fetched from `routes/web.php`
should not be fetched when the same diff already shows it. It needs a key-first experiment, because
unlike the own-file case the reviewer's view of that file may be partial.

**D2** is the cheapest remaining correctness item: the `surrounding-transaction` premise points at the
file rather than the member, because a created file is one region — and M11 has just given the parser
the vocabulary to talk about created files.
