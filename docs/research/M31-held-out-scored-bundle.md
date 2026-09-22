# M31 · The first held-out measurement, and a capability gap confirmed twice

> **Classification: a measurement, held-out and key-first.** One commit no record had ever
> named, `ee5a2e6` (the Personality entity), scored under two vendor modes against a 22-row key
> written from the diff alone before any run existed. Assertion precision **7/14 (50.0%)** with a
> readable `vendor/`, **7/42 (16.7%)** without; key recall **3/7** under both. **The headline is
> a miss:** `ApiResponse::success` - the controller's whole return contract, inherited through an
> abstract class - is missed here as it was on D1 (E5.4). Two independent commits, one held-out:
> the depth-one boundary at `extends` (ADR-A010) is a capability gap with a number behind it.
>
> No production file changed. Nothing was tuned. The key was locked before the engine ran.

## 1 · Objective

M30 measured D1 against its own development-set key and said what that cannot show: in-sample,
one commit, a key the engine ships. This milestone is the held-out companion the M30 §8 asked
for: a commit cited nowhere, a key written first, the same scorer, the same engine.

## 2 · The commit

Every one of the corpus's 46 commits is named in the M18 and M24 whole-corpus census files;
fourteen are named nowhere else. Of those, six touch no PHP (pipeline, Kubernetes, Postman), one
is migrations only, one is mostly `lang/`. **`ee5a2e6`** - *feat(personalities): add Personality
entity for the story page's "faces" section* - is the one with D1's shape: 21 files, +510/−4, a
new entity across model, migration, seeder, four actions, two DTOs, two form requests, a
resource, two controllers, routes and tests. Its M18 census output existed on disk throughout and
was not opened.

## 3 · The key

`held-out-ee5a2e6`, 22 rows in experiment-05's shape: **5 FETCH · 2 FLAG · 15 OMIT**. Written on
2026-09-24 from `git diff ee5a2e6^ ee5a2e6` and, as experiment-05's author did, the sources the
diff names at that commit - `ImageService`, `ApiResponse`, `ResolvesLocale`, `BaseFormRequest`,
`Controller`, `PageContent` and its migration, `config/cache.php`. The premise catalogue's
identifiers were read from engine *source* for transcription. No bundle, no census output, no run.

It lives in the backend repository (`docs/keys/held-out-ee5a2e6.json`, backend commit `9d0f767`),
never here: an engine that shipped it would be in-sample by derivation. It was imported with
`runs_existing_at_write = []` - zero runs at the head, checked - and **locked at import**, before
either run, by a mechanism added for the purpose (`locked_at`, distinct from `scored_at`, which
would have said only that a score happened).

**Disclosed:** two rows cite later commits as evidence that the defect they flag is real. H.6
(`surrounding-transaction`) cites `04328a5`, which later touches exactly
`CreatePersonalityAction.php` and `UpdatePersonalityAction.php`; H.7 (cache tagging) cites
`560b21b`, which later removes `Cache::tags()` codebase-wide. That is the E5.3 precedent -
experiment-05 cited `04328a5` the same way - and it is history, not tool output. It makes the
FLAG rows *right*; it does not make them *reachable*.

**Six hedges, declared in advance inside `why`**, never resolved toward what the tool does:

| Row | The doubt |
|---|---|
| H.5 `App\Models\PageContent` FETCH | the consumer `updateOrCreate` is sixty lines below the hunk; the changed region is an array literal naming no class |
| H.7 cache-store-supports-tagging FLAG | the catalogue has no such premise; no engine can match the row |
| H.8 `schema-index-support` OMIT | a stricter reviewer would flag the missing unique index on `name_ar` |
| H.12 `Controller`, `BaseFormRequest` OMIT | `BaseFormRequest` is borderline |
| H.17 the four admin controllers in the POST→PUT hunks OMIT | may not be inside the changed region at all |
| H.18 sibling seeders in `DatabaseSeeder` OMIT | may never be referenced |

And one row written to close a gap M30 found: **H.15**, the `Route` facade, which experiment-05
never wrote and which left five D1 assertions unkeyed.

## 4 · Result

Engine `v0.2.0` (`e7c919d`), budget 8000, `--caller-scope app/`, `--max-call-sites 20`, PHP
8.4.6, the commit's own `composer.lock` under `installed`. Both runs labelled **HELD-OUT** (the
engine ships this key neither by path nor by content) and **KEY-FIRST**.

| | `installed` 19 · 1078 | `none` 47 · 1638 |
|---|---:|---:|
| assertions emitted / TP / FP / unkeyed | 18 / 7 / 7 / 4 | 46 / 7 / 35 / 4 |
| **assertion precision** TP/(TP+FP) | 7/14 = **50.0%** | 7/42 = **16.7%** |
| TP / emitted | 38.9% | 15.2% |
| **key recall** (5 FETCH + 2 FLAG) | 3/7 = **42.9%** | 3/7 = 42.9% |
| satisfied · missed | H.1, H.2, H.6 · **H.3, H.4, H.5, H.7** | same |
| OMIT violated / vacuous | 3 / 12 | 6 / 9 |
| `named_reference` items: signal / wrong-slice / noise / unkeyed | 2 / 0 / 6 / 5 | 2 / 0 / 34 / 5 |
| `named_reference` item precision, count · tokens | 25.0% · **73.3%** | 5.6% · 32.6% |
| `unverifiable_premise` | 3/3 · 57/57 | 3/3 · 57/57 |
| **all kinds, count · tokens** | 5/12 = 41.7% · 386/531 = **72.7%** | 5/40 = 12.5% · 386/1091 = **35.4%** |
| unkeyed items | 7 · 547 tokens | 7 · 547 tokens |

The seven TP assertions are the same in both runs: `ImageService::store` from the create and
update actions, `::delete` from the delete and update actions, and the `surrounding-transaction`
premise three times - delete action, update action, repository. The two signal slices are
`ImageService::store` (285 tokens) and `::delete` (44); everything the `none` run adds is a
20-token *"could not be resolved on disk"* flag on a dependency class.

### 4.1 · H.3 - missed again: the headline

No assertion names `App\Traits\ApiResponse::success`, `::created` or `::deleted`, under either
vendor mode. The five controller methods produce nothing but `$this->success(...)`,
`$this->created(...)` and `$this->deleted(...)`; the trait is reached through the abstract
`Controller`, which is five lines of `use ApiResponse;`. On D1 the same trait, through the same
class, was E5.4 - missed under both vendor modes (M30 §5.2).

Two independent commits, one of them held-out and key-first, the same reference, the same miss.
**The depth-one boundary at `extends` is a capability gap, and it now has a held-out number
behind it.** ADR-A010 declined that traversal deliberately; ADR-A015 evaluated the trigger for
revisiting it and found it not met. This measurement is the *experiment* step of ADR-A003's gate
for that trigger and nothing more: it is recorded here for the engine's own decision, and no
change is proposed.

### 4.2 · H.7 - missed as the key predicted

`Cache::tags()` on the `database` store `config/cache.php` defaults to throws at runtime; every
read and write of the new entity goes through it; `560b21b` later removed it codebase-wide. A
real latent defect, and the catalogue has no premise for "the configured cache store supports
tagging". The row was written anyway, with the miss predicted in its `why`, and it missed. The
`Cache::tags` *references* appeared under `none` (four assertions) and landed on H.14 OMIT exactly
as the key had split them - the reference is the dependency's business, the premise is the
reviewer's.

### 4.3 · H.15 - the Route gap, closed

Under `none`: **eleven** `Route::*` assertions - `put` ×5, `prefix` ×2, `get` ×2, `post`,
`delete` - across both route files, every one matched to H.15 by exact subject, 220 noise
tokens. What D1's key left unkeyed is scored here. Under `installed` the map settles them and the
row is vacuous.

### 4.4 · The hedges

None resolved against its hedge. H.5 and H.7 missed as predicted; H.8, H.12, H.17 and H.18
were never tested - nothing referenced them, so the doubts stay on paper.

### 4.5 · vendor, a third time

50.0% against 16.7% at the assertion level; 72.7% against 35.4% token-weighted; 7 against 35
false-positive assertions, the difference being 28 dependency-class flags the map would have
placed. After experiment-29 (byte-level) and M30 (against a development-set key), this is the
third independent confirmation of M29 on a held-out key: `vendor_mode=none` measures a broken
environment.

## 5 · Two key misses, found against the author

Both stay unkeyed; the key is locked.

1. **`App\Services\ImageService`, bare.** H.1 and H.2 listed `::store` and `::delete`; the engine
   also emits a bare-class assertion from the constructor type-hint, whose five items - three
   constants and both method bodies, ADR-A020's surface fallback, 374 tokens - are the largest
   unkeyed block. The key's author listed the bare trait for H.4 and should have done the same
   here. A transcription miss.
2. **`PersonalitySeeder` as `same_file_symbol_absence` in `DatabaseSeeder`.** 173 tokens fetching
   the file's own `use` block and `run()`. The claim is that `PersonalitySeeder` is an absent
   symbol; it is in the same namespace as `DatabaseSeeder` and needs no import. H.18 covered the
   sibling seeders, not this claim. A key miss - and, on its face, **an apparent engine false
   positive**, recorded here for the engine's own gate. Nothing is changed.

Visible but correct by the key: `PersonalityResource::collection` flagged *"could not be
resolved"* under both modes - a same-PR class, H.11 OMIT, scored FP and noise.

## 6 · What this does not show

- **n = 1.** One commit, one engine. No direction is claimed from 50.0% against M30's 18.2%,
  or from 3/7 against 3/4. The numbers are on different keys with different authors' rows.
- **Key recall is bounded by its author** twice over: two rows the engine cannot reach by design
  (H.5 outside the hunk, H.7 without a premise) lower it, and two references the author did not
  write (§5) do not raise it.
- **The FLAG rows' evidence is history**, disclosed in §3. A key that could not cite it would
  still have written them; a reader may weigh them differently.
- **No comparison.** One engine; the run-to-run diff still waits for a second commit.

## 7 · Regression

`git status src/ bin/` empty - **no production file changed.** Engine suite 1196. Backend 103
tests, 611 assertions; commits `9d0f767` (the key) and `b9fc126` (file import, `locked_at`).

## 8 · Remaining gaps

1. **The `extends` boundary** now has a held-out miss (§4.1). Whether that meets ADR-A015's
   trigger is the engine's decision, through ADR-A003's gate.
2. **A premise for cache-store capability** does not exist (§4.2); whether it should is a
   key-first question for the catalogue.
3. **The same-namespace symbol-absence claim** (§5.2) wants a look through the engine's own tests.
4. **A second held-out commit**, by a second author, with §5's two lessons in mind: list the bare
   class beside its members, and write a row for every symbol-absence claim a new `::class` entry
   could raise.
