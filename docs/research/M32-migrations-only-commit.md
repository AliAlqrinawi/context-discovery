# M32 · A migrations-only commit, and the one question the engine did not ask

> **Classification: a measurement, held-out and key-first, of a commit shape the engine says
> nothing about.** `407c110` - fourteen new migration files and one line inserted into an
> existing one - scored against a nine-row key written from the diff alone before any run.
> With a readable `vendor/` the bundle is **empty at zero tokens**, with 56 stderr lines saying
> what was searched and settled; without one it is **42 flags at 840 tokens**, every one
> spurious. Seven of the nine rows are vacuous. **The headline is the one that was not:** the
> single genuine review question in the diff - a column added to a CREATE migration that may
> already have run - is a premise the catalogue has (`data-state-after-behaviour-change`), and
> this shape does not trigger it.
>
> No production file changed. Nothing was tuned. The key was locked before the engine ran.

## 1 · Objective

The second held-out commit, chosen for its shape rather than despite it. M31's `ee5a2e6` had
D1's shape - a new entity across twenty-one files. This one has almost nothing to discover, and a
key for it is mostly OMIT. A mostly-vacuous result on such a commit is a real finding about what
the engine does when there is nothing to fetch, not a weak measurement.

## 2 · The commit

`407c110` - *feat: add all database migrations (15 app tables)* - the second commit in the
repository's history after the skeleton. Fourteen new files of identical shape (`return new
class extends Migration` with `up()` calling `Schema::create` on a `Blueprint` closure and
`down()` calling `Schema::dropIfExists`), and one line inserted into
`0001_01_01_000000_create_users_table.php`: `$table->string('role', 20)->default('admin');`.
402 insertions. **The diff references no class the project owns.** Named in no ADR, write-up,
key, README, TSV or analysis - only in the M18/M24 census files, whose output for it was not
opened.

## 3 · The key

`held-out-407c110`, nine rows: **1 FETCH · 1 FLAG · 7 OMIT**. Written on 2026-09-23 from the
diff and, for the one changed line in an existing file, `app/Models/User.php` at the commit and a
search of the tree for any reader of `role` (none in `app/`, `database/`, `routes/` or `tests/`;
only `API_SPEC.md`, `ARCHITECTURE.md` and the spatie config). **No later commit is cited.** The
premise catalogue's identifiers were read from engine source for transcription. It lives in the
backend repository (`docs/keys/held-out-407c110.json`, backend commit `8a020f5`), imported with
`runs_existing_at_write = []` - zero runs at the head, checked - and locked at import.

| Row | Expected | Reference |
|---|---|---|
| **M.1** | FLAG | `data-state-after-behaviour-change`: `role` inserted into the users CREATE migration, which has already run wherever the application was ever migrated; the column will never be added there, and no file says whether such a database exists (X3) |
| **M.2** | FETCH | `App\Models\User` → `app/Models/User.php`: `role` can take a non-default value only by mass assignment, and `$fillable` is `['name', 'email', 'password']` |
| M.3 | OMIT | `schema-index-support`: foreign keys are `constrained()`, uniques declared, nothing queries these tables yet |
| M.4 | OMIT | `Schema::create / ::dropIfExists / ::table` - framework-known |
| M.5 | OMIT | `Migration`, `Blueprint` - dependency classes in type positions |
| M.6 | OMIT | the anonymous `new class extends Migration` and its `up`/`down` - same-file siblings in a class with no name |
| M.7 | OMIT | the users migration's context lines |
| M.8 | OMIT | table and column string literals, comments |
| M.9 | OMIT | `<?php` |

**Five hedges, declared in advance inside `why`:**

| Row | The doubt |
|---|---|
| M.1 | the commit is the second in history, so "no database has run it yet" is the likely truth; the key states what needs raising, not what the author probably knew |
| M.2 | the strongest hedge in the key: the diff names no class, a column name is not a reference, a stricter reading says OMIT; written FETCH because the question is real, **a miss expected** |
| M.3 | `quote_requests.status` and `dishes.is_featured` will be filtered on without an index - a question for the commit that adds the query |
| M.6 | unknown what an extractor does with an anonymous class; a symbol-absence claim on `up`, `down` or `class` lands here - **the row watching for M31 §5.2's PersonalitySeeder shape** |
| M.7 | whether a hunk's context lines count as the changed region |

## 4 · Result

Engine `v0.2.0` (`e7c919d`), budget 8000, `--caller-scope app/`, `--max-call-sites 20`, PHP
8.4.6, the commit's own `composer.lock` under `installed`. Both runs **HELD-OUT** and
**KEY-FIRST**.

| | `installed` **0 · 0** | `none` 42 · 840 |
|---|---:|---:|
| assertions emitted / TP / FP / unkeyed | 0 / 0 / 0 / 0 | 42 / 0 / 42 / 0 |
| assertion precision TP/(TP+FP) | **n/a** (0/0) | 0/42 = 0.0% |
| key recall (M.1 + M.2) | 0/2 · both missed | 0/2 |
| OMIT rows violated / vacuous | **0 / 7** | 2 (M.4, M.5) / 5 |
| items | none | 42 flags, all noise, 840 tokens |
| stderr | 56 lines | 56 lines |

### 4.1 · The empty bundle is a searched one

Under `installed` the engine emits nothing and says why, line by line: 14 × *framework
reference: `Schema::create` declared at vendor/…/Facades/Schema.php:34*, 14 × the same for
`::dropIfExists`, 14 × *dependency class: `Blueprint` provided by vendor/…; surface not
fetched*, 14 × *new file … own-file context is in the diff, not fetched*. Every reference the
diff makes is the framework's, every file is new, and ADR-A011/A012/A013 settle each with a
diagnostic and no item. "Searched, found none" is distinguishable from "never searched"
(freeze review 05), and it is the former.

Under `none` the same 42 references - three per new migration - become 42 `named_reference /
flagged` items, one 20-token *"could not be resolved on disk"* each, every one matched to M.4 or
M.5 by exact subject. **The 42 → 0 collapse is M29's fourth confirmation, and its purest**: a
diff that references only the framework produces, with the map, an empty bundle at zero
tokens, and without it, 840 tokens of nothing.

### 4.2 · M.1 - the headline: the one question went unstated

No `unverifiable_premise` assertion appears under either vendor mode. The one thing in this diff
a reviewer needs raised - that a column inserted into an already-run CREATE migration will never
reach a migrated database - is `data-state-after-behaviour-change`, a premise the catalogue has
(ADR-A009). It was not raised.

The mechanism is visible in the record: the users hunk produced **no assertion at all**, even
under `none`. Its changed region is one line, `$table->string('role', 20)->default('admin');`,
which names nothing static, and the extractors work from named references; the premise's
trigger is evidently not "a line added to a migration". The engine did not get the premise
wrong; it never reached the point of asking. That is the finding, and it is recorded here for the
engine's own gate: the catalogue has the word, and this shape is not one that says it.

### 4.3 · M.2 - missed as predicted

No subject in either bundle could reach `App\Models\User`; the diff carries no reference to
it. The same boundary as M31's H.5 (`PageContent`, outside the hunk): the reviewer's question is
real and off-diff, and the extractor has no claim to it. Expected, declared, and confirmed.

### 4.4 · M.6 - vacuous; the PersonalitySeeder shape did not recur

No `same_file_symbol_absence` or `same_file_reference` claim on `up`, `down`, `class` or
anything else in any of the fourteen anonymous classes, under either mode. M31 §5.2's apparent
false positive - `PersonalitySeeder` claimed absent from `DatabaseSeeder` - **stays a one-off.**

The shapes differ, which is why this was a test and not a repetition: `DatabaseSeeder` is a
named class in a namespace that references a same-namespace sibling by `::class` without a
`use`; the migrations are anonymous classes in the global namespace that reference nothing of
their own. The claim that misfired in M31 needs a symbol to claim; these files offer none. A
third look, on a namespaced file that references a same-namespace sibling, is what would settle
it.

### 4.5 · The hedges

None resolved against itself. M.1's doubt was about whether the premise *should* be raised for a
second-commit repository - moot, since it was not raised at all. M.2 missed as declared. M.3 and
M.6 vacuous. M.7 vacuous, and informatively so: the context lines of the users hunk produced
nothing, so the extractor confines itself to the changed line.

## 5 · Stated plainly

Under `installed`, **seven of nine rows are vacuous and the other two are missed**: the bundle
carries nothing, so nothing in the key could be satisfied, violated or matched. Under `none`,
five vacuous, two violated by the vendor artefact, two missed. M.3, M.6, M.7, M.8 and M.9 were
never tested in either mode.

**A migrations-only commit is a shape the engine says nothing about.** With a readable `vendor/`
that silence is correct for every OMIT row the key wrote - seven of them - and costs the one FLAG
that would have been worth saying. Assertion precision is undefined (0/0), key recall is 0/2, and
neither number is the result; the result is that the engine's silence on this shape is complete,
accounted for on stderr, and one premise short.

Also recorded, not proposed: `extends Migration` is never named as a reference in any of the
fourteen files - M.5 listed it, only `Blueprint` appeared. Consistent with ADR-A010, and worth
knowing when reading M31 §4.1.

## 6 · What this does not show

- **n = 1**, of a shape deliberately chosen to be empty. No number here transfers to a commit
  that references project code.
- **Key recall 0/2 is two rows the engine could not reach by construction** - one outside the
  diff's references, one a premise this shape does not trigger. It says nothing about the
  premise's behaviour on the shapes that do trigger it.
- **The M.6 result rules out one shape, not the false positive.** The M31 claim is untested on
  the shape that produced it.

## 7 · Regression

`git status src/ bin/` empty - **no production file changed.** Engine suite 1196. Backend key
commit `8a020f5`; both runs and scores are in the backend's tables.

## 8 · Remaining gaps

1. **What triggers `data-state-after-behaviour-change`.** The premise exists; an edited migration
   does not reach it. Which shapes do is a question for the engine's tests, not for a key.
2. **M31 §5.2's false positive** still wants a third look on its own shape: a namespaced file
   referencing a same-namespace sibling by `::class`.
3. **A commit that mixes migrations with code** - the shape a key like this one and a key like
   M31's would both have rows for - is the natural third held-out commit.

---

## Addendum · 2026-09-26 · unchanged under option B

**Source:** [M33](M33-option-b-measured.md). The text above is unaltered.

Option B (engine `6a77cdb`) re-run on `407c110`, both vendor modes: byte-identical bundles to
§4 (0 items / 0 tokens; 42 flags / 840 tokens) and byte-identical scores. The diff's regions
contain no `$this->m(` to an undeclared member, so the fourth form does not fire. The control
the change should not have touched, and did not.
