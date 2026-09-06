# M15 · origin value — does a reference from a test file mean something different?

> **Classification: C — the evidence does not justify an origin distinction.** No ADR. No production
> change. One lock test, one recorded defect (**G4**), and one correction to M14's own report.

## 1 · Objective

M14 observed that `User::create(...)` in a test file caused a project-class surface M7's answer key
did not ask for, called it a false positive, and wrote *"test files are scaffolding"* into
`evidence-gaps.md` as an **unproven** discriminator with a key-first trigger. M15 pulls that trigger.

> Does the file in which a named reference originates provide enough evidence to justify a different
> resolution or bundling outcome?

## 2 · What the corpus actually says — and it is less than M14 assumed

**The decision rule has no origin term.** `fetch-vs-flag.md` states it in full:

> `is the resolving source named, single, and depth-one on disk? └─ yes → FETCH the minimal slice`

Nothing about where the referencing line lives. `context-types.md` type 2 is *"a class, model, enum,
or method **the diff** explicitly references"* — the diff, not a privileged part of it. Experiment 1
earned the model surface because *"the change relies on the referenced thing's contract — a model's
columns"*, and a mass-assignment is a mass-assignment wherever it is written.

**The Phase 0 corpus is silent.** Every occurrence of "test" across experiments 1–4 and the
discovery documents is methodological — *recall test*, *precision test*. Not one names a test
**file** as an input the tool reads.

**E5.12 does not say what it looks like it says.** M7's row on test files is the single piece of
apparent evidence, and its rationale is broad: *"Test scaffolding. Nothing here is a contract the
change relies on."* But the references it actually **names** are `Storage::fake`,
`UploadedFile::fake`, `Sanctum::actingAs` and factories — every one of them a **dependency** class,
already omitted by ADR-A012/A013 in whatever file it appears. The row supplies a rationale and
**not one discriminating instance**.

Checked against the diff: the test writes `Sanctum::actingAs(User::create([...]))` — `User::create`
directly, not `User::factory()`. So E5.12's "factories" does not name it either. **M7's key has no
row that decides a project-model reference from a test file.** M14's §9 called the `User` surface a
false positive by inference from E5.12's rationale; that inference does not hold. §10 corrects it.

**Origin cannot be expressed as a ranking input either.** `ItemPriority` is documented as *"a
function of `assertionKind` and `lever` — two fields a BundleItem already carries, so the enforcer
never needs to know which resolver produced an item (freeze review 04)"*, and as *"**not** a
relevance signal: the engine assigns no severity, no score and no ranking (P6, X4)"*. Outcome **B**
is therefore closed by the architecture before the evidence is even consulted.

## 3 · The one real fact in favour of a rule

The project **declares** the distinction itself, and the tool already reads the file it is in:

| Origin | Declared by | Category |
|---|---|---|
| `app/**` | `autoload.psr-4` | production |
| `database/seeders/**`, `database/factories/**` | `autoload.psr-4` | **production — Composer says so** |
| `tests/**` | `autoload-dev.psr-4` | dev |
| `routes/**`, `config/**` | neither | **unmapped** |

`ComposerPsr4ClassLocator` reads *both* sections today and merges them, deliberately — *"because a
changed file may reference either"*. The distinction it discards is exactly the one under test, and
an `isDevSource()` method would be the precise mirror of the existing `isProjectSource()` — no new
port, no new file read, the same class of declared fact as ADR-A012's vendor directory (AA13).

So the discriminator, **if** it were justified, could be built cleanly. That is why this milestone
tested its strongest form rather than a directory-name guess: a rule that cannot be justified from
the project's own declaration cannot be justified from a weaker one.

## 4 · Fixture and key

`tests/Acceptance/fixtures/experiment-15/` — six changed files across all four declared categories,
twelve keyed rows, written before the CLI was run. The full key is in `answer-key.json`; the
category table and row list are in the fixture's `README.md`.

The decisive row is **T3**: `AuditLog::create(['action' => 'stored'])` from a test, where `AuditLog`
is reached from nowhere else — the `User` case, keyed for the first time.

It is keyed **FETCH**, the same as the production row P2, because every reason Experiment 1 gives
for the model surface applies to it: the line mass-assigns, whether `action` is fillable is a real
correctness question about a line **in this diff**, and no other file settles it. The key looked for
evidence that origin changes this and did not find any.

## 5 · Baseline — the current implementation against the key

Budget 8000: **19 items, 370 tokens, 0 dropped**, 9 diagnostics.

| Row | Keyed | Observed | |
|---|---|---|---|
| P1 | member `total` | fetched | ✅ |
| T1a | **one** copy of `total` | **two** copies | ❌ |
| T1b | member `subtotal` | fetched | ✅ |
| P2 | flag + `Order` surface | flag + `casts`, `fillable`, `scopeActive` | ✅ |
| T2 | own flag, no extra surface | own flag, no extra surface | ✅ |
| **T3** | flag + `AuditLog` surface | flag + `casts`, `fillable` | ✅ |
| H1 | flag, no extra surface | flag | ✅ |
| S1 | flag, no extra surface | flag | ✅ |
| R1 | flag, no extra surface | flag | ✅ |
| V1 | two flags, **no** surface | two flags, no surface | ✅ |
| N1 | zero items | zero | ✅ |
| X | contract unchanged | unchanged | ✅ |

**11 of 12.** Precision 18/19, recall 18/18 — one false positive, zero false negatives.

By origin: 4 production references, 7 dev, 1 unmapped. **The single false positive is the duplicate
`total`, and the key predicted it in advance as origin-independent.** Origin explains none of the
deviation, because there is no other deviation to explain.

## 6 · Boundary simulation

`boundary-simulation.php` — reads the real extractor, locator and resolver and asks what each
boundary *would* produce. No production file was edited to run it. Origin is computed from the
repository's own `composer.json`, the strongest form of the rule.

| Boundary | flags | fetched | tokens | FP | FN | key rows changed |
|---|---:|---:|---:|---:|---:|---|
| **B0 · current** | 8 | 8 | 314 | 1 | **0** | — |
| B1 · drop every dev-origin reference | 4 | 1 | 105 | 1 | **10** | T1b, T2, T3, H1, V1, **P2** |
| B2 · no surface for dev origin | 8 | 6 | 288 | 1 | 2 | T3 |
| B3 · no flag or surface for dev origin | 4 | 6 | 208 | 1 | 6 | T2, T3, H1, V1 |
| B4 · three-way by declaration | 8 | 6 | 288 | 1 | 2 | T3 — **identical to B2** |

Three findings.

**No boundary removes the false positive.** The only one present is the duplicate `total`, which no
origin rule touches. Every origin boundary is pure loss on this evidence.

**B1 damages a production row.** `Order`'s surface reaches the bundle through a `: Order` **return
type** in `tests/Support/CreatesOrders.php`. Dropping dev-origin references destroys the surface
that **P2, a production row**, demands. Origin-based suppression turns out to depend on which file
happens to carry the reference shape that resolves — and that is not a property anyone would choose
to depend on. B2 escapes this only by accident: it leaves the bare-class path alone, so had the
`: Order` return type been written in the controller instead, B2 would have lost it too.

**B4's third state buys nothing.** The only unmapped reference is a flag, and no boundary touches
flags, so the three-way rule is byte-identical to the two-way one. The extra distinction has no
measured effect to justify it.

## 7 · Decision

**Outcome C.** The evidence does not justify an origin distinction. No ADR, and production is
untouched — no file under `src/` or `bin/` was modified in this milestone.

Stated as the strongest fair summary of the case *for* a rule: the project declares the categories,
the tool already reads the declaration, and the mechanism would be clean. What is missing is not
mechanism but **value** — not one keyed row wants a different answer, and each boundary that acts on
origin loses context the key demands.

## 8 · What would reopen it

A key-first experiment containing a row where the same reference shape, same ownership, same member
state and same diff visibility yields a **different correct answer solely because of the origin
file**. This fixture was built to contain such a row if one exists — T3 against P2 — and keying it
honestly produced the same answer on both sides.

A scored Phase 1 run would also reopen it: if a reviewer, given the bundle, judges test-origin
surfaces to be noise, that is evidence of the kind this milestone could not manufacture. `X4` and
the requirements document both name scoring as the thing still missing.

## 9 · G4 — recorded, not fixed

`OrderCalculator::total` is named from a production file and from a test, and arrives in the bundle
**twice**: two identical slices, 50 tokens, for one fact.

Verified origin-independent on a scratch fixture built for the purpose: two **production** files
calling the same member produce the same two identical slices. So this is a **duplicate-subject**
defect, not an origin one. `NamedReferenceResolver` has no cross-assertion de-duplication for member
slices, and `BundleAssembler` does not de-duplicate either — M14's `$surfaced` guard covers only the
ADR-A020 surface fallback.

On M7's real pull request it costs nothing today: no member subject there resolves from two files.
It is written into `evidence-gaps.md` with a key-first trigger and asserted as-is by the lock test,
so fixing it is a deliberate act with its own key rather than a tidy-up inside another milestone.

## 10 · Correction to M14

M14 §9 stated that the `User` surface on M7's pull request is a **false positive**. That was
inferred from E5.12's rationale, and §2 shows the inference does not hold: E5.12's four named
references are all dependency classes, and M7's key contains **no row** deciding a project-model
reference from a test file. The honest description is **unkeyed**, not wrong — and M15's key, asked
the same question directly, answers FETCH.

M14's precision figure for M7 (**5/18**) counted the three `User` slices as errors. Recounted
against the key row by row, the 18 items are:

| | items | |
|---|---:|---|
| keyed **wanted** | 5 | E5.1 `Setting` ×2, E5.2 `MediaItem` ×2, E5.3 the premise flag |
| keyed **unwanted** | 5 | the Eloquent dispatch flags E5.6 and E5.7 name by region |
| **unkeyed** | 8 | `User` ×3, and the five items originating in `tests/Feature/MenuPdfTest.php` |

So `5/18` is a floor that charges every unkeyed item as an error, and `5/10` — precision over the
items the key actually decides — is the ceiling. The true figure is somewhere between, and the
honest statement is that **M7's key does not decide 8 of the 18 items it now produces**. That is a
gap in the key, recorded here rather than resolved by picking whichever number reads better. M14's
other measurements are unaffected. `docs/research/M14-*.md` §9 and
the `evidence-gaps.md` row it created are corrected in the same commit as this document.

## 11 · Regression, budget, determinism

No production change, so nothing should move, and nothing did.

- **M7 at 500, 2000 and 8000 is byte-identical to the M14 result** on both streams — item count,
  fetched count, flag count, token count, recall 3/4, precision unchanged as measured, 0 vendor items.
- Milestone fixtures M1–M14: 239 targeted tests, 1145 assertions, green.
- **Full suite: 1044 → 1052 tests, 4689 → 4707 assertions, 4 failures** — the same four pre-existing
  `ExperimentKeyTest` failures (private Phase 0 fixture repositories are not on this machine).
- Determinism, 5 runs each on both streams, 1 distinct result every time: experiment-15 JSON @8000,
  Markdown @8000, JSON @200 (**drops forced**), and M7 JSON @500 and Markdown @500.
- Budget: experiment-15 at 200 keeps 10 items / 198 tokens and records **9 drops** with reasons and
  token counts. Every flag survives — `ItemPriority::NEVER_DROPPED` — which is the D4 behaviour.

## 12 · Is M16 unblocked?

Yes. M15 blocks nothing and settles the question M14 left open, so no milestone is waiting on it.

## 13 · Recommendation for M16

Not started, per the milestone. In priority order:

1. **G4 — the duplicate member slice.** The only measured false positive left in the tool, cheap to
   key, and the fix is a de-duplication guard of the kind M14 already built for surfaces. It has an
   experiment-shaped question: *is one slice per (path, member) always right, or does a second
   origin deserve its own copy?* — and the flag side of M15's T2 row suggests the answer differs for
   flags and slices, which is worth keying rather than assuming.
2. **The retained flag's statement.** ADR-A016 records that it is arguably false for
   `Package::create`, and M14 put a surface beside it without improving the words.
3. **A scored run.** Both remaining questions in this document — G4's second copy, and whether
   test-origin surfaces are noise — are ultimately answered by a reviewer reading a bundle, which is
   the measurement Phase 1 has never taken.
