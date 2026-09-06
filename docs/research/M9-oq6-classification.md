# M9 · OQ6 — unresolved reference classification

- **Status:** Research and decision complete. **No production code was changed.**
- **Date:** 2026-09-05
- **Decision:** [ADR-A016](../../../context-discovery-architecture/architecture/decisions/ADR-A016-oq6-the-available-facts-cannot-classify.md) — **OQ6 closed as unresolvable on the current evidence**
- **Fixture:** [`tests/Acceptance/fixtures/experiment-09/`](../../tests/Acceptance/fixtures/experiment-09/)

---

## 1 · Hypotheses, stated before the experiment

| | Hypothesis | Result |
|---|---|---|
| **H1** | An unresolved member on a class that *was* located is distinguishable from a completely unknown class | **TRUE**, but the only channel that could carry the distinction is the stderr diagnostic — and it currently reports both as `missing PSR-4 entry` |
| **H2** | Knowing a class is an Eloquent model gives enough evidence to classify conventional Eloquent statics | **FALSE** — disproved below |
| **H3** | The evidence cannot separate `Package::create` from `Package::activatte`, so any framework-like classification is unsafe | **TRUE** — proved below |

## 2 · What is in hand when resolution gives up

Read out of the current `NamedReferenceResolver`, in the order it decides:

| # | Fact | Where it comes from |
|---|---|---|
| 1 | a PSR-4 prefix covers the class name | `ClassLocator::pathFor()` |
| 2 | the file that prefix points at exists | `pathFor()` checks `exists()` |
| 3 | the path is project source or a dependency's | `isProjectSource()` (ADR-A012) |
| 4 | the full text of that **one** file | `SourceRepository::text()` |
| 5 | the member is declared in it | `MemberSlicer::member()` |
| 6 | `scope<Name>` is declared in it | `FrameworkKnowledge::sameFileMemberFor()` (ADR-A011) |
| 7 | the file is a `Facade` subclass with a matching `@method static` tag | `FrameworkKnowledge::declarationOf()` (ADR-A011) |

Deliberately unavailable: any second file — the parent, a trait, `Eloquent\Builder`, `Query\Builder`
(ADR-A010, reaffirmed by ADR-A015).

## 3 · The experiment

`experiment-09`, key-first, twelve rows. **All twelve match the current behaviour**, with
`Package::create` taking the safe FLAG:

| id | subject | expected | actual |
|---|---|---|---|
| OQ6.1 | `Helper::run` — declared | FETCH | FETCH ✓ |
| OQ6.2 | `Helper::nope` — unknown member | FLAG | FLAG ✓ |
| OQ6.3 | `Package::create` — Eloquent conventional | OMIT ideal / FLAG safe | FLAG ✓ |
| OQ6.4 | `Package::activatte` — **typo** | FLAG | FLAG ✓ |
| OQ6.5 | `Missing::create` — unknown class | FLAG | FLAG ✓ |
| OQ6.6 | `Helper::slug` — project member, framework-sounding name | FETCH | FETCH ✓ |
| OQ6.7 | `Log::info` — documented facade member | OMIT | OMIT ✓ |
| OQ6.8 | `Package::totallyUnknownThing` | FLAG | FLAG ✓ |
| OQ6.C1 | `Helper::Run` — capitalisation | FLAG | FLAG ✓ |
| OQ6.C2–C4 | comment, docblock, string literal, `::class` | OMIT | absent ✓ |

`items 9 · fetched 2 · flagged 7 · dropped 0 · used_tokens 239`

## 4 · The proof

The three rows that decide OQ6, evaluated against every available fact:

| Member | f1 prefix | f2 file | f3 project | f5 declared | f6 scope | f7 facade | Correct answer |
|---|---|---|---|---|---|---|---|
| `Package::create` | yes | yes | yes | no | no | no | **OMIT** |
| `Package::activatte` | yes | yes | yes | no | no | no | **FLAG** |
| `Package::totallyUnknownThing` | yes | yes | yes | no | no | no | **FLAG** |

**Three references, one fact-set, three different correct answers.** The facts do not vary while the
answer does, so no function of them can produce the right classification. This refutes any rule of
this shape in advance, without needing to see the rule.

## 5 · The two facts that keep being confused

`Package.php` declares `class Package extends Model`. That is real, in-file, deterministic evidence —
**that the class is an Eloquent model**. It is not evidence that `create` exists.

`Illuminate\Database\Eloquent\Model` carries **0** `@method` tags and **0** `@mixin` tags. So unlike
a facade there is no declarative surface to read. Row OQ6.7 shows what a proven framework member
looks like: `Log.php` declares `class Log extends Facade` **and** carries
`@method static void info(...)` — a positive, machine-readable, *member-level* fact.

That asymmetry is the whole of OQ6's answer, and it retires M0's proposed rule **L1** as unsafe
rather than merely unbuilt.

## 6 · Real-application measurement (M8's numbers reproduced)

Every static call on a project class in `abouelsid-backend/app`:

| Category | M8 | M9 | |
|---|---:|---:|---|
| member declared in the named class | 73 | **73** | already fetched |
| one hop to a project parent/trait | 0 | **0** | |
| one hop to a dependency parent | 21 | **21** | ADR-A015 gap |
| Eloquent dynamic | 96 | **96** | across 14 distinct member names |

Correction to M8's figure: of those 96, **2 are `forPage`**, which is a `scope` and is already
resolved correctly by ADR-A011's convention rule. The genuinely unresolvable population is **94**.
Most common: `findOrFail` (37), `create` (13), `where` (13), `count` (9), `with` (8), `orderBy` (6).

Had option C been adopted — accept any static member on a class extending `Model` — it would have
accepted all 94 **and** every typo. Candidates 94, accepted 94, remaining flags 0, and the false-
positive count is unmeasurable precisely because the rule cannot tell a typo from a method.

## 7 · Options

| | Option | Precision | Typo-safe | Verdict |
|---|---|---|---|---|
| A | keep all unresolved as FLAG | unchanged | yes | **Chosen** |
| B | classify using existing framework knowledge | — | — | Impossible: Eloquent has no `@method`/`@mixin` to read |
| C | bounded Eloquent convention | −94 false flags | **no** | Refuted by §4 |
| D | new assertion/extraction move | — | — | Out of scope (G1/G5), ADR-A003 gated |
| E | change the unresolved-reference classification | — | — | Blocked twice: splitting needs a new premise (excluded here); broadening already rejected by ADR-A009 |
| F | sharpen the stderr diagnostic (not a premise or field) | truthfulness | yes | **Not taken** — see §8 |

## 8 · The one real finding, deliberately deferred

Fact 2 *is* a distinction the tool already makes and reports wrongly. `App\Ghost\Missing::create`
emits `missing PSR-4 entry` although the `App\` prefix plainly exists and it is the **file** that is
absent. M1's answer key recorded this as row **S08.1**.

Not taken in M9 because it is a diagnostic-wording defect rather than a classification rule,
`experiment-09`'s key did not key diagnostic text, and M8 has just set the precedent of not acting on
findings that surface during measurement. Recorded as the next milestone's cheapest item, to be done
key-first.

## 9 · Regression and validation

| Check | Result |
|---|---|
| `Log::info`, `Str::slug`, `Arr::only`, `Package::active`, `MissingGateway::resolve` | unchanged |
| **`Package::activatte`** | **still flagged** |
| `Package::create` / `::where` / `::orderBy` / `::query` | still flagged, no bundle growth |
| vendor/framework source in bundle (experiment-09 + 30 M1 runs) | **0 items** |
| Determinism — 5× JSON, 5× Markdown, 5× at a drop-forcing budget | stdout, stderr, ordering, tokens, `dropped[]` byte-identical |
| M1 / M2 / M3 / M4 / M5 / M6 | OK |
| Full suite | **980 tests, 4501 assertions, 4 failures** — identical to the M8 baseline |

## 10 · G1 and G5 were not folded in

No extraction change. `Setting::updateOrCreate` and `MediaItem::where` still do not become
model-surface references; `$this->inheritedMember()` is still silent. Both remain separate
ADR-A003-gated questions.

## 11 · Outcome

OQ6 is **closed, not deferred**. The value of the milestone is a proof that removes a plausible line
of work permanently: the evidence available at resolution time cannot distinguish a framework-supplied
member from one that does not exist, and no amount of care in rule design changes that, because the
inputs are identical.

**No production code was changed.** Created: `tests/Acceptance/fixtures/experiment-09/` and this
document. Modified: `docs/research/README.md`, and in the architecture repository
`decisions/ADR-A016-…md` (new), `README.md`, `evidence-gaps.md`.
