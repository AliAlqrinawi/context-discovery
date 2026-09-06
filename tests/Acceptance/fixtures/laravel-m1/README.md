# M1 · Laravel Acceptance Fixture

- **Status:** Fixture complete. **No implementation performed.** Laravel Framework Knowledge is not built.
- **Milestone:** M1, following [`docs/research/M0-laravel-framework-knowledge.md`](../../../../docs/research/M0-laravel-framework-knowledge.md)
- **Baseline:** `context-discovery` at `v0.1.0` (`212ea91`) — `src/`, `bin/` and `composer.json` untouched
- **Purpose:** record what the CLI *does* today and what it *should* do, as two separate, checkable things, so the next architectural decision is made against evidence

---

## 1 · Fixture structure

```
tests/Acceptance/fixtures/laravel-m1/
├── README.md                  this document
├── answer-key.json            the hand-written key — authoritative, read by the harness
├── answer-key.md              a generated projection of the key, for reading
├── baseline-v0.1.0.json       what v0.1.0 actually produces, measured, for all 20 runs
├── .gitignore                 vendor/ is never committed; composer.lock is
├── diffs/                     10 unified diffs, one per scenario
│   └── S01…S10-*.diff
├── repo-a-app-map/            variant A — the application before `composer install`
│   ├── composer.json          psr-4: App\ only
│   └── app/                   6 files
├── repo-b-vendor-map/         variant B — the same application, framework roots mapped by hand
│   ├── composer.json          psr-4: App\ + the framework's own roots
│   ├── composer.lock          committed (P8)
│   ├── vendor/                gitignored; produced by `composer install`
│   └── app/                   byte-identical to variant A's, asserted by the harness
└── repo-c-vendor-installed/   variant C — the realistic application *after* `composer install`
    ├── composer.json          psr-4: App\ only, exactly like variant A
    ├── composer.lock          committed (P8)
    ├── vendor/                gitignored; the framework's namespaces live only in its generated map
    └── app/                   byte-identical to variant A's, asserted by the harness
```

The harness is [`tests/Acceptance/LaravelFixtureBaselineTest.php`](../../LaravelFixtureBaselineTest.php).

**The variants differ only in `composer.json` and in whether `vendor/` is installed.** That is
asserted, not assumed: `testBothVariantsShipTheSameApplicationSource()` compares an md5 digest of
every file under the `app/` trees. It is what makes any difference between them attributable to the
PSR-4 map and to nothing else.

| Variant | own `composer.json` map | `vendor/` | What it isolates |
|---|---|---|---|
| **A** `repo-a-app-map` | `App\` only — what `laravel/laravel` ships | **absent** | problem **A**; the bare-checkout environment |
| **B** `repo-b-vendor-map` | `App\` + the framework's roots, added by hand | present | problem **A** removed, so problem **B** is visible alone |
| **C** `repo-c-vendor-installed` | `App\` only | present | the **realistic** environment — the framework is on disk and only Composer's generated map knows where. Added by M6 |

### Why variant C exists

Neither A nor B is the case a real reviewer runs against. A is the application *before*
`composer install`; B has had its map widened by hand, which no real application does. C is the
application afterwards: `composer.json` still declares `App\` and nothing else, and the framework's
namespaces exist only in `vendor/composer/autoload_psr4.php`. Until
[ADR-A014](../../../../../context-discovery-architecture/architecture/decisions/ADR-A014-composer-generated-map-as-location-metadata.md)
the tool never read that file, so C behaved exactly like A with the framework sitting unused on disk.

### Why variant B exists, and what it is not

Variant B declares the framework's own PSR-4 roots in the **fixture application's own
`composer.json`** — ordinary tool input, exactly like the four Phase 0 fixtures' `composer.json`
files. `ComposerPsr4ClassLocator` was **not** widened; M0 proposal S1 is not implemented. Variant B
is a *simulation* of S1 having landed, and its only job is to remove M0 problem A so that problem B
can be observed on its own.

Its map is transcribed from Composer's real generated map for this exact install
(`repo-b-vendor-map/vendor/composer/autoload_psr4.php`):

```php
'Illuminate\\Support\\' => array(… /Macroable, … /Collections, … /Conditionable, … /Reflection),
'Illuminate\\'          => array(… /laravel/framework/src/Illuminate),
```

Both rows matter. `Illuminate\Support\Arr` lives under `Collections/`, and `Illuminate\Support\Str`
lives under none of the four, so it is found only by falling back to the shorter `Illuminate\`
prefix. Scenario **S06** exists to exercise exactly that (M0 §4.7). A single-path shortcut map would
pass S06.1 and fail S06.2, which is why the fixture uses the real shape.

*(Composer's generated file lists each `Illuminate\` row twice in this fixture, because the fixture's
own `composer.json` contributes the same roots the framework's does. Harmless — the CLI never reads
Composer's generated file today; it reads `composer.json`.)*

---

## 2 · Exact Laravel version

**`laravel/framework` v12.64.0**, pinned exactly (not `^12.0`) in `repo-b-vendor-map/composer.json`,
with `composer.lock` committed. This is the same tree M0 used as its primary evidence, so every
file-and-line citation in the answer key resolves inside this fixture.

PHP: **8.4.6** on the machine that recorded the baseline; the fixture requires `^8.2`.

### Preparing variant B

```
composer install --no-interaction --no-scripts \
  --working-dir tests/Acceptance/fixtures/laravel-m1/repo-b-vendor-map
```

~35 MB, 73 packages. **The framework source is deliberately not committed.** A vendored copy could
drift from what Composer actually installs, and the fixture's whole value is that it demonstrates
real Laravel behaviour — so the tree is gitignored and the version is pinned instead.

**Variant A needs no `vendor/` at all** and runs on a clean checkout: nothing outside `App\` is
placeable in variant A either way, so the framework source could not change its result. On a clean
checkout the harness therefore runs 34 tests and skips variant B's 20 with the command above.
Nothing is silently degraded, and nothing is faked.

---

## 3 · Scenarios

Ten scenarios, 23 answer-key rows. Every one is here because M0 named it; none is here for coverage.

| # | Scenario | What it establishes | M0 |
|---|---|---|---|
| **S01** | `facade-static-call` | `Log::info`, `DB::transaction` — facade proxy through the container, with a declared `@method static` surface | C3, RQ1 |
| **S02** | `eloquent-static-create` | `Package::create` — Eloquent static forwarding on a class the app's own map always places | C5 |
| **S03** | `eloquent-static-and-chain` | `Package::where` (builder-provided), `Package::orderBy` (`@mixin` → query builder), `->get()` (chain, invisible), `Collection` return type | C2, C5 |
| **S04** | `relation-and-instance` | `$package->features()->createMany()`, `$package->fresh()` — relation-provided and inherited, both blocked by the receiver's type | C1, C8 |
| **S05** | `inherited-static` | `Package::query` — a **real declared** static on `Model`; plain PHP inheritance, no magic at all | C1 |
| **S06** | `declared-framework-static` | `Str::slug`, `Arr::only` — framework methods declared in their own files; pure problem A | (locator) |
| **S07** | `project-local-lookalikes` | `Registry::create` (project static that looks Eloquent), `Package::active` (scope convention → **application** code) | C4, RQ8 |
| **S08** | `genuinely-unresolved` | `MissingGateway::resolve` (class absent), `Package::activatte` (typo) — must stay flagged | R1 |
| **S09** | `comments-strings-docblocks` | Laravel names in a `//` comment, a docblock `@see`, three string literals, and `Log::class` | R9 |
| **S10** | `missing-import-absence` | `Log::info` with **no import** — Experiment 1's headline finding | R2 |

### Mapping to the brief's required experiment matrix

| Required | Covered by | Note |
|---|---|---|
| 1 · framework class outside the `App\` map | S01, S03.4, S06 in variant **A** | |
| 2 · class reachable in vendor, member dynamically supplied | S01, S03.4 in variant **B** | the flag survives the map being fixed |
| 3 · framework method directly declared / inherited, statically resolvable | S06 (declared in its own file), S05 (inherited from `Model`) | split deliberately — they fail for different reasons |
| 4 · project model using a Laravel-provided method | S02, S03.1, S03.2 | identical in A and B, so map-independent |
| 5 · project method resembling a Laravel method | S07.1 (`Registry::create`), S07.2 (`Package::active`) | two different kinds of look-alike |
| 6 · unknown class / member | S08.1 (class), S08.2 (member) | both must keep flagging |
| 7 · comment / string / docblock false positives | S09 (four rows) | |

Two additions beyond the required matrix, both earned by M0 rather than by symmetry:

- **S05 as its own scenario.** The brief groups "directly declared/inherited". M0 shows these are
  different problems: a declared method fails only because of the map (S06), while an inherited one
  fails in *both* variants because the CLI does not follow `extends` (S05). Merging them would hide
  M0 rule **L0** behind rule **L1** and would make open question **OQ1** untestable.
- **S10.** Not in the required matrix, but M0 rates the risk of destroying it **Critical** (R2). A
  fixture for framework knowledge that cannot detect the destruction of Experiment 1's headline
  finding is not a regression net.

---

## 4 · Diff inputs

Ten unified diffs under `diffs/`. Each **adds one method to a file that already exists on disk**,
rather than creating a file. That is deliberate: a whole-file diff makes the entire file one changed
region, which in v0.1.0 triggers two unrelated artefacts M0 recorded as defects **D1** and **D2**
(§4.8 there). Using modification diffs keeps those artefacts out of this fixture's baseline, so a
Laravel signal is never confused with a diff-shape signal.

The diffs were generated mechanically — final file, minus the one method, diffed against the final
file — so line numbers agree with the on-disk source the CLI reads. Example:

```
diff --git a/app/Repositories/PackageRepository.php b/app/Repositories/PackageRepository.php
--- a/app/Repositories/PackageRepository.php
+++ b/app/Repositories/PackageRepository.php
@@ -65,4 +65,10 @@
         return Registry::create(['catering' => 'Catering']);
     }
 
+    public function reconcile(string $reference): void
+    {
+        MissingGateway::resolve($reference);
+
+        Package::activatte($reference);
+    }
 }
```

One fixture detail worth recording, because it cost a rebuild: the unknown-class case was first
written as `MissingGateway::for($reference)`. `for` is a PHP keyword and tokenises as `T_FOR`, not
`T_STRING`, so the extractor formed no subject at all and the scenario silently tested nothing. It
is now `::resolve(`. Any future fixture author choosing member names should avoid reserved words.

---

## 5 · Hand-written answer key

[`answer-key.json`](answer-key.json) — authoritative, read by the harness.
[`answer-key.md`](answer-key.md) — the same content, rendered for reading.

Every row carries: `input`, `subject`, `classification`, `mechanism`, `defined_at` (a file and line
in this fixture's own vendor tree), `evidence`, `expected` (item / flag / diagnostic), `current`
(per variant), `gap` (per variant), and `why`.

### The one design rule that matters

> `expected` states a **semantic** outcome and deliberately does **not** commit to a bundle
> representation.

`expected.item` is one of `none` / `fetched` / `flagged`. It says whether an item should exist and
under which lever. It does not say whether a framework fact is *also* represented somewhere — M0
argues that a stderr diagnostic suffices and needs no schema change, but records the alternative as
open question **OQ3**. The key must not pre-empt that decision, so it does not.

### Classification census

| Classification | Rows |
|---|---|
| `framework_known` | 12 |
| `project_local` | 5 |
| `not_a_reference` | 4 |
| `unresolved` | 2 |

One coherence rule is asserted by the harness rather than left to review: **a flag is expected if
and only if the classification is `unresolved`.** That is ADR-A009's *"a premise exists only where an
unverified premise exists"*, written as a test over the key itself.

---

## 6 · Current v0.1.0 output

Measured, recorded in [`baseline-v0.1.0.json`](baseline-v0.1.0.json), and asserted on every run.

| Variant | Scenario | items | fetched | flagged | tokens |
|---|---|---:|---:|---:|---:|
| A | S01 facade-static-call | 2 | 0 | **2** | 40 |
| A | S02 eloquent-static-create | 5 | 4 | **1** | 136 |
| A | S03 eloquent-static-and-chain | 3 | 0 | **3** | 60 |
| A | S04 relation-and-instance | 4 | 4 | 0 | 116 |
| A | S05 inherited-static | 1 | 0 | **1** | 20 |
| A | S06 declared-framework-static | 2 | 0 | **2** | 40 |
| A | S07 project-local-lookalikes | 5 | 5 | 0 | 179 |
| A | S08 genuinely-unresolved | 2 | 0 | 2 | 40 |
| A | S09 comments-strings-docblocks | 0 | 0 | 0 | 0 |
| A | S10 missing-import-absence | 1 | 1 | 0 | 29 |
| B | S01 facade-static-call | **0** | 0 | 0 | **0** |
| B | S02 eloquent-static-create | 5 | 4 | **1** | 136 |
| B | S03 eloquent-static-and-chain | 2 | 0 | **2** | 40 |
| B | S04 relation-and-instance | 4 | 4 | 0 | 116 |
| B | S05 inherited-static | 1 | 0 | **1** | 20 |
| B | S06 declared-framework-static | 0 | 0 | 0 | 0 |
| B | S07 project-local-lookalikes | 5 | 5 | 0 | 179 |
| B | S08 genuinely-unresolved | 2 | 0 | 2 | 40 |
| B | S09 comments-strings-docblocks | 0 | 0 | 0 | 0 |
| B | S10 missing-import-absence | 1 | 1 | 0 | 29 |

Bold marks a cell the answer key says is wrong.

> **Updated by M3 (2026-09-05).** Three rows changed, and only three — S07 in both variants and S01
> in variant B. Variant A now produces **11 flagged items, of which 9 are false positives**; variant
> B produces **7, of which 4 are false positives**. The 117 fetched framework slices in S03/B remain,
> because that is a bare framework class name and neither M3 rule touches it. The table above is the
> post-M3 measurement; the pre-M3 numbers are in this file's git history and in
> [ADR-A011](../../../../../context-discovery-architecture/architecture/decisions/ADR-A011-framework-known-recognition.md).

---

## 7 · Expected output

The same twenty runs, as the key says they should be. Only the rows that differ from §6 are listed;
everything else is already correct and is guarded (§9).

| Variant | Scenario | Expected change from today |
|---|---|---|
| ~~A + B~~ B | ~~S01~~ | ~~both facade flags gone; two diagnostics instead~~ **done in B by M3.** Variant A still flags: the class is unplaceable there, and the Composer map was deliberately not widened |
| A + B | S02 | `Package::create` flag gone; the **four model-surface slices stay** |
| A + B | S03 | `Package::where` and `Package::orderBy` flags gone; `->get()` stays absent |
| A | S03 | `Illuminate\Support\Collection` flag gone |
| ~~B~~ | ~~S03~~ | ~~**115 fetched framework slices gone** — 13 426 tokens → ~0~~ **done by M4**: 117 items / 13,426 tokens → 2 items / 40 tokens |
| A + B | S05 | `Package::query` flag gone |
| A | S06 | both flags gone |
| ~~B~~ | ~~S06~~ | ~~**both fetched framework slices gone** — 397 tokens → ~0~~ **done by M5**: 2 items / 397 tokens → 0 / 0 |
| ~~A + B~~ | ~~S07~~ | ~~`Package::active` flag gone, replaced by a **fetched** slice of `Package::scopeActive`~~ **done in both variants by M3** |

Everything not in that table is expected to be **byte-identical to today**. That is the fixture's
real contribution: it is as much a list of things that must not move as a list of things that must.

---

## 8 · Gap analysis

| Gap | A | B | Meaning |
|---|---:|---:|---|
| `match` | 11 | 16 | already right; must not regress |
| `false_positive_flag` | 9 | 4 | flagged although the contract is defined and citable |
| `known_false_negative` | 3 | 3 | nothing emitted; outside the M0 minimal scope |
| `over_fetch` | 0 | **0** | framework source pulled into the bundle |

*(Post-M5. Before M3: `match` 10/10, `false_positive_flag` 10/7, `over_fetch` 0/3. M3 closed three rows, M4 a fourth, M5 the last two — `over_fetch` is now zero.)*

Four findings the fixture makes concrete:

1. **Three flags are immune to the map.** S02.1, S03.1, S03.2 name `App\Models\Package`, which the
   application's own map always places. They are flagged in both variants. Nothing about Composer
   can fix them — this is dynamic dispatch, alone and unmixed.

2. **Two more survive the map being fixed.** S01.1 and S01.2 change only their *diagnostic* between
   variants (`missing PSR-4 entry` → `unresolved named_reference`) and produce the identical flag.
   This reproduces M0 finding **F2** inside the harness.

3. **Fixing the map alone makes precision worse, not better.** Variant B is what M0 proposal S1
   would produce if it landed with no decision about what a framework reference *means*:
   - S06 turns 2 false flags (40 tokens) into 2 **fetched framework slices** (397 tokens);
   - S03.4 turns 1 false flag (20 tokens) into **115 fetched slices at 13 426 tokens** — the entire
     member surface of `Illuminate\Support\Collection`, from one return type, a **224×** increase in
     that bundle's cost.

   Experiment 2's key forbids exactly this (*"pulling the model just in case is a precision
   failure, not caution"*), and its research text says a reviewer *"that knows Laravel catches these
   unaided"*. **S1 must not ship on its own.** The harness asserts the 100× relation directly, so the
   claim cannot rot.

4. **One flag has its own answer sitting in an adjacent bundle.** S07.2 flags
   `App\Models\Package::active` as unresolvable, while S02 fetches `scopeActive` from the same file
   as part of the model surface. The resolving source is application code, one naming convention
   away. This is the row that shows *framework knowledge* and *framework code* are different things.

---

## 9 · Precision cases

Ten rows are marked `match`: v0.1.0 is already right, and framework knowledge must not take them
away. Eight of those with a formable subject are asserted individually by
`testTheGuardRowHoldsInBothVariants()`.

| Row | Must stay | Risk it guards |
|---|---|---|
| **S02.2**, **S07.3** | model / class surface **fetched** | framework knowledge suppressing a Model subclass wholesale, destroying Experiment 1's `PlaidAccount` move |
| **S07.1** | `Registry::create` **fetched** | matching on the member *name* rather than on evidence about the receiving class — M0 risk **R1** |
| **S08.1** | `MissingGateway::resolve` **flagged** | a genuinely absent class being explained away |
| **S08.2** | `Package::activatte` **flagged** | **the sharpest guard.** A typo on a class that really does extend `Model` is precisely what a careless framework layer swallows. If this stops flagging, suppression has become silence (P10) |
| **S10.1** | the missing-`Log`-import **absence** | M0 risk **R2**, severity Critical. Enforces M0's rule that framework knowledge is consulted only for `named_reference`, never for the own-file extractor |
| **S09.1–4** | nothing at all | text-driven recognition. Covers a `//` comment, a docblock including `@see Log::warning()`, three string literals and `Log::class` |

Two details in S09 are pointed rather than decorative:

- One string literal is `'Illuminate\Support\Facades\Cache::lock'` — the **literal trigger token** for
  ADR-A009's `atomic-lock-store` premise. No premise fires, so the fixture guards the premise
  catalogue against text matching as well as the extractors.
- The docblock case exists because M0 proposes reading `@method static` tags *out of docblocks*.
  S09.2 fixes the asymmetry in advance: a docblock on a **facade class** is evidence about that
  class; a docblock in **application code** is prose.

`testTheChainedFormsStillProduceNothing()` guards the opposite direction — `->get()`,
`->features()`, `->createMany()` and `->fresh()` must keep producing nothing until an experiment
earns the `->` form, because widening the extractor's closed list is an ADR-A003 change, not a fix.

---

## 10 · The Composer-map / dynamic-dispatch distinction

M0 established that the false positives have two independent causes. The fixture keeps them apart
**structurally** — one repository variant each — rather than by labelling rows, and asserts the
separation in `testProblemAAndProblemBAreSeparable()`.

| | **Problem A** — PSR-4 placement | **Problem B** — dynamic dispatch |
|---|---|---|
| Symptom | `missing PSR-4 entry: …` | `unresolved named_reference: …` |
| Cause | the class name has no prefix in the application's `composer.json` | the member is not a `function` in the class's own file |
| Is it a Laravel problem? | **No** — Composer. It fails identically for any package, and for PHP globals | **Yes** — `__callStatic`, facade proxy, `@mixin`, scope convention |
| Isolated by | variant **A**, where it is present | variant **B**, where it has been removed |
| Rows that are *only* A | S06.1, S06.2 | — |
| Rows that are *only* B | — | S02.1, S03.1, S03.2, S05.1, S07.2 |
| Rows that are **both** | S01.1, S01.2, S03.4 (A first, B underneath) | |

The third row of that table is the one to read twice: **five rows are pure problem B**, and they
name `App\Models\Package` — a class the application maps itself. No amount of Composer work touches
them.

The harness asserts three facts, so the distinction cannot be quietly merged later:

1. `S02`'s flagged subjects are **identical** in A and B — dispatch is map-independent;
2. `S01`'s flagged subjects are **identical** in A and B — fixing the map does not resolve a facade;
3. `S06` flags in A and resolves in B — that row, and only that row's kind, is pure problem A.

---

## 11 · Reproduction commands

```bash
# 0 · prepare variant B (variant A needs nothing)
composer install --no-interaction --no-scripts \
  --working-dir tests/Acceptance/fixtures/laravel-m1/repo-b-vendor-map

# 1 · one scenario, one variant
php bin/context-discover \
  --diff tests/Acceptance/fixtures/laravel-m1/diffs/S02-eloquent-static-create.diff \
  --repo tests/Acceptance/fixtures/laravel-m1/repo-a-app-map \
  --budget 20000 --format json

# 2 · the whole matrix, as a table
for repo in repo-a-app-map repo-b-vendor-map; do
  for d in tests/Acceptance/fixtures/laravel-m1/diffs/*.diff; do
    printf '%-18s %-32s ' "$repo" "$(basename "$d" .diff)"
    php bin/context-discover --diff "$d" \
      --repo "tests/Acceptance/fixtures/laravel-m1/$repo" \
      --budget 20000 --format json 2>/dev/null \
    | php -r '$b=json_decode(stream_get_contents(STDIN),true);
              printf("items=%-4d tokens=%d\n", count($b["items"]), $b["used_tokens"]);'
  done
done

# 3 · the harness
./vendor/bin/phpunit --testsuite Acceptance --filter LaravelFixtureBaselineTest

# 4 · everything
./vendor/bin/phpunit
```

The headline contrast, in one command each:

```bash
# variant A: one false flag, 60 tokens
php bin/context-discover --diff …/diffs/S03-eloquent-static-and-chain.diff \
  --repo …/repo-a-app-map --budget 20000 --format json | …   # items=3   tokens=60

# variant B: the map is fixed, and the bundle explodes
php bin/context-discover --diff …/diffs/S03-eloquent-static-and-chain.diff \
  --repo …/repo-b-vendor-map --budget 20000 --format json | … # items=117 tokens=13426
```

---

## 12 · Test results

| Run | Result |
|---|---|
| `--filter LaravelFixtureBaselineTest`, framework source installed | **OK — 54 tests, 506 assertions** |
| `--filter LaravelFixtureBaselineTest`, framework source absent (clean checkout) | **OK — 54 tests, 404 assertions, 20 skipped** |
| Full suite | **843 tests, 3 950 assertions, 4 failures** |

The four failures are the pre-existing Phase 0 acceptance gate (`experiment-01`…`04`), which fails
loudly by design until an operator supplies the private Laravel + Plaid fixtures. The count is
unchanged from `v0.1.0`: **789 → 843 tests is exactly this fixture's 54, and 4 → 4 failures.**

The skip path is not theoretical — it was exercised by moving `vendor/` aside and re-running.
Variant A's 34 tests passed; variant B's 20 skipped with the `composer install` command in the
message.

### The one change outside the fixture

`phpunit.xml` gained `<exclude>tests/Acceptance/fixtures</exclude>` in the Acceptance suite. Without
it PHPUnit's scanner recurses into a fixture's `vendor/`, tries to load
`symfony/service-contracts/Test/ServiceLocatorTest.php`, and **aborts the entire run** with
`Class "…ServiceLocatorTestCase" not found`.

This is a **pre-existing latent defect**, not something this fixture introduced: any operator who
supplies a real repository checkout at `tests/Acceptance/fixtures/experiment-01/repo` — which the
Phase 0 harness explicitly instructs them to do — hits the identical failure today. The M1 fixture is
simply the first thing to trip it. Fixture repositories are input to the tool under test, never test
code, so excluding them is the correct fix and it changes no test behaviour.

---

## 13 · Open architectural questions

The fixture does not answer these. It makes four of them **testable**, which was the point.

| Q | Question | The fixture's contribution |
|---|---|---|
| ~~**OQ1**~~ | ~~Is following `extends` / `@mixin` a depth-two traversal under P3/X2?~~ | **RESOLVED by [ADR-A010](../../../../../context-discovery-architecture/architecture/decisions/ADR-A010-inheritance-and-annotation-traversal.md) (M2).** Resolution opens at most **one file beyond the changed file**; `extends`, `use <Trait>` and `@mixin` are not followed, even to verify. S05 was the decision case, exactly as this fixture predicted. Rows S02.1, S03.1, S03.2 and S05.1 are therefore blocked, and their flags stand as a recorded defect rather than being fixed. The boundary is locked by `tests/Acceptance/Oq1DepthBoundaryTest.php` |
| **OQ3** | Is a stderr diagnostic enough for a framework-known reference, or must the fact enter the bundle? | The key's `expected.item` is deliberately representation-free, so either answer can be scored against these rows without rewriting the key |
| **OQ4** | What about macros? | Not in the fixture. `Builder::macro` is registered by executing a service provider, so a fixture for it would need a booted application — outside P9. **S08.2 is the stand-in**: it proves the fallback for anything unrecognised is still a flag |
| **OQ6** | Should `unresolved-reference` be split by cause? | **S08.1** shows the current wording is already imprecise: `App\Contracts\MissingGateway` reports `missing PSR-4 entry` although the `App\` prefix *is* mapped and it is the **file** that is absent. Three distinct causes share one premise and one statement |
| **new · OQ8** | What is the *correct* outcome for a bare framework class name in a type position? | **S03.4**. Today it is one flag (A) or 115 fetched slices (B). Suppression is the obvious answer, but `Illuminate\Http\Request` in a controller signature is a case where a reviewer might genuinely want the surface. No experiment settles it |
| **new · OQ9** | Should the model surface still be fetched when the model is only named as a type? | **S02.2 / S07.3**. v0.1.0 fetches the whole surface *and*, when the class also appears as `Class::member`, emits that member's slice twice. Pre-existing, unrelated to Laravel, recorded not fixed |

---

## 14 · Recommendation for the next milestone

**M2 · Decide OQ1, then implement nothing else until it is decided.**

The fixture makes the ordering clear. Five of the ten `false_positive_flag` rows in variant A
(S02.1, S03.1, S03.2, S05.1, S07.2) cannot be resolved without reading a file the reference does not
directly name — the parent class, the builder, the `@mixin` target. Whether that is permitted is
**OQ1**, and it is an architecture-repository decision (an ADR against P3/X2), not a coding task. Every
other proposal in M0 §10 sits downstream of it.

Recommended order:

1. **M2 — settle OQ1** with an ADR. S05 is the minimal test case: one real declared method, one
   `extends`, no Laravel semantics at all.
2. **M3 — the framework-known outcome (OQ3)**, using S01 as the reference case, since it needs no
   inheritance walk: a facade's `@method static` tag is a fact in one file.
3. **M4 — proposal S1 (the Composer map)** — and *not before* M3. §8 finding 3 is the reason:
   landing S1 first converts 3 cheap false flags into 117 fetched framework items and a 224× token
   increase in one bundle. The fixture will fail loudly if that is attempted in the wrong order,
   which is exactly what it is for.

Two things that should **not** be next: widening the extractor's closed form list (the `->` chain
rows S03.3, S04.1, S04.2 need an *experiment* under ADR-A003, not a patch), and anything touching
macros or the container.

> **Update (M2, 2026-09-05).** Step 1 is done: OQ1 is resolved by
> [ADR-A010](../../../../../context-discovery-architecture/architecture/decisions/ADR-A010-inheritance-and-annotation-traversal.md).
> The answer was *disallowed* — one file beyond the changed file, no inheritance or annotation
> chains — so **M3 is unblocked only for rules that need no second file**: the facade `@method
> static` tag (S01) and the `scope<Name>` convention (S07.2). The Eloquent forwarding rules that
> would close S02 and S03 stay blocked until an ADR-001-grade experiment exists.
>
> **Update (M3, 2026-09-05).** Step 2 is done:
> [ADR-A011](../../../../../context-discovery-architecture/architecture/decisions/ADR-A011-framework-known-recognition.md)
> implements both single-file rules. A framework-known reference is a successful negative — no
> bundle item, one cited diagnostic — and a local scope resolves to the project's own member and is
> fetched. Three answer-key rows closed; nothing else moved. **Step 3, the Composer map (M4), is now
> unblocked**, and §8 finding 3 is why it had to wait: landing it first would have converted cheap
> false flags into fetched framework source. The facade rule now absorbs that case, but S03.4 —
> a bare framework *class* name, which expands to 115 slices in variant B — is untouched by M3 and
> is the open precision problem M4 must answer before widening anything.

> **Update (M4, 2026-09-05).** S03.4 is closed by
> [ADR-A012](../../../../../context-discovery-architecture/architecture/decisions/ADR-A012-surface-move-is-for-project-classes.md):
> the bare-class surface move applies to the project's own classes, so a class placed inside
> Composer's dependency directory is settled from its path alone — **117 items / 13,426 tokens → 2
> items / 40 tokens**, and its file is never opened. Variant A is unchanged, because the class is
> unplaceable there. **Widening the Composer map (S1) is now safe for bare classes**; what remains
> open is the *member* case — S06's `Str::slug` and `Arr::only` still fetch their one declared
> method (397 tokens), which is a minimal slice by the spec's own definition and therefore a weaker
> case for withholding.

> **Update (M5, 2026-09-05).** The member case is closed by
> [ADR-A013](../../../../../context-discovery-architecture/architecture/decisions/ADR-A013-dependency-members-are-settled-not-fetched.md),
> on evidence M4 had not quoted: `context-types.md` type 2 is *"Named collaborator code
> (**application code**, depth one)"* and covers *"a class, model, enum, or **method**"*. So
> ownership governs members exactly as it governs classes — but only with positive evidence that the
> member is really there, or `Str::slugg()` would be silently swallowed. **S06/B: 2 items / 397
> tokens → 0 / 0.** `B:over_fetch` is now **zero**: nothing from `vendor/` reaches any bundle.
> What remains is entirely variant A — the unplaceable-class flags that only the Composer-map
> question can reach.

> **Update (M6, 2026-09-05).** Variant **C** was added and
> [ADR-A014](../../../../../context-discovery-architecture/architecture/decisions/ADR-A014-composer-generated-map-as-location-metadata.md)
> now reads Composer's generated map as *location* metadata — parsed, never executed, appended after
> the project's own so the project keeps precedence. C moves from behaving like A to behaving like B:
>
> | scenario | C before | C after |
> |---|---|---|
> | S01 `Log::info` / `DB::transaction` | 2 items, 40 tok, `missing PSR-4 entry` | **0 items, 0 tok**, tag citations |
> | S03 `Collection` | 3 items, 60 tok | **2 items, 40 tok**, dependency-class citation |
> | S06 `Str::slug` / `Arr::only` | 2 items, 40 tok | **0 items, 0 tok**, member citations |
>
> **Variants A and B are byte-identical to before**, verified across all 20 of their runs, and no
> bundle item in any of the 30 runs carries a `vendor/` provenance. Locating a dependency never makes
> it fetchable — which is exactly why M4 and M5 had to land first.

---

### Not implemented by this milestone

No Laravel knowledge interface, class, rule, or recognition of any kind. `ComposerPsr4ClassLocator`
unchanged. No new assertion kind, premise, lever, port or bundle field. `src/`, `bin/` and
`composer.json` are byte-identical to `v0.1.0`. The only file changed outside this directory and its
harness is `phpunit.xml`, for the reason in §12.
