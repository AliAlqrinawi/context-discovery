# Experiment 21 · answer key

Written **before** any reviewer ran. `answer-key.json` is the machine-readable form. Neither was
changed after scoring.

## Purpose

Establish the **baseline**: how often can an isolated reviewer identify a real defect from the diff
alone? **No bundle was generated or supplied to anyone.**

## Selection criteria, fixed before scoring

| | |
|---|---|
| **S1** | a later **real** commit fixes a behaviour present in the target |
| **S2** | ≤ 2500 changed lines — a review unit, however large. The bound deliberately keeps the two ~2350-line commits so "the defect was buried" can be separated from "the defect was invisible" |
| **S3** | controls: no later commit fixes the behaviour |
| **S4** | **no** selection on whether a defect looks easy to spot; sizes span 8 → 2375 lines on purpose |

**Excluded by S2:** `c48963d` (12,631 lines, 71 files) — a genuine STRONG pair with `b969836`, but
12k lines is not a review unit and a floor there would tell us nothing.

## Census

**15 tasks · 9 defect · 6 control · STRONG 5 · MODERATE 8 · WEAK 2.** All five STRONG tasks are
defect tasks. Keyed as **not visible in the diff**: T01, T03, C04.

## Tasks

| | Commit | Lines | Key | Strength | Fixed by | Defect |
|---|---|---:|---|---|---|---|
| T01 | `fc573d2` | 48 | YES | **STRONG** | `11c0ced` | nullable descriptions leave the update path unable to clear a field |
| T03 | `c31783d` | 22 | YES | MODERATE | `dae67ab` | adds rows to a seeder that cannot be re-run; the defect is *inherited*, so attribution is partial |
| T04 | `ec92403` | 540 | YES | **STRONG** | `04328a5`, `2c2a7b4` | (a) file written before the DB write with no cleanup; (b) `permanent_url` returns the API domain |
| T05 | `ec76dc4` | 557 | YES | **STRONG** | `dae67ab` | `PageContentSeeder` uses `create()` in a loop — not idempotent |
| T06 | `e5e48ce` | 715 | YES | **STRONG** | `1fa1f66` | raw `featured`/`signature` query strings; `"false"` is truthy |
| T07 | `e4e3f24` | 820 | YES | MODERATE | `e770086` | `scopeForPage` requires `$page`, so "all slots" is unaskable |
| T08 | `44726d0` | 961 | YES | MODERATE | `721ea3c` | three different default page sizes ship together |
| T09 | `8054003` | 2339 | YES | **STRONG** | `560b21b` | 44 `Cache::tags()` call sites that throw on the file driver production uses |
| T10 | `4c6a83b` | 2375 | YES | MODERATE | `fc573d2` | `StoreDishRequest` requires descriptions; the DTO types them non-nullable |
| C01 | `f490fb9` | 32 | **NO** | MODERATE | — | control |
| C02 | `e770086` | 22 | **NO** | MODERATE | — | control |
| C03 | `2c2a7b4` | 8 | **NO** | MODERATE | — | control, smallest task |
| C04 | `4411454` | 257 | **NO** | MODERATE | — | control; `User::$fillable` verified to contain all three mass-assigned fields |
| C05 | `cb66103` | 116 | **NO** | WEAK | — | control; sweeping later refactors touch the same files, so absence-of-fix is weaker |
| C06 | `fdf15e5` | 188 | **NO** | WEAK | — | control, same caveat |

## What this can and cannot show

It measures the **floor**. If DIFF_ONLY detects few or none of these, M20's zero is a property of the
task and the reviewer — not of the bundle — and no bundle comparison can be interpreted until that
is understood.
