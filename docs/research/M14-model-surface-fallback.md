# M14 · G1 — the project class surface as a resolution-time fallback

> **Status:** implemented. ADR-A020 accepted; M13's boundary **B4** built as a fallback inside
> `NamedReferenceResolver`, wired at pipeline stage 5c. The unresolved-member flag is **retained**
> beside the fetched surface, not replaced by it.
>
> Measured on the real Experiment 5 pull request: **218 → 606 tokens**, 11 → 18 items, recall
> **1/4 → 3/4**, precision **1/11 → 5/18**. One new false positive, named in §9.

## 1 · Objective

Close **G1**, the gap M7 measured: Experiment 1's model-surface move — the best-evidenced move in
the whole corpus — never fired on real Laravel code, because real code reaches a model through
`Setting::updateOrCreate(...)` and never through `new Setting` or `: Setting`.

M13 answered *whether* and *where*. M14 builds it, and settles the one question M13 left open.

## 2 · The question M13 left open

> When B4 fires, does the existing unresolved-member flag **remain** alongside the fetched surface,
> or is it **replaced** by it?

Both are defensible on their face. *Replace* is tidier and cheaper: the reference is answered, so
say so once. *Retain* costs an extra item per reference and leaves a flag whose statement ADR-A016
already records as arguably false.

## 3 · Answer key, written first

`tests/Acceptance/fixtures/experiment-14/answer-key.json`, hand-written from the fixture sources
before any code changed, with an explicit row for each of A–J.

**A deliberate ordering deviation, stated:** the milestone asked for ADR-A020 first. The ADR has to
*record* the flag decision, and the key is what establishes it — so the key was written first and
the ADR immediately after. Both precede the first line of implementation.

| Row | Reference | Conditions | Keyed expectation |
|---|---|---|---|
| A | `Package::where` | placeable · project · unresolved | Package surface **+ flag retained** |
| B | `Package::create` | same | surface + flag; no *additional* surface |
| C | `Package::query` | same | surface + flag; still no additional surface |
| D | `Setting::updateOrCreate` | same | Setting surface + flag — a second, independent model |
| E | `Registry::create` | placeable · project · **resolves** | the `create` slice only; no surface, no flag |
| F | `MissingGateway::resolve` | **not placeable** | exactly one flag, no surface |
| G | `Log::info` | placeable · **not project** | no item at all; no vendor source |
| H | `Package::activatte` | placeable · project · unresolved — *identical to B* | **flag must remain** |
| I | `Widget`, and `Invoice` reached two ways | bare-class form | one surface each; no duplicate |
| J | docblock / comment / string / `::class` | — | zero items |
| X | everything else | — | schema, kinds, premises, levers, ports unchanged |

## 4 · The decision, and the row that made it

**RETAIN.** Decided by row **H**.

`Package::activatte` is a typo. It satisfies all three of B4's conditions *identically* to
`Package::create`, and ADR-A016 established that no fact available to the tool separates them.

Test *replace* against that row and it fails immediately: the typo would silently become "here is
the model surface", with no warning attached — M0's risk **R1**, and exactly the silent omission
**P10** forbids. Test *retain* and it holds: the typo keeps its flag, and so does the real method,
because the tool does not claim to know which is which.

> A rule that cannot tell a typo from a method must not make a decision that depends on telling
> them apart.

The cost is real and is recorded rather than hidden: an Eloquent member reference now produces two
outcomes — a flag whose statement ADR-A016 could not make true, and a surface. One assertion
yielding both a flagged and a fetched `ResolvedAssertion` is not new; call-site truncation has done
it since freeze review 06.

## 5 · What was built

Two files, both existing.

**`NamedReferenceResolver::unresolvedMemberSurface(Assertion): list<SourceSlice>`** — the three
conditions, asked in full so the boundary holds wherever it is called from:

1. the locator **places** the class;
2. the located path is **project source** (ADR-A012);
3. **nothing accounts for the member** in that class's own file — not a declaration, not a framework
   naming convention (`scope<Name>`), not a framework declaration (`@method static`).

Then, and only then, the existing private `surface()` runs on the file already read.

**`DiscoverContext` stage 5c-i** — after the flag is appended, the fallback is offered, subject to
two guards and the existing ADR-A019 visibility filter:

- a class some assertion already names **on its own** is left to the bare-class path it already
  has, so `?Invoice` and `Invoice::create` produce one surface, not two. Computed from the whole
  assertion list *before* the loop, so the answer does not depend on which reference the parser
  reached first (P8);
- a class the fallback has already served is not served twice, so `Package::where` + `::create` +
  `::query` produce one surface, not three.

No new diagnostic. The bundle item carries reason, lever and provenance, which is the visible
record P5 asks for; a second stderr line would say nothing the flag has not already said.

## 6 · What was *not* built

No extraction change — `NamedReferenceAssertionExtractor`'s form-3 `T_DOUBLE_COLON` guard is byte
for byte what it was, because extraction has no `ClassLocator` and no `SourceRepository` and so
cannot ask any of B4's three questions. No inheritance traversal, no `Eloquent\Builder` knowledge,
no `@mixin`. No Composer-map widening. No framework knowledge moved into extraction. No new port.
No name, namespace, base class or method name is special-cased anywhere.

Mechanically verified after the change: **5 ports, 5 assertion kinds, 7 premises, 2 levers,
`bundle_version` 1**, item schema unchanged. 455 boundary / architecture / determinism / freeze
tests green.

## 7 · Fixture and results — experiment-14

A superset of experiment-13's repository: `Package`, `Setting`, `Widget`, `Registry`,
`MissingGateway`, plus a new `Invoice` referenced *both* as a `?Invoice` return type and through
`Invoice::create` — the duplicate guard, which nothing before M14 could exercise.

Run at budget 8000: **16 items, 298 tokens, 0 dropped**, 8 diagnostics.

| Row | Keyed | Observed | |
|---|---|---|---|
| A·B·C | Package surface once + 3 flags | `casts`, `fillable`, `scopeActive` + 3 flags | ✅ |
| D | Setting surface + flag | `fillable` + flag | ✅ |
| E | `Registry::create` slice only | one `create` slice, no flag, no surface | ✅ |
| F | one flag, no surface | one flag; `class file not found` | ✅ |
| G | no item at all | none; framework diagnostic cites `Log.php:25` | ✅ |
| H | **flag retained** | flagged, and said on stderr | ✅ |
| I | one surface each | `Widget` ×2 members, `Invoice` ×1 — **not duplicated** | ✅ |
| J | zero items | zero | ✅ |
| X | contract unchanged | unchanged | ✅ |

**9 of 9 keyed rows.** Determinism: 5 runs, byte-identical on both streams.

The bare-class index is genuinely exercised, not merely present: the extractor emits *both*
`App\Models\Invoice` and `App\Models\Invoice::create` for the fixture, and the bundle contains one
`app/Models/Invoice.php` slice.

## 8 · The real pull request — Experiment 5

Same commit, same repository, same budget as M7 and M12.

| | M7 | M11 | M12 | **M14** |
|---|---:|---:|---:|---:|
| items | 26 | 14 | 11 | **18** |
| tokens | 1231 | 536 | 218 | **606** |
| vendor items | 0 | 0 | 0 | **0** |
| recall | 1/4 | 1/4 | 1/4 | **3/4** |

M13 projected 218 → *approximately* 606 tokens and 11 → 18 items, and named `Setting`, `MediaItem`
and `User` as the three classes that would gain surfaces. All four predictions are exact.

**Recall 3/4.** E5.1 (`Setting` surface) and E5.2 (`MediaItem` surface) both close — the two rows
M7 recorded as false negatives caused by G1. E5.3 (the surrounding-transaction premise) was already
satisfied and still is. E5.4 (`ApiResponse::success`) remains absent, as its own `known_gap` field
predicted: reaching it needs *changed file → Controller → ApiResponse*, which ADR-A010 forbids and
M8 declined to change.

Token census at 8000:

| | items | tokens |
|---|---:|---:|
| flags (11) | 11 | 218 — unchanged from M12, exactly |
| `app/Models/MediaItem.php` | 2 | 137 |
| `app/Models/Setting.php` | 2 | 86 |
| `app/Models/User.php` | 3 | 165 |

Budget: 8000 → 606 used, 0 dropped; 2000 → 606, 0 dropped; 500 → 460 used, **2 dropped**, both
recorded with reason and token count. Determinism: 5× JSON @8000, 5× Markdown @8000, 5× JSON @500 —
one distinct output each, on both streams.

## 9 · The new unkeyed item, named

> **Corrected by M15.** This section originally called the `User` surface a **false positive**. It
> is not: M7's key contains no row that decides a project-model reference from a test file, so the
> honest description is **unkeyed**. See
> [`M15-origin-value.md`](M15-origin-value.md) §10, and §2 there for why E5.12 does not supply the
> row it appears to. M15 keyed the case directly and answered **FETCH**.

`app/Models/User.php`, 3 slices, **165 tokens**, caused by `User::create(...)` in
`tests/Feature/MenuPdfTest.php`.

No key row asks for it — and, as M15 established, none forbids it either. E5.12's rationale reads
as though it covers this, but the four references it names are all dependency classes, omitted by
ownership in any file whatever.

It is not a boundary failure — `User` satisfies all three conditions exactly as `Setting` does, and
under ADR-A016 nothing available distinguishes a model surface wanted by a controller from the same
surface wanted by a test. It is the honest cost of the rule.

Precision therefore moves from **1/11 (9%)** at M12 to **5/18 (28%)** — wanted items being E5.1's
two `Setting` slices, E5.2's two `MediaItem` slices, and E5.3's premise flag. *(M15 recount: 5/18 is
a floor that charges all 8 unkeyed items as errors; 5/10 over the items the key decides is the
ceiling. M7's key does not decide 8 of the 18.)*

**Recorded, not fixed.** The obvious lever — *a reference originating in a test file is scaffolding*
— is a new discriminator with no experiment behind it, and inventing one inside an implementation
milestone is the thing ADR-A003 exists to prevent. It is written up as a M15 candidate in §14.

> **M15 ran that experiment and the discriminator did not survive.** Every candidate boundary lost
> keyed context without removing a single keyed false positive, and dropping dev-origin references
> destroyed a surface a *production* row demanded. Origin-blind behaviour stands.

## 10 · The M1 baseline — 9 entries changed, and why

| Variant × scenario | items | fetched | flagged | tokens |
|---|---|---|---|---|
| A · `S03-eloquent-static-and-chain` | 3 → 7 | 0 → 4 | **3 → 3** | 60 → 176 |
| B, C · `S03-eloquent-static-and-chain` | 2 → 6 | 0 → 4 | **2 → 2** | 40 → 156 |
| A, B, C · `S05-inherited-static` | 1 → 5 | 0 → 4 | **1 → 1** | 20 → 136 |
| A, B, C · `S08-genuinely-unresolved` | 2 → 6 | 0 → 4 | **2 → 2** | 40 → 156 |

The **delta** is identical in all three repository variants — +4 items, +4 fetched slices, +116
tokens, +0 flags — which is the point: the change turns on *ownership and member evidence*, and
none of the three variants differ in either. The absolute numbers differ for S03 only because of a
pre-existing split M4 already recorded: in variant A `Illuminate\Support\Collection` is unplaceable
and flags, while in B and C it is a placed dependency class settled by diagnostic (S03.4). That gap
is untouched here.

**The flag count does not move anywhere.** That is the RETAIN decision, visible as a measurement
across nine independent runs.

Two things must be said plainly rather than smoothed over.

**S03.1 / S03.2 / S05.1 are not closed, and now cost more.** Their M1 key rows expect
`{"item":"none","flag":false}` — the *ideal* being that `Package::where` is recognised as
framework-provided and produces nothing at all. M14 leaves that false-positive flag exactly where it
was and adds a surface beside it. The two keys are answering different questions about the same
line: M1's row is about the **member contract** (`where` is the framework's, so no premise), M14's
is about the **class the region depends on** (a model whose columns the diff cannot show). Both can
be true at once. Reaching M1's ideal needs Model → Builder, which ADR-A015 evaluated and declined.

**S08 now fetches the `Package` surface for a typo alone.** `Package::activatte` is the only Package
reference in that scenario, so the surface there is caused entirely by a misspelling. The critical
guard holds — S08.1 and S08.2 both still flag, which is what the key demands — and the surface is
not silence: it shows the reviewer Package's real members, which is precisely what reveals that
`activatte` is not among them. Under ADR-A016 the tool cannot treat it differently from
`Package::create` without pretending to know something it does not.

## 11 · One test assertion tightened

`LaravelFixtureBaselineTest::testProblemAAndProblemBAreSeparable` asserted
`fetched_slices_by_path === []` under the message *"and nothing from `vendor/` reaches the bundle"*.
That expressed M4's guarantee only while nothing else was fetched there; `app/Models/Package.php` is
now fetched, and it is the project's own code.

Tightened to the guarantee actually claimed — no path containing `vendor/` — rather than relaxed
away from it. Same discipline as the M6 spy fix: when a measurement stops matching, make the
assertion say what it means.

## 12 · Full suite

**1044 tests, 4689 assertions, 4 failures** — the same four pre-existing failures as every milestone
since M1: `ExperimentKeyTest` for experiments 01–04, whose private fixture repositories are not on
this machine. The suite refuses to fabricate them (ADR-001), which is the correct behaviour.

29 tests added: 16 in `ProjectSurfaceFallbackTest` (the resolver boundary), 13 in
`ProjectSurfaceFallbackAcceptanceTest` (the assembled bundle — flag retention, duplicate guard, the
noise floor, the frozen contract, determinism).

## 13 · Is G1 closed?

**Yes.** The move Experiment 1 earned now fires on the form real Laravel code actually contains, on
a real pull request, closing two answer-key rows that M7 recorded as false negatives — under a
boundary with an ADR, nine keyed rows, and 29 tests.

What is *not* closed, and was never M14's to close:

- **the false-positive flags** on Eloquent members (M1 S03.1/S03.2/S05.1, E5.6/E5.7). Their fix is
  Model → Builder, declined by ADR-A015;
- **E5.4** — trait members reached through a parent. Blocked by ADR-A010;
- **the `User` surface** — §9.

## 14 · Recommendation for M15

Not started, per the milestone. Recorded in priority order:

1. **The origin question.** `User`'s surface came from a test file. *Does the file a reference
   originates in change what the reference is worth?* An experiment, not a patch — measured against
   E5.12 and the M1 fixture, with the null result ("no, and here is what a scope rule would have
   wrongly dropped") a fully acceptable outcome.
2. **The flag statement.** With the surface now beside it, the retained flag's wording is the
   weakest text in the bundle: ADR-A016 records that it is arguably false for `Package::create`.
   Changing wording only, in ADR-A017's spirit, may be worth its own small milestone.
3. **Nothing else.** In particular not depth — ADR-A015 measured that and declined it, and M14
   changes none of the evidence it was declined on.
