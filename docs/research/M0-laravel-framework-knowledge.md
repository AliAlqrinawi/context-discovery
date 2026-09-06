# M0 · Laravel Framework Knowledge — Research & Scope

- **Status:** Research complete. **No implementation performed.**
- **Milestone:** M0 of the framework-knowledge line of work
- **Date:** 2026-09-05
- **Baseline:** `context-discovery` at `v0.1.0` (`212ea91`), working tree clean, untouched
- **Governs:** nothing. This document proposes; it changes no frozen decision, no ADR, no contract.

> **Reading rule.** Every factual claim about Laravel below carries a file-and-line citation into an
> installed framework tree, and every claim about the CLI's behaviour carries a reproducible command
> and its actual output. Section 13 lists the sources. Where a claim is *inferred* rather than
> observed, it says so in the sentence.

---

## 0 · Research questions — direct answers

Each answer is a conclusion; the evidence for it is in the section named. Nothing here is a
decision — §9 and §10 are recommendations that a future milestone must gate through ADR-A003.

**RQ1 · What Laravel references commonly appear in application diffs but are not defined in the
application's own source?**
Measured, not guessed (§4.3): across 15 real commits, 45 flagged `named_reference` items, in four
groups — Laravel framework names (58 %: facades `Log`/`DB`/`Cache`/`Storage`/`Route`/`Schema`, and
types `Request`, `JsonResponse`, `UploadedFile`, `Blueprint`, `Eloquent\Collection`); **project-local
models carrying framework-provided members** (29 %: `X::where|create|updateOrCreate|first`);
third-party Composer packages (9 %); PHP built-in globals (4 %). The second group matters most
conceptually: the *class* is the application's, only the *member* is the framework's.

**RQ2 · Which of those can be resolved deterministically?**
§6.1: everything whose evidence is a file the CLI may read — `extends` / `use <Trait>`, `@mixin`
tags, facade `@method static` tags, the `scope<Name>` convention, Composer's generated PSR-4 map,
and the resolved framework version. §6.2: not the concrete class behind a facade accessor (container,
runtime), not macros, not `resolveRelationUsing` relations, not an arbitrary local variable's type,
not runtime attribute resolution. Two of the brief's own six examples (`$model->fresh(…)`,
`$model->relation()->createMany(…)`) fall on the non-deterministic side and must stay unresolved.

**RQ3 · Can Laravel knowledge be data/metadata rather than hard-coded special cases?**
Almost entirely, yes — and it must be, because a shipped catalogue is *already* wrong between two
versions installed on this machine (§4.5). The facts (which methods exist, which facades exist, what
they proxy) are read from the installed `vendor/` tree. The irreducible part is **four rules**
(§9.3), only one of which encodes procedural behaviour, each with a source citation. It is a rule
set over on-disk evidence, not a method list.

**RQ4 · How should framework knowledge interact with `NamedReferenceResolver`?**
At exactly one point, after the resolver's own lookup fails and before the pipeline flags (§9.1).
The resolver's existing steps are unchanged and the project's own file is **always tried first**, so
a project override always wins and a wrong framework answer degrades to today's flag (§6.3). The
resolver gains one collaborator behind a port; it gains no Laravel vocabulary (§7.1).

**RQ5 · How should framework-known references appear in the Context Bundle?**
They should **not appear in it at all**. They appear as one stderr diagnostic naming the declaring
path and line (§9.2). A framework-provided reference is not an unverified premise, so ADR-A009's own
rule — *"a premise exists only where an unverified premise exists"* — forbids a flag; and §4.6
forbids a fetch. "No item, one diagnostic" is the outcome freeze review 05 already established for a
lookup that ran and answered. **No bundle-schema change is required.**

**RQ6 · Fetched, compact facts, or both?**
Neither fetched nor in-bundle facts, for the minimal scope: **a compact fact on stderr**. Fetching is
refuted by Experiment 2, which contains ~12 `Log::info` calls and one `fresh()` and whose key demands
an almost-empty bundle, with the research stating a reviewer "that knows Laravel catches these
unaided" (§4.6). Putting facts *in* the bundle is a `bundle_version` change and is recorded as a
**proposed change only**, contingent on a scored run (OQ3).

**RQ7 · What happens when a Laravel version changes the API?**
Mostly nothing, by construction — facts are read from the installed tree, so they track the version
(§4.5, §9.4). The measured drift between v12.64.0 and v13.26.1 hit exactly the things a catalogue
would encode (4 changed `@method` signatures on `Log`; `Model::__call`'s short-circuit list grew by
4) and **not** the four rules, which held across both. The rules carry a verified major range read
from `vendor/composer/installed.php`; outside it the provider returns nothing and the tool degrades
to v0.1.0 flagging (P10).

**RQ8 · Project macros, extensions, overrides, custom framework behaviour?**
Three different cases (§6.2, §6.3, R8). (i) **Overrides** need no handling: the project's own file is
checked first, unconditionally. (ii) **Convention-named project members** (`scopeForPage`) are
project-local and should be *fetched* — the framework supplies only the naming rule (§3.2). (iii)
**Macros** (`Builder::macro`, `Macroable`) are registered by executing code at boot and are checked
by `Eloquent\Builder::__call` *before* everything else, so no file settles them: they must keep the
existing flag. Whether a macro-heavy project ever warrants opt-in metadata is left open (OQ4).

**RQ9 · How do we prevent framework knowledge from causing false positives?**
Five guards, all stated as constraints rather than intentions (§6.3, §12): (1) a verdict is issued
only on **positive evidence** — a parsed tag or a real declared method found on disk — never on a
name match; (2) the project's own file always wins; (3) a wrong redirect finds nothing and falls
back to today's flag, so the failure mode *is* the current behaviour; (4) every suppression emits a
stderr diagnostic, so nothing is silent (P10); (5) framework knowledge is consulted **only** for
`NamedReference` and never for `SameFileSymbolAbsence`, which is what protects Experiment 1's
missing-`Log`-import finding (R2, the critical risk). Experiment 2 gaining zero items is the
acceptance condition (R7).

**RQ10 · What is the smallest useful Laravel knowledge scope for the next milestone?**
Six items, §10.1: **S1** widen `ClassLocator` to Composer's generated map (this is Composer
knowledge, not Laravel, and should be decided separately); **S2** facade `@method static`
recognition; **S3** the `scope<Name>` redirect to a *project-local, fetched* member; **S4** the
Eloquent static redirect to `Eloquent\Builder` and its `@mixin`; **S5** one stderr diagnostic per
framework verdict; **S6** one boundary test. Explicitly out: relation-chain typing, local-variable
type inference, `->`-chain recognition, macros, container bindings, PHP globals, and any fetching of
framework source (§10.2).

---

## 1 · Problem statement

The Context Discovery CLI resolves a *named reference* by placing its class through the repository's
PSR-4 map and slicing the named member out of that class's file
(`01-architecture.md` §3.3; `src/Discovery/Resolution/NamedReferenceResolver.php`). Where that
lookup fails, P10 requires the concern be stated rather than dropped, so the pipeline emits the
`unresolved-reference` premise — *"ASSUMPTION: named reference could not be resolved on disk;
contract unverified"* ([ADR-A009](../architecture/decisions/ADR-A009-premise-catalogue.md)).

That mechanism is correct for its designed case: a reference whose contract genuinely cannot be
checked. It is **wrong** for a reference whose contract is defined by the framework rather than by
the application, because such a reference is neither missing nor unverifiable — it is simply defined
somewhere the current lookup does not look.

In a Laravel application this is not a corner case. Measured across the last fifteen commits of a
real Laravel 12 application (§4.3), **every single `named_reference` flag the tool produced was a
false positive** — 45 of them, in 131 bundle items. The tool's most precision-sensitive output is
also its least accurate one on its declared target platform (`README.md`: "PHP/Laravel target only").

The narrow question this milestone answers is therefore:

> Which framework-provided references can the CLI recognise **from evidence it can actually reach**,
> what should it then do with them, and where does that knowledge live so that it does not become
> Laravel logic smeared through the discovery engine?

**Framing note.** The examples in the brief are described as producing "incorrect unresolved
`named_reference` flags". That is true of four of the six. Two of them (`Model::orderBy` when
chained, `$model->relation()->createMany(...)`, `$model->fresh(...)`) produce **no assertion at
all** — a silent omission, which P10 treats as the worse failure. §3 separates the two.

---

## 2 · Existing CLI behaviour (as of v0.1.0)

Established by reading the source and by running the tool; nothing here is assumed.

### 2.1 What produces a `NamedReference` assertion

`NamedReferenceAssertionExtractor` recognises a **closed list of three cross-file forms**, each
resolved through the changed file's own `use` block
(`01-architecture.md` §3.3; `src/Discovery/Extraction/NamedReferenceAssertionExtractor.php`):

| # | Form | Subject emitted |
|---|---|---|
| 1 | `Name::member` — static or enum member access | `Fqcn::member` |
| 2 | `$this->property->method(` where the property's declared type is imported | `Fqcn::method` |
| 3 | A class name in a `new`, type, or return-type position | `Fqcn` |

Any other shape produces **no assertion**. In particular: a method reached through `->` on anything
that is not `$this->property`, and every element of a fluent chain after the first, are invisible to
the extractor.

### 2.2 What happens to the assertion

1. `LeverPolicy` asks `ClassLocator->pathFor($class)`. `null` ⇒ `Flagged` immediately
   (`src/Discovery/Lever/LeverPolicy.php`).
2. Otherwise `NamedReferenceResolver` reads that file and calls `MemberSlicer->member($text, $m)`
   for a `Fqcn::member` subject, or slices the whole member surface for a bare `Fqcn` subject.
3. An empty result is declared a **lookup failure**, never a successful negative
   (`NamedReferenceResolver::lookupRan()` returns `false` with the comment *"There is no
   successful-negative case here"*), so it becomes an `unresolved-reference` flag.

### 2.3 The two independent root causes

Both were reproduced (§4.1, §4.2). They are independent, and fixing one does not fix the other.

| Cause | Description | Diagnostic emitted |
|---|---|---|
| **A · class not locatable** | `ComposerPsr4ClassLocator` reads **only** `composer.json`'s own `autoload` / `autoload-dev` PSR-4 blocks (`src/Adapters/Autoload/ComposerPsr4ClassLocator.php`). A Laravel app's `composer.json` maps `App\`, `Database\Factories\`, `Database\Seeders\` and nothing else, so no `Illuminate\…` name can be placed | `missing PSR-4 entry: …` |
| **B · member not statically declared** | The class *is* placed, but the named member is not a `function` in that file — because Laravel provides it by inheritance, by trait, by `__callStatic`, by facade proxy, or by naming convention | `unresolved named_reference: …` |

`ClassLocator::pathFor()` itself is already correct PSR-4: longest-prefix-first with fall-through to
the next prefix when a directory does not hold the file. It is the *map* that is narrow, not the
algorithm.

### 2.4 What the CLI is not allowed to do about it

The frozen contract constrains any answer. Load-bearing constraints, verbatim in substance:

- **P3 / X2** — depth one; the engine resolves references the diff names directly and does not then
  resolve *their* references. X2's status is *"not yet"*, not *"never"* (`requirements.md`).
- **P4 / X1** — forward import-following is banned at the architecture level. Status: *"never"*.
- **P9** — inputs are free and local: the diff, changed-file text, the local filesystem, the PSR-4
  map. No index, no graph, no network.
- **P6 / X4** — the engine judges nothing.
- **P10** — fail toward flagging, never toward silence.
- **ADR-A003** — the move set is closed: four extractors, three resolvers, no registry, no
  discovery, no configuration file, no plugin system. Adding a move requires *an experiment, a
  requirement, an ADR and a `Wiring` change, in that order*.
- **ADR-A009** — flags come from a closed premise catalogue with one literal trigger each, and
  **"a premise exists only where an unverified premise exists."**
- **Bundle schema v1** — `lever` is `fetched | flagged`; `assertion_kind` is a five-value enum.

---

## 3 · Concrete Laravel examples

The six examples from the brief, each traced to where the behaviour is actually defined, in
**laravel/framework v12.64.0** (the version installed in the application these examples come from —
§4.5). Line numbers are from that tree; §13 gives paths.

| # | Example | Defined where | Mechanism | v0.1.0 outcome |
|---|---|---|---|---|
| 1 | `Model::create($data)` | `Eloquent\Builder::create()` — `Builder.php:1216` | `Model::__callStatic` (`Model.php:2554`) → `(new static)->$method(...)` → `Model::__call` (`Model.php:2529`) → `forwardCallTo($this->newQuery(), …)` (`Model.php:2544`) | **flag** (cause B) |
| 2 | `Model::where(...)` | `Eloquent\Builder::where()` — `Builder.php:352` | same chain as #1 | **flag** (cause B) |
| 3 | `Model::orderBy(...)` | `Query\Builder::orderBy()` — `Query/Builder.php:2918` | chain as #1, then `Eloquent\Builder::__call` → `$passthru` / `forwardCallTo($this->query, …)` (`Builder.php:2236`, `:2240`); declared statically as `@mixin \Illuminate\Database\Query\Builder` (`Builder.php:33`) | **flag** if head of a static chain; **silent** if reached through `->` |
| 4 | `$model->fresh(...)` | `Eloquent\Model::fresh()` — `Model.php:1838` | an ordinary inherited public method — no magic at all | **silent** (form not recognised) |
| 5 | `$model->relation()->createMany(...)` | `Relations\HasOneOrMany::createMany()` — `HasOneOrMany.php:438`, inherited by `HasMany` (`HasMany.php:13`) | project-local `relation()` returns `HasMany`; `createMany` is inherited | **silent** (form not recognised) |
| 6 | `Log::info(...)` | proxied: `Facade::__callStatic` (`Facade.php:355`) → `getFacadeRoot()` (`Facade.php:209`) → `resolveFacadeInstance($name)` from the **container** (`Facade.php:232`); accessor is the string `'log'` (`Log.php:47-50`); the surface is declared as `@method static void info(string\|\Stringable $message, array $context = [])` (`Log.php:25`) | container-resolved proxy | **flag** (cause A, then cause B — §4.2) |

### 3.1 Per-example analysis

For each: is it statically determinable, what would help a reviewer, and how should the CLI classify
it? Classification uses the brief's own four options (a) project-local, (b) framework-known,
(c) unresolved/flagged, (d) needs additional metadata.

**#1–#2 · `Model::create` / `Model::where` — statically determinable via one declared rule.**
The dispatch chain is readable in framework source but is *procedural*, not declarative: no
annotation says "`Model` forwards static calls to `Eloquent\Builder`". Recognising it requires the
CLI to hold that as an explicit, cited rule. Once held, the target member resolves with the existing
machinery and no new I/O — verified in §4.4: the shipped `TokenizerMemberSlicer` finds
`Builder::create` at 1210–1221 and `Builder::where` at 343–365.
*Useful to a reviewer:* almost nothing about `create()` itself, which any Laravel reviewer knows.
What is useful is the **model's** `$fillable` / `$casts`, because `Builder::create` mass-assigns —
and the CLI already fetches that surface via form 3 when the class name also appears bare.
*Classification:* **(b) framework-known**, with the caveat that the *related* project-local fact
(`$fillable`) is already covered by an existing move.

**#3 · `Model::orderBy` — statically determinable, declaratively.**
`@mixin \Illuminate\Database\Query\Builder` (`Builder.php:33`) is a machine-readable delegation
declared in the framework's own source, present unchanged in both v12.64.0 and v13.26.1 (§4.5).
Following it needs no hand-written knowledge of method names.
*Classification:* **(b) framework-known** — *but only in the static-call form.* The chained form
(`Branch::where(...)->orderBy(...)`) is invisible to the extractor and stays **silent**; making it
visible is a change to the extractor's closed form list, which ADR-A003 gates behind an experiment.

**#4 · `$model->fresh()` — not statically determinable today.**
`fresh()` is a plain method (`Model.php:1838`); the framework side is trivial. The undecidable half
is the **receiver**: `$package` gets its type from `CateringPackage::create($data)` — a local
data-flow fact. Establishing it needs intra-procedural type inference, which is neither depth-one
nor cheap, and which no experiment has earned.
*Classification:* **(c) unresolved** — and, today, not even that: it is silent, because no assertion
is emitted. Correctly leaving it alone is the precision-preserving choice.

**#5 · `$model->relation()->createMany()` — partially determinable, at a cost.**
Three facts are needed: (i) `$package` is a `CateringPackage` — a data-flow fact, undecidable as in
#4; (ii) `CateringPackage::features()` declares `: HasMany` — a **declared return type in a
project-local file**, statically readable; (iii) `HasMany extends HasOneOrMany`, which declares
`createMany` — statically readable. Fact (i) is the blocker, and steps (ii)→(iii) are a two-hop walk
whose relationship to P3 is the open question in §11 (OQ1).
*Classification:* **(c) unresolved** for now. Promoting it needs an experiment showing a finding that
depended on a relation method's contract; none of Experiments 1–4 did.

**#6 · `Log::info()` — determinable from the framework's own docblock, not from its code.**
The runtime chain terminates in the service container, whose `'log'` binding is registered by a
service provider at boot — **not statically determinable** (Facade.php:232 reads `static::$app[$name]`).
But the framework ships the answer as data: 31 `@method static` tags on `Log.php`, one of which is
`info`. That is on disk, versioned with the vendor directory, and requires no knowledge base.
Laravel ships 44 such facade classes, and all 44 carry `@method static` tags.
*Useful to a reviewer:* nothing. Experiment 2 is explicit that a review "that knows Laravel catches
these unaided" (§4.6) — this is the single strongest piece of evidence in the corpus about what to
do with framework-known references.
*Classification:* **(b) framework-known**, from the `@method static` tag — **not** from a
hand-maintained list of logger method names.

### 3.2 A seventh example the brief did not name, and it matters most

`PageContent::forPage($page, $section)` — a real call site in the target application
(`app/Repositories/PageContentRepository.php:14`). `PageContent` declares
`public function scopeForPage(Builder $query, …)` (`app/Models/PageContent.php:20`), a Laravel
**named scope**: `Eloquent\Builder::__call` checks `hasNamedScope($method)` (`Builder.php:2232`)
before falling through to the query builder.

Running v0.1.0 on the commit that introduced that file produces, **in one bundle**:

- a `fetched` `named_reference` item whose payload *is* `scopeForPage`, lines 20–29, and
- a `flagged` `named_reference` item saying *"ASSUMPTION: named reference could not be resolved on
  disk; contract unverified"* for `App\Models\PageContent::forPage`.

The bundle contains the answer and the flag denying that the answer exists. This case is decisive
for the boundary in §7 because the resolving source is **project-local** — the framework supplies
only the *naming convention* (`scope` + `ucfirst`). It shows that "framework knowledge" and
"framework code" are different things, and that treating them as the same would send Exp 1's
model-surface fetch (`forItem`) in the wrong direction.

*Classification:* **(a) project-local reference**, reached through a framework naming convention.

---

## 4 · Research findings

All commands were run against `v0.1.0` unmodified. Full outputs are reproducible with the commands
shown.

### 4.1 Finding F1 — reproduction on a real Laravel commit

Command:

```
php bin/context-discover \
  --diff <commit 44726d0, app/Repositories/CateringPackageRepository.php> \
  --repo <abouelsid-backend> --budget 8000 --format json
```

stderr, verbatim:

```
unresolved named_reference: App\Models\CateringPackage::with in app/Repositories/CateringPackageRepository.php
missing PSR-4 entry: Illuminate\Support\Facades\DB::transaction named in app/Repositories/CateringPackageRepository.php
unresolved named_reference: App\Models\CateringPackage::create in app/Repositories/CateringPackageRepository.php
unresolved named_reference: App\Models\CateringPackage::findOrFail in app/Repositories/CateringPackageRepository.php
missing PSR-4 entry: Illuminate\Database\Eloquent\Collection named in app/Repositories/CateringPackageRepository.php
```

Five `flagged` `named_reference` items resulted, all false positives. The source under review is
ordinary, correct Laravel.

Also observed in that bundle: `$package->features()->createMany($features)` and
`$package->fresh('features')` produced **no assertion of any kind** — confirming that two of the
brief's six examples fail silently rather than by flagging.

### 4.2 Finding F2 — the two causes are independent; fixing the map alone fixes nothing

A fixture repository was built (scratchpad only; no user project modified) carrying the same PSR-4
map **plus** `"Illuminate\\": "vendor/laravel/framework/src/Illuminate/"`, with the relevant
framework files copied in. Re-running the same probe diff:

```
unresolved named_reference: Illuminate\Support\Facades\Log::info in app/Repositories/ProbeRepository.php
unresolved named_reference: App\Models\CateringPackage::create in app/Repositories/ProbeRepository.php
unresolved named_reference: App\Models\Branch::where in app/Repositories/ProbeRepository.php
```

The diagnostic for `Log::info` changed from `missing PSR-4 entry` to `unresolved named_reference`.
**The bundle is identical: three flagged items, all still false positives.** Widening the class map
is necessary and insufficient. This is the single most important negative result in this milestone,
because a map widening is the obvious first fix and it does not, on its own, remove one false flag
of the member kind.

### 4.3 Finding F3 — quantified baseline over fifteen real commits

`git log -15` of the target application, each commit run through v0.1.0 with `--budget 100000`:

| Measure | Count |
|---|---|
| Commits analysed | 15 |
| Bundle items produced | 131 |
| `flagged` items | 48 |
| `flagged` + `named_reference` items | **45** |
| Of those, manually verified as false positives | **45 / 45** |

Composition of the 45, by the subject's namespace and shape:

| Origin | `Fqcn::member` | bare `Fqcn` | Total | Share |
|---|---|---|---|---|
| Laravel framework (`Illuminate\`, `Laravel\`) | 21 | 5 | 26 | 58 % |
| Project-local model, framework-provided member (`App\Models\X::where\|create\|updateOrCreate\|first`) | 13 | 0 | 13 | 29 % |
| Third-party Composer package (`Endroid\QrCode\…`) | 1 | 3 | 4 | 9 % |
| PHP built-in global (`Closure`, `DateTimeInterface`) | 0 | 2 | 2 | 4 % |

Two structural readings follow:

1. **Cause A (class not locatable) accounts for 32 of 45 (71 %)** and is *not a Laravel problem at
   all* — it is a Composer problem. `Illuminate\`, `Endroid\` and `Laravel\Sanctum\` fail for exactly
   the same reason, and PHP's own global classes fail for a third reason (they have no PSR-4 entry
   anywhere). A Laravel knowledge layer would fix none of `Closure`, `DateTimeInterface`, or
   `Endroid\QrCode\Builder\Builder`.
2. **Cause B (member not declared) accounts for 13 of 45 (29 %) on its own**, plus an unknown
   remainder of the 21 Laravel `Fqcn::member` rows that would survive a map widening (F2 shows
   `Log::info` is one of them).

### 4.4 Finding F4 — the shipped slicer already resolves every redirect target

The v0.1.0 `TokenizerMemberSlicer` was pointed at the framework tree directly. Result:

| File | Member | Result |
|---|---|---|
| `Database/Eloquent/Model.php` | `fresh` | found, lines 1832–1848 |
| `Database/Eloquent/Model.php` | `create` | **not found** |
| `Database/Eloquent/Model.php` | `where` | **not found** |
| `Database/Eloquent/Model.php` | `__callStatic` | found, lines 2547–2561 |
| `Database/Eloquent/Builder.php` | `create` | found, lines 1210–1221 |
| `Database/Eloquent/Builder.php` | `where` | found, lines 343–365 |
| `Database/Eloquent/Builder.php` | `orderBy` | **not found** |
| `Database/Query/Builder.php` | `orderBy` | found, lines 2909–2940 |
| `Database/Eloquent/Relations/HasOneOrMany.php` | `createMany` | found, lines 432–447 |
| `Support/Facades/Log.php` | `info` | **not found** |
| `Support/Facades/Log.php` | `getFacadeAccessor` | found, lines 42–50 |

The pattern is exact: the slicer succeeds wherever a real `function` is declared and fails wherever
dispatch is magic. **No new slicing capability is required.** What is missing is only the answer to
"which class and member should be tried instead?" — a redirection, not a parser.

### 4.5 Finding F5 — version drift is real, and it hits exactly the constructs a knowledge base would encode

Two installed framework trees were compared: **v12.64.0** and **v13.26.1**.

| Construct | v12.64.0 | v13.26.1 | Stable? |
|---|---|---|---|
| `@mixin \Illuminate\Database\Query\Builder` on `Eloquent\Builder` | line 33 | line 34 | ✅ present in both |
| `Eloquent\Builder::$passthru` contents | — | — | ✅ byte-identical |
| `Log` facade `@method` tag count | 31 | 31 | ✅ same count |
| `Log` facade `@method` **signatures** | `channel(string\|null $channel = null)` | `channel(\UnitEnum\|string\|null $channel = null)` | ❌ **4 tags changed** |
| `Model::__call` short-circuit list | `['increment','decrement','incrementQuietly','decrementQuietly']` | same **plus** `incrementEach`, `decrementEach`, `incrementEachQuietly`, `decrementEachQuietly` | ❌ **grew by 4** |
| `Model.php`, `Builder.php`, `Log.php`, `HasOneOrMany.php` | — | — | ❌ all differ by checksum |

This is the empirical answer to RQ7 and it points one way: **a shipped catalogue of Laravel method
names would already be wrong between two versions the user has installed today.** Facts read out of
the installed `vendor/` tree track the version automatically; only the small set of *rules* is
version-sensitive, and those rules held across both versions.

### 4.6 Finding F6 — the research forbids fetching framework source

`docs/01-phase0/experiment-02.md` is the precision control. Its diff contains, in the research's own
words, *"roughly a dozen `Log::info` calls per account"* and *"`$candidate->fresh()` in a trace
line"* (§2). Its classification section states:

> **Diff-only:** all five real findings. … **A naked-diff review that knows Laravel catches these
> unaided.**

And its minimum-context section:

> The correct output of a context engine on this commit is an **almost-empty bundle**. That is not
> the engine failing; it is the engine being right.

The acceptance key transcribes this as: *"Precision: pulling the `PlaidAccount` model 'just in case'
fails the test."* (`tests/Acceptance/fixtures/experiment-02/expected-context.md`).

Therefore: **fetching `LogManager::info` or `Model::fresh` slices into Experiment 2's bundle would
fail the tool's own precision control**, and would do so at roughly a dozen items. Any design that
resolves framework references by *fetching* them is refuted by existing evidence before it is built.

### 4.7 Finding F7 — Composer already publishes the complete, deterministic class map

`vendor/composer/autoload_psr4.php` in the target application contains 96 PSR-4 roots, including:

```php
'Illuminate\\Support\\' => array($vendorDir.'/laravel/framework/src/Illuminate/Macroable', … ),
'Illuminate\\'          => array($vendorDir.'/laravel/framework/src/Illuminate'),
```

Notes that matter:

- It is generated by `composer install`, is byte-stable for a given `composer.lock`, is read-only, is
  inside the repository root, and requires no network — it satisfies P9 exactly as `composer.json`
  does.
- Prefix values are **arrays**, and the more specific prefix can be the *wrong* one:
  `Illuminate\Support\Facades\Log` does not live under any of the four `Illuminate\Support\`
  directories and is only found by falling through to `Illuminate\`. The shipped
  `ComposerPsr4ClassLocator::pathFor()` already implements exactly this fall-through, so no algorithm
  change is needed — only a wider map.
- `vendor/` is **gitignored** in a standard Laravel application (verified: `.gitignore:22`), while
  `composer.lock` **is** tracked. So the map exists on a developer machine and after
  `composer install` in CI, but **not** in a bare checkout. This is a hard availability constraint,
  not a detail (§12, R3).
- `vendor/composer/installed.php` and `installed.json` give the exact resolved version
  (`laravel/framework  v12.64.0`), deterministically, without executing anything.

### 4.8 Finding F8 — two adjacent defects observed, deliberately not addressed

Both are pre-existing at v0.1.0, both are unrelated to framework knowledge, and **neither was
touched**. Recorded so they are not later mistaken for framework-knowledge regressions.

- **D1 · `same_file_symbol_absence` fires on the token `php`.** Every whole-file (new-file) diff in
  the survey produced items reading *"the region uses php, which the file's use block does not
  import"*. The `<?php` opening tag is being read as an unimported symbol.
- **D2 · `surrounding-transaction` fires inside a transaction.**
  `CateringPackageRepository::create()` *is* `DB::transaction(function () { … })`, yet the premise
  fired. Observed mechanism: for a new-file diff the region spans lines 1–43, and
  `opensNoTransactionAround()` takes the **first** line in the region that has an enclosing member —
  verified to be `getAll` (line 11) — which opens no transaction, while
  `persistenceWrites()` counts writes across the *whole* region. ADR-A009's stated trigger
  ("the enclosing member does not itself open a transaction") is therefore not what the code
  evaluates for a whole-file region.

D2 is a precision failure against the catalogue's own text and should be raised as its own item;
per ADR-A003/ADR-A009 the correction belongs to the architecture repository, not to a code patch.

---

## 5 · Laravel behaviour categories

Derived from §3 and §4, ordered by how the *evidence* reaches the CLI, not by Laravel's own taxonomy.
This ordering is the point: it is what makes the boundary in §7 expressible without a Laravel method
catalogue.

| # | Category | Example | Evidence available to the CLI | Where the evidence lives |
|---|---|---|---|---|
| C1 | **Ordinary inherited method** | `Model::fresh`, `HasMany::createMany` | `class X extends Y` / `use T;` in source, then a real `function` | any placed PHP file — *language*, not framework |
| C2 | **Declared delegation** | `Eloquent\Builder` → `Query\Builder` | `@mixin \Fqcn` docblock tag | framework source, machine-readable |
| C3 | **Declared proxy surface** | `Log::info`, `DB::transaction`, `Cache::get` | `@method static <ret> <name>(…)` tags on a class extending `Support\Facades\Facade` | framework source, machine-readable, 44 facade classes |
| C4 | **Convention-named project member** | `PageContent::forPage` → `scopeForPage` | the member exists in the **project's own** file under a transformed name | project source + one naming rule |
| C5 | **Procedural static forwarding** | `Model::create`, `Model::where` | `__callStatic` → `__call` → `forwardCallTo(newQuery())`, readable but not declarative | framework source; requires an explicit cited **rule** |
| C6 | **Container-resolved binding** | the concrete class behind `'log'` | a service provider `bind`/`singleton` executed at boot | **runtime only** — not statically determinable |
| C7 | **Runtime-registered extension** | `Builder::macro(…)`, `Model::resolveRelationUsing(…)` | registration executes at boot; `Builder::__call` checks macros *before* passthru (`Builder.php:2210-2231`) | **runtime only** |
| C8 | **Receiver type from data flow** | `$package->fresh()` after `$package = X::create()` | local assignment chain | requires type inference — out of every current budget |
| C9 | **Dynamic attribute access** | `$model->official_name`, casts, accessors | `$fillable` / `$casts` / migration columns | project source; already covered by the existing model-surface fetch |

C1–C4 are **deterministic from files the CLI may read**. C5 is deterministic *given one rule*. C6–C8
are **not deterministic** and must remain flagged or silent. C9 is already handled and is out of
scope.

---

## 6 · Deterministic vs non-deterministic cases

Applying the brief's own test — *"Can the CLI know this is Laravel behaviour from evidence available
to it?"* — to every construct examined.

### 6.1 Deterministic (evidence is a file the CLI may read)

| Construct | The evidence, precisely | Category |
|---|---|---|
| `X extends Y`, `use T;` | a token sequence in a placed file | C1 |
| `@mixin \Fqcn` | a docblock tag on the class | C2 |
| `@method static … name(…)` on a `Facade` subclass | 31 tag lines on `Log.php`; 107 on `DB.php`; present on **all 44** facade subclasses | C3 |
| `scope<Ucfirst>` present where `<name>` is absent | both facts in the same project file already being read | C4 |
| Class → path for **any** installed package | `vendor/composer/autoload_psr4.php`, 96 roots | (locator) |
| Installed framework version | `vendor/composer/installed.php` → `v12.64.0` | (versioning) |
| `Model::__callStatic` forwards to a builder | `Model.php:2554` + `Model.php:2544`; requires one held rule, not inference | C5 |

### 6.2 Non-deterministic (must stay flagged or silent)

| Construct | Why no file settles it |
|---|---|
| The concrete class behind a facade accessor | `Facade::resolveFacadeInstance()` reads `static::$app[$name]` — a container lookup at runtime (`Facade.php:232`). *Note:* the `@method` tags make the concrete class unnecessary for **verifying a member exists**, which is the only question the CLI asks |
| `Builder::macro` / global `Macroable` macros | registered by executing a service provider; `Builder::__call` consults them **first** (`Builder.php:2210-2231`), so a macro can shadow anything |
| `Model::resolveRelationUsing` relations | resolved by `relationResolver()` at runtime (`Model.php:2535`) |
| The type of an arbitrary local variable | requires data-flow analysis |
| `Model::__get` attributes, accessors, casts beyond the declared arrays | attribute resolution is runtime |
| Whether a package's service provider overrode a binding | runtime |

### 6.3 The asymmetry that makes this safe

C7 (macros) *shadows* everything — so in principle no framework member lookup is ever certain. But
the failure is one-directional and benign under the design in §9, because the lookup order mirrors
the framework's own:

1. the project's own class file — **always checked first**, so a project override always wins;
2. inheritance / trait chain within placed files;
3. the framework redirect;
4. otherwise → the existing `unresolved-reference` flag.

A wrong redirect finds no member and falls through to today's behaviour. **The failure mode of the
proposal is the current behaviour**, which is the property that makes it evaluable at all.

---

## 7 · The Framework Knowledge boundary

### 7.1 The requirement

The brief's constraint is the right one: *framework knowledge must not become Laravel-specific logic
scattered through the existing discovery engine.* Restated as a testable property:

> **No class in `Domain\`, `Discovery\`, `Assembly\`, `Pipeline\` or `Cli\` (except `Cli\Wiring`) may
> name `Laravel`, `Illuminate`, `Eloquent`, `Facade`, `scope`, or any framework symbol.**

This is enforceable today: `tests/Unit/ArchitectureBoundaryTest.php` already asserts
`testWiringIsTheOnlyPlaceAConcreteAdapterIsNamed` and `testNoBannedVocabularyInIdentifiers`. One
added case makes the boundary a test, not a convention.

### 7.2 The line

| Core Context Discovery **owns** | Framework Knowledge **owns** |
|---|---|
| What the diff asserts (assertion kinds, extraction forms) | Nothing about diffs |
| The fetch-vs-flag decision (`LeverPolicy`) | Nothing about levers |
| Placing a class name on disk (`ClassLocator`) | Nothing about paths |
| Slicing a member out of file text (`MemberSlicer`) | Nothing about parsing files |
| Bundle assembly, priority, budget, drops | Nothing about output |
| The premise catalogue and its triggers | Nothing about premises |
| **Deciding what to do with a verdict** | **Producing a verdict** |

Framework Knowledge answers exactly **one question**, and holds no other capability:

> Given a `NamedReference` subject the core could not settle by looking in the class's own file,
> either (i) name another *(class, member)* the core should try with its existing machinery, or
> (ii) state that the reference is **framework-provided** and cite where that is written down, or
> (iii) say nothing.

It performs no I/O of its own, emits no bundle item, writes no premise, and never sees a `Diff`, a
`Bundle`, or a `Lever`. Its input is a string subject plus file text the core already holds; its
output is a small value object. It is a **redirector with citations**, not a resolver and not a
knowledge base of method names.

### 7.3 Why this shape and not a bigger one

- It cannot fetch, so §4.6's precision refutation cannot be reintroduced through it.
- It cannot flag, so ADR-A009's closed catalogue stays closed.
- It cannot search, so P9 and ADR-A006 are untouched.
- Because it only ever *adds* a lookup after one has already failed, it cannot change any bundle that
  v0.1.0 produces correctly — which is what makes the four acceptance keys a usable regression net.

---

## 8 · Alternatives considered

Each was tested against the evidence, not against taste. "Refuted" means an existing document or a
measured result rules it out; "rejected" means it is permissible but worse.

| # | Alternative | Verdict | Why |
|---|---|---|---|
| **A1** | **Do nothing; accept the flags** | Rejected | 45/45 false positives (F3) makes `unresolved-reference` uninformative. The research's own open question — *"Is the false-positive rate tolerable?"* (`evidence-gaps.md` §3.2) — cannot be measured through a channel this noisy |
| **A2** | **Widen `ClassLocator` to Composer's generated map only** | Necessary, insufficient | F2: the `Log::info` flag survives verbatim. Fixes the 5 Laravel + 3 third-party **bare-class** rows (8 of 45); leaves 35 `Fqcn::member` rows. Also fixes nothing for `Closure` / `DateTimeInterface` |
| **A3** | **A shipped list of known-safe Laravel method names, suppressed on match** | **Refuted** | (i) F5 shows such a list is already version-wrong between two installed versions; (ii) it suppresses on a *name*, so a project method genuinely missing named `create` would be silenced — a P10 violation with no diagnostic; (iii) ADR-A009's discipline ("a premise exists only where an unverified premise exists") requires positive evidence, and a name is not evidence |
| **A4** | **Special-case Laravel inside `NamedReferenceResolver` / the extractor** | Rejected | Directly violates §7.1 and the brief. Also makes the tool untestable on non-Laravel PHP, which `ArchitectureBoundaryTest` would then have to stop asserting |
| **A5** | **Fetch framework slices like any other reference** | **Refuted** | F6: Experiment 2 contains ~12 `Log::info` calls and one `fresh()`; its key demands an almost-empty bundle and its research text says a reviewer who knows Laravel needs none of it. This alternative fails the tool's own precision control |
| **A6** | **Full static analysis (PHPStan/Psalm-grade inference, or a reflection pass)** | Refuted | P9 (no index, no graph); ADR-A001 (zero runtime dependencies); reflection means *executing* framework code, which is neither deterministic nor read-only. It would also solve C6–C8, which no experiment has earned |
| **A7** | **Require a project-supplied framework metadata file** | Rejected as the primary mechanism | Contradicts P9's "no config file" spirit and ADR-A003's "no configuration file"; the evidence (F7) shows the facts are *already on disk*, so asking a human to restate them adds a drift surface for no gain. **Retained only as the escape hatch** for C7 (macros) — see §11 OQ4 |
| **A8** | **A third `lever` value, or a sixth `assertion_kind`, for framework-known references** | Rejected for the minimal scope | Both are `bundle_version: 1` breaking changes. §9 shows the outcome is expressible with **no** schema change, using the precedent freeze review 05 already set. Recorded in §11 (OQ3) as the fallback if a scored run shows the diagnostic stream is not enough |
| **A9** | **One `FrameworkKnowledge` port, one Laravel implementation, one call site** | **Recommended** | See §9 |

### 8.1 On "is a new port a plugin system?"

ADR-A003 excludes *"a plugin/extension registry for moves"* and the README excludes *"no plugin
system"*. A sixth port with exactly one implementation, constructed explicitly in `Cli\Wiring` with
no registry, no discovery and no configuration, is the same construction as the five existing ports —
`BundleWriter` already has two implementations under that rule. The distinguishing property is that
**the set is closed in `Wiring` and visible in one `match`**, which this preserves.

It is, nonetheless, a change ADR-A003's four-step gate governs (experiment → requirement → ADR →
`Wiring`). §10 states which experiment discharges which step. This document does **not** claim the
gate is already satisfied.

---

## 9 · Recommended architecture direction

**Not an implementation decision. A recommendation, with the evidence attached.**

### 9.1 Shape

The brief's sketch survives the evidence, with one correction — the provider sits *beside* the
resolver, consulted at one point, rather than above the core:

```
                    Pipeline\DiscoverContext
                              │
                    NamedReferenceResolver
                              │
              1. project's own class file  ──────► found → FETCH  (unchanged, always wins)
                              │ not found
                              ▼
              2. Ports\FrameworkKnowledge  ──────► Laravel implementation
                              │                     (redirect | framework-fact | nothing)
                              ├─ redirect ────────► re-enter step 1 at the named target
                              ├─ framework-fact ──► NO bundle item + one stderr diagnostic
                              └─ nothing ─────────► unresolved-reference flag  (unchanged)
```

Everything to the left of the port is framework-agnostic. Everything Laravel lives behind it.
Wiring a null implementation restores v0.1.0 byte-for-byte — which is also the regression test.

### 9.2 The three verdicts, and why the third needs no schema change

| Verdict | Bundle effect | Precedent |
|---|---|---|
| **Redirect** | whatever the redirected lookup produces — a normal `fetched` `named_reference` item, or a normal flag if it too fails | none needed; it is the existing path |
| **Framework-provided** | **no item**, one stderr diagnostic (e.g. `framework reference: Illuminate\Support\Facades\Log::info declared at vendor/…/Log.php:25 (@method static)`) | **freeze review 05**, exactly: a lookup that *ran and answered* produces no item and one diagnostic; ADR-A009 — *"a premise exists only where an unverified premise exists"* |
| **Nothing** | today's `unresolved-reference` flag | unchanged |

The middle row is the load-bearing claim, so it is worth stating plainly: a framework-provided
reference is **not** an unverified premise. The run asked "is this contract defined, and where?", and
answered it with a citation. Emitting `ASSUMPTION: … contract unverified` would state something
false — the precise failure mode freeze review 06 rejected when it refused to reuse
`unresolved-reference` for a caller-search failure. And per §4.6 the resolving source must not be
fetched. "No item, one diagnostic" is the only outcome consistent with both.

This is why **RQ5 and RQ6 resolve to "compact facts on stderr, nothing in the bundle"**, and why the
`bundle_version: 1` contract does not need to change.

### 9.3 Knowledge as data, with a small irreducible rule set

The Laravel implementation is **four rules plus facts read from the installed tree** — not a
catalogue:

| Rule | Statement | Evidence it reads | Category |
|---|---|---|---|
| **L0** *(not Laravel)* | If a member is not in a class's own file, follow `extends` and `use <Trait>;` to classes the locator can place, bounded to *N* hops | PHP source | C1 |
| **L1** | A class that (transitively) extends `Illuminate\Database\Eloquent\Model`, receiving a **static** call to `m` it does not declare, redirects to: `scope<Ucfirst(m)>` **on itself** → else `Illuminate\Database\Eloquent\Builder::m` → else that class's `@mixin` target | `Model.php:2554`, `Model.php:2544`, `Builder.php:2232`, `Builder.php:33` | C4, C5, C2 |
| **L2** | A class extending `Illuminate\Support\Facades\Facade`, receiving a static call to `m`, is **framework-provided** iff a `@method static … m(` tag is present on it | `Facade.php:355`; `Log.php:25` | C3 |
| **L3** | A `@mixin \Fqcn` tag redirects an unresolved member lookup to `Fqcn` | `Builder.php:33` | C2 |

L0 is PHP-language knowledge and arguably belongs in the core, not behind the port — noted as OQ2.
L1 is the only rule that encodes procedural behaviour, and it is *one* rule with four citations.
Everything else — which methods exist, what their signatures are, which facades exist — is **read
from `vendor/` at run time and therefore tracks the installed version by construction** (F5).

### 9.4 Version handling

Read `laravel/framework`'s resolved version from `vendor/composer/installed.php` (F7). Declare the
major range the four rules were verified against — **12.x and 13.x**, both checked in F5. Outside the
range, the provider returns *nothing* for every subject, and the tool degrades to exactly v0.1.0
behaviour: flags, not wrong answers (P10).

---

## 10 · Proposed minimal Laravel knowledge scope

The smallest scope that is *individually justified*. Each item names what earns it and what would
falsify it. **This is a proposal for a future milestone; nothing here is built.**

### 10.1 In

| # | Item | Removes | Earned by | Falsified by |
|---|---|---|---|---|
| **S1** | `ClassLocator` also reads `vendor/composer/autoload_psr4.php` when present | the `missing PSR-4 entry` class of failure — 32 of 45 rows *as a precondition*, 8 of 45 outright | F2, F3, F7 | any non-determinism or path escape; `vendor/` absent (falls back to today) |
| **S2** | Rule **L2** — facade `@method static` recognition → *framework-provided* verdict | `Log::info`, `DB::transaction`, `Cache::*`, `Storage::*`, `Route::*` — the largest single group in F3 | F3, F4, F6 | any Exp 2 item appearing; any tag matched by name rather than by parsed tag |
| **S3** | Rule **L1**, `scope` half only → *redirect to a project-local member*, **fetched** | `PageContent::forPage` (§3.2) and its siblings | §3.2, and Exp 1's precedent that model surface is fetched | a project method named `scopeX` that is not a scope |
| **S4** | Rule **L1**, builder half → `Eloquent\Builder` then its `@mixin` → *framework-provided* verdict | `Model::create\|where\|orderBy\|findOrFail\|with\|updateOrCreate\|first` — the 13 `App\Models\X::…` rows plus part of the Laravel rows | F3, F4, F5 | any Exp 2 or Exp 4 item appearing |
| **S5** | One stderr diagnostic line per framework-provided verdict, citing the declaring path and line | keeps P10 satisfied without a bundle item | freeze review 05 / AA11 precedent | — |
| **S6** | One `ArchitectureBoundaryTest` case: no core class names a framework symbol | makes §7.1 a test | — | — |

**S1 is not Laravel knowledge** and should be decided on its own merits — it is a Composer-map
widening that equally serves `Endroid\QrCode\…` and every other package. It is listed first because
S2 and S4 cannot function without it.

### 10.2 Explicitly out of the minimal scope

| Deferred | Why | Trigger to revisit |
|---|---|---|
| Relation-chain typing (`$x->rel()->createMany()`) | needs the receiver's type (C8) plus a two-hop walk (OQ1) | an experiment whose finding depended on a relation method's contract |
| Local-variable type inference (`$package->fresh()`) | C8; no experiment | a finding that required it |
| Recognising members through `->` chains at all | changes the extractor's closed form list — ADR-A003 gate | an experiment |
| Macros, `Macroable`, `resolveRelationUsing` | C7, runtime-only | see OQ4 |
| Container bindings / concrete facade targets | C6, runtime-only; unnecessary given L2 | — |
| PHP built-in globals (`Closure`, `DateTimeInterface`) | 2 of 45; not framework knowledge — a separate, smaller question | measurement showing it matters |
| Third-party packages beyond what S1 gives for free | no experiment; S1 already places their classes | — |
| Fetching any framework source into the bundle | refuted by F6 | a scored run showing a framework slice changed a review verdict |
| Config, migrations, routes, Blade, container, queues, events, middleware, validation | X3; no experiment | X3's existing trigger |

### 10.3 Predicted effect on the F3 baseline

Stated as a prediction to be measured, not as a claim:

| Outcome | v0.1.0 | Predicted with S1–S5 |
|---|---|---|
| `flagged` `named_reference` items | 45 | ~2 (`Closure`, `DateTimeInterface`) + any genuine failure |
| `fetched` items added | — | ~3 (S3 scopes; project-local, Exp-1-consistent) |
| stderr framework diagnostics | 0 | ~40 |
| Items added to Experiment 2's bundle | — | **must be 0** |

The last row is the acceptance condition, not an aspiration.

---

## 11 · Open questions

Recorded open, per the repository's discipline. None is resolved by this document.

- **OQ1 · Is following `extends` / `use <Trait>` / `@mixin` a depth-two traversal?**
  P3 says *"resolves references the diff names directly; it does not then resolve their references."*
  An inheritance walk resolves the **same** assertion (`CateringPackage::create`) to the place PHP
  itself defines the named member; it creates no new assertion and the bundle item's `reason` is
  unchanged. The counter-reading is that any second file read is depth two. X2's status is *"not
  yet"*, not *"never"*, so this is decidable on evidence — **but it must be decided in the
  architecture repository by an ADR, not assumed here.** It is the single largest risk to the
  proposal in §9.
- **OQ2 · Does rule L0 belong in the core or behind the port?**
  Following `extends`/`use` is PHP-language knowledge, not Laravel knowledge, and putting it behind a
  *Framework* port mislabels it. Putting it in `NamedReferenceResolver` is honest but is the change
  OQ1 governs.
- **OQ3 · Is a stderr diagnostic sufficient for a framework-provided verdict?**
  §9.2 argues it is, on the freeze-review-05 precedent. If a scored run shows reviewers need the fact
  *in* the bundle, the answer is a bundle change (a third lever, or a `framework_reference` kind) —
  a `bundle_version` bump, recorded here as **a proposed change only**.
- **OQ4 · What happens to a project that registers macros?**
  A `Builder::macro('activeOnly', …)` shadows everything (`Builder.php:2210-2231`). The safe behaviour is
  the existing flag, since no file settles it — but a project with many macros would see flags the
  proposal was meant to remove. Whether that ever warrants opt-in metadata (A7) is unanswered.
- **OQ5 · Which Laravel version is authoritative for this project?**
  Two are installed in the user's own trees: **v12.64.0** (the application these examples come from)
  and **v13.26.1** (a second application). The rules in §9.3 were verified against both. No single
  authoritative version is established, and the design deliberately does not need one — but if a
  future milestone pins one, it must be pinned explicitly rather than assumed.
- **OQ6 · Should `unresolved-reference` be split?**
  Cause A (class not placed) and cause B (member not declared) currently share one premise and one
  statement, while producing two different stderr lines. Freeze review 06 established that a fixed
  statement must be *true* of the case it covers. Whether "could not be resolved on disk" is true of
  both is an ADR-A009 question, not a framework-knowledge question.
- **OQ7 · Is the whole approach worth it before Experiment 5 is run?**
  `requirements.md` states the first thing to feed the finished tool is the missing *sloppy* commit.
  Doing framework knowledge first is a defensible reordering — the false-positive rate makes any
  scored run hard to read — but it *is* a reordering of the research plan and should be acknowledged
  as one.

---

## 12 · Risks

| # | Risk | Severity | Evidence | Mitigation available |
|---|---|---|---|---|
| **R1** | **Suppression becomes silence.** A framework verdict removes an item; if the recognition is wrong, a real missing member disappears | **High** — a direct P10 violation | — | Verdict only on **positive** evidence (a parsed `@method` tag, a real declared method found on disk), never on a name; one stderr diagnostic per verdict (S5); never consult the provider for `SameFileSymbolAbsence` (see R2) |
| **R2** | **Experiment 1's missing-`Log`-import finding is destroyed.** That finding is a `same_file_symbol_absence` about the *absence of an import*. A framework layer that "knows about `Log`" could plausibly suppress it | **Critical** — it is the headline finding of the primary recall test | `experiment-01.md` §2 | Hard rule: framework knowledge is consulted **only** for `AssertionKind::NamedReference`, and `OwnFileAssertionExtractor` never sees it. Assert it in `ArchitectureBoundaryTest` |
| **R3** | **`vendor/` is absent.** It is gitignored (F7); a CI job reviewing a PR from a bare checkout has no framework source | **High** — the whole layer becomes inert exactly where the tool is most useful | F7 | Degrade to v0.1.0 behaviour with one diagnostic naming the reason. Never guess from `composer.lock` alone — a version string is not a member list |
| **R4** | **Determinism loss.** `vendor/` contents depend on when `composer install` ran; two machines can differ | Medium — P8 is non-negotiable | F5 | Same `composer.lock` ⇒ same tree. Parse `autoload_psr4.php` as text; never `require` it. Emit the resolved framework version in the diagnostics so a bundle diff is explicable |
| **R5** | **Scope creep into a Laravel support library.** 44 facades, dozens of contracts, macros, Blade, queues, events | Medium | ADR-A003's stated failure mode | Four rules, one port, one call site, one implementation; the boundary test in §7.1; every addition through ADR-A003's four-step gate |
| **R6** | **The acceptance keys cannot verify any of this.** All four fixtures require an operator-supplied private repo; the harness currently fails 4/4 by design (§14) | **High** — the proposal's regression net is not currently runnable | verified: 789 tests, 4 failures, all *"fixture diff absent"* | Either obtain the Phase 0 fixtures, or add a **new** Laravel fixture with a hand-written key — which is itself an experiment, and is exactly what ADR-A003's gate asks for |
| **R7** | **Precision regression on Experiment 2.** The trace-logging control contains ~12 `Log::info` calls | **Critical** | F6 | The design cannot fetch (§9.1). But S2 must also produce **zero bundle items** there, not merely no fetches — this is the sharpest single test of the whole proposal |
| **R8** | **A project override is masked.** A project method genuinely named `create` on a model that the framework also provides | Low | §6.3 | Lookup order puts the project's own file first, unconditionally — the framework redirect is only reached after that fails |
| **R9** | **Rule drift.** Laravel changes a dispatch mechanism the four rules encode | Medium | F5 shows the *facts* drift while these four rules held across 12→13 | Pin the verified major range from `installed.php`; outside it, return nothing |

---

## 13 · Evidence and source references

### 13.1 Frozen project documents (re-read for this milestone)

| Document | What was taken from it |
|---|---|
| `context-discovery-research/docs/03-phase1/architecture-principles.md` | P1–P10, verbatim constraints in §2.4 |
| `…/docs/03-phase1/requirements.md` | R1–R5; X1–X5 with their *never* / *not yet* statuses |
| `…/docs/02-discovery/fetch-vs-flag.md` | the decision rule; the canonical flag case |
| `…/docs/02-discovery/discovery-moves.md` | the five moves; the disproven forward-import-following move |
| `…/docs/02-discovery/context-types.md` | types 1–6; type 2 = named collaborator, depth one |
| `…/docs/01-phase0/experiment-01.md` | §2 the missing-`Log`-import finding (R2); §4 the minimum-context list |
| `…/docs/01-phase0/experiment-02.md` | §2, §3, §4 — the precision control and *"a naked-diff review that knows Laravel catches these unaided"* (F6) |
| `…/docs/00-introduction/00-project-overview.md` | the domain note: Laravel + Plaid; findings generalise, examples do not |
| `context-discovery-architecture/architecture/01-architecture.md` | modules, ports, data flow, the closed cross-file form list |
| `…/architecture/03-interfaces.md` | the CLI contract; bundle schema v1; the five port signatures |
| `…/architecture/decisions/ADR-A003-closed-move-set.md` | the closed set; the four-step gate; "no plugin system" |
| `…/architecture/decisions/ADR-A009-premise-catalogue.md` | the seven premises; *"a premise exists only where an unverified premise exists"*; freeze reviews 05 and 06 |
| `…/architecture/decisions/ADR-A006-grep-not-graph.md`, `A005`, `A001` | bounded search; slices not files; zero dependencies |
| `…/architecture/evidence-gaps.md` | AA1–AA11; the open questions kept open; the under-builds and their triggers |
| `…/architecture/README.md` | the non-negotiable exclusions |

### 13.2 CLI source read (v0.1.0, `212ea91`)

`src/Discovery/Extraction/NamedReferenceAssertionExtractor.php` ·
`src/Discovery/Resolution/NamedReferenceResolver.php` ·
`src/Discovery/Lever/LeverPolicy.php` ·
`src/Discovery/Extraction/UnverifiablePremiseAssertionExtractor.php` ·
`src/Adapters/Autoload/ComposerPsr4ClassLocator.php` ·
`src/Adapters/Filesystem/LocalSourceRepository.php` ·
`src/Domain/Bundle/Lever.php` · `src/Domain/Assertion/AssertionKind.php` ·
`tests/Unit/ArchitectureBoundaryTest.php` · `tests/Acceptance/fixtures/*/expected-context.md`

### 13.3 Laravel framework source

**Primary tree — `laravel/framework` v12.64.0**, at
`<abouelsid-backend>/vendor/laravel/framework/src/Illuminate/`
(version from `vendor/composer/installed.php` → `pretty_version: v12.64.0`, and `composer.lock`).

| Claim | Source |
|---|---|
| `Model::__call` → `forwardCallTo($this->newQuery(), …)` | `Database/Eloquent/Model.php:2529`; the forward at `:2544` |
| `Model::__callStatic` → `(new static)->$method(...)` | `Database/Eloquent/Model.php:2554` |
| `Model::__call` consults `relationResolver()` first | `Database/Eloquent/Model.php:2535` |
| `Model::fresh()` is a declared method | `Database/Eloquent/Model.php:1838` (slice 1832–1848) |
| `Model` carries **no** `@mixin` tag | `Database/Eloquent/Model.php:1-38` — verified absent |
| `Eloquent\Builder::create()` | `Database/Eloquent/Builder.php:1216` (slice 1210–1221) |
| `Eloquent\Builder::where()` | `Database/Eloquent/Builder.php:352` (slice 343–365) |
| `@mixin \Illuminate\Database\Query\Builder` | `Database/Eloquent/Builder.php:33` |
| `Eloquent\Builder::__call` order: macro → local macro → global macro → named scope → `$passthru` → forward | `Database/Eloquent/Builder.php:2208` (`macro` `:2210`, local/global macros `:2214`/`:2222`, named scope `:2232`, `$passthru` `:2236`, forward `:2240`); `$passthru` declared at `:105` |
| `Query\Builder::orderBy()` | `Database/Query/Builder.php:2918` (slice 2909–2940) |
| `HasOneOrMany::createMany()` | `Database/Eloquent/Relations/HasOneOrMany.php:438` (slice 432–447) |
| `HasMany extends HasOneOrMany` | `Database/Eloquent/Relations/HasMany.php:13` |
| `Facade::__callStatic` → `getFacadeRoot()->$method(...)` | `Support/Facades/Facade.php:355` |
| `Facade::getFacadeRoot()` → `resolveFacadeInstance(getFacadeAccessor())` | `Support/Facades/Facade.php:209` |
| `resolveFacadeInstance` reads the container `static::$app[$name]` | `Support/Facades/Facade.php:232` |
| `Log::getFacadeAccessor()` returns `'log'` | `Support/Facades/Log.php:47-50` |
| `@method static void info(string\|\Stringable $message, array $context = [])` | `Support/Facades/Log.php:25` (31 `@method` tags total) |
| `DB` facade carries 107 `@method` tag lines | `Support/Facades/DB.php` |
| 44 facade subclasses ship with the framework (45 files, of which `Facade.php` is the base); all 44 declare `@method static` | `Support/Facades/*.php` — counted by `grep -l 'extends Facade'` and `grep -l '@method static'` |

**Comparison tree — `laravel/framework` v13.26.1**, at
`<nativephp/backend>/vendor/laravel/framework/src/Illuminate/`, used only for F5.

### 13.4 Composer / application metadata

| Claim | Source |
|---|---|
| App PSR-4 map is `App\`, `Database\Factories\`, `Database\Seeders\` | `<abouelsid-backend>/composer.json` → `autoload.psr-4` |
| Generated map has 96 PSR-4 roots incl. `Illuminate\` | `vendor/composer/autoload_psr4.php:78-79` |
| Resolved framework version | `vendor/composer/installed.php`; `composer.lock` |
| `vendor/` gitignored, `composer.lock` tracked | `.gitignore:22`; `git ls-files composer.lock` |

### 13.5 Application source used as evidence

`app/Repositories/CateringPackageRepository.php` (`CateringPackage::create`,
`$package->features()->createMany`, `$package->fresh`) ·
`app/Models/CateringPackage.php` (`features(): HasMany`) ·
`app/Repositories/BranchRepository.php` (`Branch::orderBy`, `Branch::where`) ·
`app/Models/PageContent.php:20` (`scopeForPage`) ·
`app/Repositories/PageContentRepository.php:14` (`PageContent::forPage`) ·
`app/Models/User.php` (`use HasApiTokens, HasFactory, HasRoles, Notifiable`) ·
commit `44726d0`; `git log -15` for the F3 survey.

### 13.6 Reproduction artifacts

All under the session scratchpad; nothing written into any project:
`exp-catering.diff`, `exp-probe.diff`, `exp-scope.diff`, `fixture-repo/`, `fixture-repo-2/`,
`survey/*.diff`, `survey/all.ndjson`, `probe-slicer.php`, `probe2.php`.

### 13.7 Sources deliberately **not** used

No web documentation, changelog, blog post, upgrade guide or third-party article was consulted. Every
Laravel claim is cited to source code in a tree installed on this machine, because a documentation
claim cannot be checked by the CLI and would not meet the brief's *"attributable source"* bar for a
tool whose only evidence is files on disk.

---

## 14 · Explicitly NOT implemented, and NOT designed

### 14.1 Not implemented (this is a research milestone)

- No source file was created, modified or deleted in `src/`.
- No test was created, modified or deleted in `tests/`.
- No dependency was added; `composer.json` and `composer.lock` are untouched.
- No `Ports\FrameworkKnowledge` interface exists. No `LaravelFrameworkKnowledge` class exists.
- `ComposerPsr4ClassLocator` was **not** changed; it still reads `composer.json` only.
- `NamedReferenceResolver`, `NamedReferenceAssertionExtractor` and `LeverPolicy` are unchanged.
- No premise was added, removed or reworded; `PremiseCatalogue` still holds seven.
- The bundle schema is unchanged: `bundle_version: 1`, two levers, five assertion kinds.
- The two defects found in §4.8 (**D1**, **D2**) were **not** fixed, although both are real.
- No file in `context-discovery-architecture/` or `context-discovery-research/` was modified.
- `v0.1.0` is untouched; `HEAD` is still `212ea91`, and it is still the tag's commit.

### 14.2 Not designed (out of scope by the brief)

Dashboard · GitHub integration · pull-request pipeline · AI prompts or prompt templates · platform
UI · multi-framework support beyond the boundary definition in §7 · Symfony/other-framework providers
· a general plugin or extension system · severity or scoring · any LLM call.

### 14.3 Not proposed, though adjacent

- No redesign of the Context Bundle. §9.2 shows the required outcome fits schema v1 as it stands.
  The only bundle change mentioned anywhere is in **OQ3**, and it is recorded as a **proposed change
  only**, contingent on a scored run.
- No change to the five assertion kinds, the seven premises, the four extractors, the three
  resolvers, or `LeverPolicy`'s rule.
- No config file, no environment variable, no state directory, no cache, no index.
