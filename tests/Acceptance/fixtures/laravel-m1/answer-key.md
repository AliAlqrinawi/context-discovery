# M1 · Hand-written answer key

**A projection of [`answer-key.json`](answer-key.json)** — that file is authoritative and is what the
harness reads. The *content* of both is hand-written from `docs/research/M0-laravel-framework-knowledge.md`
and from the framework source installed under `repo-b-vendor-map/vendor`; only this rendering is generated,
so the two cannot drift.

Laravel: **v12.64.0** · budget: **20000** · rows: **23**

`expected` is a **semantic** expectation. It says whether a bundle item should exist and with which
lever; it deliberately does **not** commit to a bundle representation. Whether a framework fact is also
*represented* somewhere is undecided (M0 open question OQ3), and this key must not pre-empt it.

## Classifications

| Value | Meaning |
|---|---|
| `framework_known` | the member is supplied by the framework; the contract is defined, and the run can cite where |
| `project_local` | the resolving source is application code, even when a framework convention is what names it |
| `unresolved` | no file settles the reference; the flag is correct and must survive |
| `not_a_reference` | the text mentions a name but the code does not reference it |

## Gap values

| Value | Meaning |
|---|---|
| `match` | v0.1.0 already produces the expected outcome |
| `false_positive_flag` | v0.1.0 flags a reference that is neither missing nor unverifiable |
| `over_fetch` | v0.1.0 fetches framework source that Experiment 2 forbids putting in a bundle |
| `known_false_negative` | v0.1.0 emits nothing; out of the M0 minimal scope, recorded so it is not mistaken for a pass |

## The key


### S01-facade-static-call

#### S01.1 · `Illuminate\Support\Facades\Log::info`

| | |
|---|---|
| **input** | `Log::info('package.audit', ['action' => $action]);` |
| **classification** | `framework_known` |
| **framework mechanism** | facade proxy (declared surface) |
| **defined at** | vendor/laravel/framework/src/Illuminate/Support/Facades/Log.php:25 — @method static void info(string\|\Stringable $message, array $context = []) |
| **evidence** | Facade::__callStatic Facade.php:355 -> getFacadeRoot() Facade.php:209 -> resolveFacadeInstance() Facade.php:232 (container, runtime). The concrete class is NOT statically determinable; the @method tag is, and it is the only fact needed to know the member exists. |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | yes |
| **current, variant A** | flagged |
| **current, variant B** | none (framework-known; diagnostic only) |
| **gap** | A: `false_positive_flag` · B: `match` |
| **why** | Identical in A and B. The map is fixed in B and the flag survives, because the failure is that `info` is not a declared method on Log.php. This row is the fixture's proof that M0 problem B is independent of problem A. **Closed in variant B by M3**: the facade rule reads the `@method static` tag in Log.php and the reference now yields no item and one cited diagnostic. Variant A is unchanged and still flags, because the PSR-4 map was deliberately not widened — which is this row's original point, now demonstrated by the same reference behaving differently in the two variants. |

#### S01.2 · `Illuminate\Support\Facades\DB::transaction`

| | |
|---|---|
| **input** | `DB::transaction(function () use ($action): void { ... });` |
| **classification** | `framework_known` |
| **framework mechanism** | facade proxy (declared surface) |
| **defined at** | vendor/laravel/framework/src/Illuminate/Support/Facades/DB.php — one of 107 @method tag lines |
| **evidence** | same dispatch as S01.1 |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | yes |
| **current, variant A** | flagged |
| **current, variant B** | none (framework-known; diagnostic only) |
| **gap** | A: `false_positive_flag` · B: `match` |
| **why** | A second facade, chosen because DB::transaction( is also a literal trigger token in ADR-A009's surrounding-transaction premise. The premise must not fire here (the enclosing member opens the transaction itself), and it does not. **Closed in variant B by M3**, on the same rule; the premise still does not fire. |


### S02-eloquent-static-create

#### S02.1 · `App\Models\Package::create`

| | |
|---|---|
| **input** | `return Package::create($data);` |
| **classification** | `framework_known` |
| **framework mechanism** | Eloquent static forwarding (procedural) |
| **defined at** | vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:1216 — public function create(array $attributes = []) |
| **evidence** | Model::__callStatic Model.php:2554 -> (new static)->$method(...) -> Model::__call Model.php:2529 -> forwardCallTo($this->newQuery(), ...) Model.php:2544. Not declared on Model: verified absent. |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | yes |
| **current, variant A** | flagged |
| **current, variant B** | flagged |
| **gap** | A: `false_positive_flag` · B: `false_positive_flag` |
| **why** | The class is App\Models\Package, always placeable by the application's own map, so this row is problem B with problem A absent by construction — in BOTH variants. It is the cleanest isolation of dynamic dispatch in the fixture. |

#### S02.2 · `App\Models\Package`

| | |
|---|---|
| **input** | `public function store(array $data): Package` |
| **classification** | `project_local` |
| **framework mechanism** | bare class name in a return-type position (closed form 3) |
| **defined at** | app/Models/Package.php |
| **evidence** | Experiment 1's minimum-context list asks for the model surface — fillable/casts and the named members. |
| **expected item** | `fetched` |
| **expected flag** | no |
| **expected diagnostic** | no |
| **current, variant A** | fetched |
| **current, variant B** | fetched |
| **gap** | A: `match` · B: `match` |
| **why** | GUARD. Framework knowledge must not suppress the model surface merely because the class extends Model. Experiment 1 requires exactly this fetch. |


### S03-eloquent-static-and-chain

#### S03.1 · `App\Models\Package::where`

| | |
|---|---|
| **input** | `Package::where('is_active', true)->orderBy('position')->get();` |
| **classification** | `framework_known` |
| **framework mechanism** | Eloquent static forwarding -> builder-provided |
| **defined at** | vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:352 — public function where(...) |
| **evidence** | same chain as S02.1; `where` is declared on the Eloquent builder itself, not passed through |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | yes |
| **current, variant A** | flagged |
| **current, variant B** | flagged |
| **gap** | A: `false_positive_flag` · B: `false_positive_flag` |
| **why** | Builder-provided, one hop past the model. |

#### S03.2 · `App\Models\Package::orderBy`

| | |
|---|---|
| **input** | `Package::orderBy('position')->get();` |
| **classification** | `framework_known` |
| **framework mechanism** | Eloquent static forwarding -> @mixin -> query builder |
| **defined at** | vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php:2918 — public function orderBy($column, $direction = 'asc') |
| **evidence** | Eloquent Builder declares @mixin \Illuminate\Database\Query\Builder at Builder.php:33 (machine-readable); at runtime Builder::__call reaches it through $passthru Builder.php:2236 or forwardCallTo Builder.php:2240 |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | yes |
| **current, variant A** | flagged |
| **current, variant B** | flagged |
| **gap** | A: `false_positive_flag` · B: `false_positive_flag` |
| **why** | Two hops: model -> Eloquent builder -> query builder, the second declared by an annotation rather than by code. Written as the head of a static call so the extractor can see it at all. |

#### S03.3 · `App\Models\Package::get`

| | |
|---|---|
| **input** | `...->orderBy('position')->get();` |
| **classification** | `framework_known` |
| **framework mechanism** | reached only through a `->` chain |
| **defined at** | vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php (get) |
| **evidence** | not applicable — the CLI never forms this subject |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | no |
| **current, variant A** | absent |
| **current, variant B** | absent |
| **gap** | A: `known_false_negative` · B: `known_false_negative` |
| **why** | GUARD, in the other direction. `get` appears ONLY as a chain link, never as the head of a static call, so it is outside the closed three-form list (ADR-A003). Its continued absence is the observable form of the extractor not being widened. Adding it needs an experiment, not a patch — M0 §10.2. |

#### S03.4 · `Illuminate\Support\Collection`

| | |
|---|---|
| **input** | `public function listing(): Collection` |
| **classification** | `framework_known` |
| **framework mechanism** | bare framework class name in a return-type position |
| **defined at** | vendor/laravel/framework/src/Illuminate/Collections/Collection.php |
| **evidence** | resolved through the PSR-4 prefix Illuminate\Support\ -> .../Collections, one of four directories that prefix maps to |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | yes |
| **current, variant A** | flagged |
| **current, variant B** | none (dependency class; diagnostic only) |
| **gap** | A: `false_positive_flag` · B: `match` |
| **why** | THE HEADLINE ROW. In A it is one 20-token false flag. In B — where only the map changed — NamedReferenceResolver::surface() slices every member of the class: 115 items and 13,426 tokens from one return type. Widening the map without deciding what a framework reference means converts a cheap false positive into a budget event. **Closed in variant B by M4**: the surface move applies to the project's own classes, so a class the map places inside Composer's vendor directory is settled from its path alone — no item, one cited diagnostic, 115 slices and 13,426 tokens gone. Variant A still flags: the class is unplaceable there, and the Composer map was deliberately not widened. |


### S04-relation-and-instance

#### S04.1 · `(none formed)`

| | |
|---|---|
| **input** | `$package->features()->createMany($rows);` |
| **classification** | `framework_known` |
| **framework mechanism** | relation-provided, reached through a `->` chain on a local variable |
| **defined at** | vendor/laravel/framework/src/Illuminate/Database/Eloquent/Relations/HasOneOrMany.php:438, inherited by HasMany (HasMany.php:13) |
| **evidence** | features() declares : HasMany in app/Models/Package.php — statically readable. The blocker is the receiver: $package's type is a data-flow fact (M0 category C8). |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | no |
| **current, variant A** | absent |
| **current, variant B** | absent |
| **gap** | A: `known_false_negative` · B: `known_false_negative` |
| **why** | Out of the M0 minimal scope. Recorded because the brief names it: today it fails SILENTLY, not by flagging, which P10 treats as the worse failure. Its absence is the honest baseline, not a pass. |

#### S04.2 · `(none formed)`

| | |
|---|---|
| **input** | `return $package->fresh('features');` |
| **classification** | `framework_known` |
| **framework mechanism** | ordinary inherited method with an undetermined receiver |
| **defined at** | vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php:1838 — public function fresh($with = []) |
| **evidence** | the framework half is trivial (a real declared method); the receiver's type is not statically determinable (M0 category C8) |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | no |
| **current, variant A** | absent |
| **current, variant B** | absent |
| **gap** | A: `known_false_negative` · B: `known_false_negative` |
| **why** | Paired with S04.1 to show that the obstacle is the receiver, not the framework: fresh() is as plainly declared as any project method. |


### S05-inherited-static

#### S05.1 · `App\Models\Package::query`

| | |
|---|---|
| **input** | `return Package::query();` |
| **classification** | `framework_known` |
| **framework mechanism** | plain PHP inheritance — no magic dispatch at all |
| **defined at** | vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php:1588 — public static function query() |
| **evidence** | Package extends Model (app/Models/Package.php:10). __callStatic is never reached, because the method really exists on the parent. |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | yes |
| **current, variant A** | flagged |
| **current, variant B** | flagged |
| **gap** | A: `false_positive_flag` · B: `false_positive_flag` |
| **why** | ISOLATION ROW. This flag has nothing to do with Laravel semantics: it is the CLI not following `extends`. It separates M0 rule L0 (PHP inheritance) from rule L1 (Eloquent forwarding), and it is the row that makes M0 open question OQ1 — is following `extends` a depth-two traversal? — concrete and testable. |


### S06-declared-framework-static

#### S06.1 · `Illuminate\Support\Str::slug`

| | |
|---|---|
| **input** | `$data['slug'] = Str::slug($data['name']);` |
| **classification** | `framework_known` |
| **framework mechanism** | framework method declared in its own class file |
| **defined at** | vendor/laravel/framework/src/Illuminate/Support/Str.php:1552 — public static function slug(...) |
| **evidence** | no dispatch of any kind; the slicer finds it directly once the class is placeable |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | yes |
| **current, variant A** | flagged |
| **current, variant B** | none (dependency member; diagnostic only) |
| **gap** | A: `false_positive_flag` · B: `match` |
| **why** | PURE PROBLEM A. Nothing is dynamic here; A flags it only because Illuminate\ is not on the application's map. B proves that — and simultaneously proves that mapping alone is not the answer, because B then fetches framework source that Experiment 2 forbids in a bundle. **Closed in variant B by M5**: the fetch-collaborator move is scoped to application code (`context-types.md` type 2), so a member declared by an installed dependency is settled from its declaration — no item, one diagnostic citing Str.php, source not fetched. The member must really be there: a typo against the same class still flags. Variant A still flags, because the class is unplaceable there. |

#### S06.2 · `Illuminate\Support\Arr::only`

| | |
|---|---|
| **input** | `return Arr::only($data, ['name', 'slug', 'price']);` |
| **classification** | `framework_known` |
| **framework mechanism** | framework method declared in its own class file |
| **defined at** | vendor/laravel/framework/src/Illuminate/Collections/Arr.php:725 — public static function only($array, $keys) |
| **evidence** | Illuminate\Support\Arr does NOT live under a directory named Support: Composer maps the prefix Illuminate\Support\ to four directories (Macroable, Collections, Conditionable, Reflection). Arr is in Collections; Str is in none of them and is found only by falling back to the Illuminate\ prefix. |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | yes |
| **current, variant A** | flagged |
| **current, variant B** | none (dependency member; diagnostic only) |
| **gap** | A: `false_positive_flag` · B: `match` |
| **why** | Paired with S06.1 to exercise PSR-4 multi-directory prefixes and longest-prefix fall-through — the subtlety M0 §4.7 flagged. Any future map widening must reproduce Composer's real algorithm, not a single-path shortcut. **Closed in variant B by M5**: the fetch-collaborator move is scoped to application code (`context-types.md` type 2), so a member declared by an installed dependency is settled from its declaration — no item, one diagnostic citing Collections/Arr.php, source not fetched. The member must really be there: a typo against the same class still flags. Variant A still flags, because the class is unplaceable there. |


### S07-project-local-lookalikes

#### S07.1 · `App\Services\Registry::create`

| | |
|---|---|
| **input** | `return Registry::create(['catering' => 'Catering']);` |
| **classification** | `project_local` |
| **framework mechanism** | an ordinary static factory that happens to share Eloquent's member name |
| **defined at** | app/Services/Registry.php — public static function create(array $entries): self |
| **evidence** | Registry does not extend Model and is final; nothing about it is framework behaviour |
| **expected item** | `fetched` |
| **expected flag** | no |
| **expected diagnostic** | no |
| **current, variant A** | fetched |
| **current, variant B** | fetched |
| **gap** | A: `match` · B: `match` |
| **why** | GUARD. `create` is the single most Eloquent-looking member name in the fixture. Any framework knowledge that matches on a NAME rather than on evidence about the receiving class will swallow this and produce a silent omission. M0 risk R1. |

#### S07.2 · `App\Models\Package::active`

| | |
|---|---|
| **input** | `Package::active()->get();` |
| **classification** | `project_local` |
| **framework mechanism** | Laravel named-scope convention resolving to APPLICATION code |
| **defined at** | app/Models/Package.php:29 — public function scopeActive(Builder $query): Builder |
| **evidence** | Eloquent Builder::__call consults hasNamedScope($method) (Builder.php:2232, delegating to Builder.php:1512) BEFORE $passthru and before forwarding to the query builder |
| **expected item** | `fetched` |
| **expected flag** | no |
| **expected diagnostic** | no |
| **current, variant A** | fetched (scopeActive) |
| **current, variant B** | fetched (scopeActive) |
| **gap** | A: `match` · B: `match` |
| **why** | THE BOUNDARY ROW. The framework supplies only the NAMING RULE; the resolving source is application code and must be FETCHED, not suppressed. S02 already fetches scopeActive as part of the model surface, so v0.1.0 can emit the answer and a flag denying the answer exists in adjacent bundles. This is why framework knowledge and framework code must not be treated as the same thing. **Closed in both variants by M3**: the scope rule resolves `active` to `scopeActive` in the model's own file, and the slice is fetched as project-local code. No second file is opened, so ADR-A010's bound holds. |

#### S07.3 · `App\Services\Registry`

| | |
|---|---|
| **input** | `public function labelled(): Registry` |
| **classification** | `project_local` |
| **framework mechanism** | bare class name in a return-type position (closed form 3) |
| **defined at** | app/Services/Registry.php |
| **evidence** | same move as S02.2 |
| **expected item** | `fetched` |
| **expected flag** | no |
| **expected diagnostic** | no |
| **current, variant A** | fetched |
| **current, variant B** | fetched |
| **gap** | A: `match` · B: `match` |
| **why** | Observed v0.1.0 behaviour worth recording: the member form (S07.1) and the surface form both fire, so the `create` slice appears twice in one bundle. Pre-existing, unrelated to framework knowledge, NOT fixed here. |


### S08-genuinely-unresolved

#### S08.1 · `App\Contracts\MissingGateway::resolve`

| | |
|---|---|
| **input** | `MissingGateway::resolve($reference);` |
| **classification** | `unresolved` |
| **framework mechanism** | none — the class genuinely does not exist |
| **defined at** | nowhere; app/Contracts/MissingGateway.php is absent |
| **evidence** | the App\ prefix maps to app/, so the lookup is well-formed and simply finds no file |
| **expected item** | `flagged` |
| **expected flag** | **yes** |
| **expected diagnostic** | yes |
| **current, variant A** | flagged |
| **current, variant B** | flagged |
| **gap** | A: `match` · B: `match` |
| **why** | GUARD. The unresolved-reference premise is correct here and must survive. The wording imprecision this row recorded — `missing PSR-4 entry` although the prefix IS mapped and it is the FILE that is absent — was **fixed by M10** (ADR-A017): the diagnostic now reads `class file not found`. The bundle item is unchanged in every field. |

#### S08.2 · `App\Models\Package::activatte`

| | |
|---|---|
| **input** | `Package::activatte($reference);` |
| **classification** | `unresolved` |
| **framework mechanism** | none — a typo |
| **defined at** | nowhere: not on Package, not on Model, not on either builder, not a scope (scopeActivatte is absent), not a facade |
| **evidence** | every redirect M0 proposes fails, so the fallback is the existing flag |
| **expected item** | `flagged` |
| **expected flag** | **yes** |
| **expected diagnostic** | yes |
| **current, variant A** | flagged |
| **current, variant B** | flagged |
| **gap** | A: `match` · B: `match` |
| **why** | THE CRITICAL GUARD (M0 risk R1: suppression becoming silence). A misspelled member on an Eloquent model is precisely the shape a careless framework layer would swallow, because the class DOES extend Model. It must stay flagged. Deliberately placed in the same region as S08.1 so one diff carries both unresolved kinds. |


### S09-comments-strings-docblocks

#### S09.1 · `(none formed)`

| | |
|---|---|
| **input** | `// Package::create() was replaced by the repository, and Log::info() moved to audit().` |
| **classification** | `not_a_reference` |
| **framework mechanism** | line comment |
| **defined at** | n/a |
| **evidence** | the extractors skip T_COMMENT and T_DOC_COMMENT |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | no |
| **current, variant A** | absent |
| **current, variant B** | absent |
| **gap** | A: `match` · B: `match` |
| **why** | PRECISION. Recorded as a baseline so a future text- or regex-driven framework layer cannot regress it unnoticed. |

#### S09.2 · `(none formed)`

| | |
|---|---|
| **input** | `/** Historically this ran Log::info() ... @see Log::warning() */` |
| **classification** | `not_a_reference` |
| **framework mechanism** | docblock, including an @see tag |
| **defined at** | n/a |
| **evidence** | as S09.1 |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | no |
| **current, variant A** | absent |
| **current, variant B** | absent |
| **gap** | A: `match` · B: `match` |
| **why** | PRECISION, and pointed: M0 proposes reading @method static tags out of docblocks. This row fixes the asymmetry in advance — docblocks on a FACADE CLASS are evidence about that class; docblocks in application code are prose. |

#### S09.3 · `(none formed)`

| | |
|---|---|
| **input** | `return ['Log::info', 'Package::create', 'Illuminate\\Support\\Facades\\Cache::lock'];` |
| **classification** | `not_a_reference` |
| **framework mechanism** | string literals |
| **defined at** | n/a |
| **evidence** | a quoted name tokenises as T_CONSTANT_ENCAPSED_STRING, never T_STRING |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | no |
| **current, variant A** | absent |
| **current, variant B** | absent |
| **gap** | A: `match` · B: `match` |
| **why** | PRECISION, twice over. The third literal is `Cache::lock`, the exact trigger token for ADR-A009's atomic-lock-store premise; no premise fires, so the fixture also guards the premise catalogue against text matching. |

#### S09.4 · `(none formed)`

| | |
|---|---|
| **input** | `return Log::class;` |
| **classification** | `not_a_reference` |
| **framework mechanism** | ::class constant |
| **defined at** | n/a |
| **evidence** | NamedReferenceAssertionExtractor skips `Foo::class` explicitly — it names no member contract |
| **expected item** | `none` |
| **expected flag** | no |
| **expected diagnostic** | no |
| **current, variant A** | absent |
| **current, variant B** | absent |
| **gap** | A: `match` · B: `match` |
| **why** | PRECISION. A real reference to the Log class that asks nothing of its surface. Framework knowledge must not start forming a subject here. |


### S10-missing-import-absence

#### S10.1 · `Log`

| | |
|---|---|
| **input** | `Log::info('auditor', ['action' => $action]);  // in a file with NO use statement for Log` |
| **classification** | `project_local` |
| **framework mechanism** | an ABSENCE — the defect is that the import is not there |
| **defined at** | n/a — the finding is the missing `use Illuminate\Support\Facades\Log;` |
| **evidence** | Experiment 1's headline finding, transcribed in tests/Acceptance/fixtures/experiment-01/expected-context.md |
| **expected item** | `fetched` |
| **expected flag** | no |
| **expected diagnostic** | no |
| **current, variant A** | fetched |
| **current, variant B** | fetched |
| **gap** | A: `match` · B: `match` |
| **why** | THE CRITICAL REGRESSION GUARD (M0 risk R2, severity Critical). The item is a same_file_symbol_absence, and a framework layer that 'knows about Log' could plausibly suppress it and destroy the primary recall test's headline finding. M0's stated rule — framework knowledge is consulted ONLY for named_reference, never for the own-file extractor — is what this row exists to enforce. |

