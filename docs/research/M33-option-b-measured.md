# M33 · Option B, built and measured: the inherited-member gap, closed at the engine and not at the key

> **Classification: the first engine change made in answer to a scored measurement, measured by
> the same instrument.** M30 found E5.4 (`ApiResponse::success`) missed; M31 found it missed
> again, held-out (H.3). ADR-A003's gate was opened, the first decision was **no change**, a
> reviewer pre-check reversed it, three gates were cleared in writing, a guard landed alone, and
> option B - a fourth recognised form, an eighth premise, and a verify-only ancestry walk - landed
> as one change (engine `6a77cdb`, `policy_version` 3). Re-run on the three recorded commits with
> the same inputs: **D1 606 → 730 tokens, ee5a2e6 1078 → 1393, 407c110 unchanged.** Scored
> against the locked keys: **key recall did not move on any run.** E5.4, H.3 and H.4 stay
> MISSED. The engine now says the true sentence - *`success()` is declared in trait
> `App\Traits\ApiResponse` at `app/Traits/ApiResponse.php:9`, body not fetched* - and the keys,
> written before the sentence existed, do not have a row it can satisfy. On D1 the two S1 claims
> score unkeyed; on ee5a2e6 the five score **FP**, because the key's H.11 lists the calling
> classes bare as OMIT and the scorer's bare-class rule claims them. **That second outcome was
> predicted wrong in ADR-B002** and is recorded as measured.
>
> Nothing was tuned. No key was edited. The cost is stated in tokens, per run.

## 1 · Objective

Close the loop that M30 opened. The programme's rule since ADR-A003 is that an engine change
needs an experiment behind it, a decision recorded before the code, and a measurement after,
by the same instrument that found the gap. This is the first time the whole loop has run on one
finding, and the write-up is as much about the loop as the number.

## 2 · The loop, step by step

| Step | Record | What it settled |
|---|---|---|
| Measurement finds the gap | [M30](M30-first-scored-bundle.md) §5.2 | E5.4 `ApiResponse::success` MISSED on D1 in both vendor modes. Traced to `OwnFileAssertionExtractor.php:85`: `$this->m(` to an undeclared member yields nothing, by design (P3) |
| Confirmed held-out | [M31](M31-held-out-scored-bundle.md) §4.1 | H.3 - the same trait, an independent commit, a key written from the diff alone. The gap is not an artefact of the in-sample key |
| Gate opened | ADR-A027 | Hypothesis and experiment only. Four options costed. **Decision: no change** - the flag was a true sentence for the reviewer population as then understood |
| Pre-check reversed it | ADR-A027 appendix | Seven of forty-five diff-only reviewer cells (M17–M22) had named the `Controller` → `ApiResponse` path; `ec92403` itself was named. **Option B became the candidate**, with two prerequisites |
| Gate 1 - the statement | ADR-A028 | ADR-A009's fixed-statement rule opened for one template. S1 (declared in a project ancestor) an item; S2 (ancestry leaves project code) a diagnostic, because S2's population is the test helpers no key asks for |
| Gate 2 - the form | ADR-A029 | `01-architecture.md` §3.3 opened for one row: `$this->m(` undeclared, subject the **calling class**, never the parent. `extends Name` is not a form. Shape 6 stays out. And: **three changes must land together**, or the form alone yields a false flag and the diff duplicated |
| Gate 3 - the guard | ADR-A020 addendum | The surface fallback never targets the assertion's own origin file. Landed **alone** (engine `f735dda`), content-neutral, verified by re-running every recorded bundle byte for byte |
| The scorer, first | backend ADR-B002 decision | `calling_subjects`: a key may predict the calling-class spelling. Re-scored all six pairs: nothing moved |
| Built | engine `6a77cdb`, architecture `e0c276f` | Form, premise, walk, golden, `POLICY` 3. 1263 tests |
| Gate 4, late | ADR-A024 addendum | The templated payload is additive; `bundle_version` stays 2. Written *after* the build - the wrong order, said so there |
| Measured | this | Below |

## 3 · What was built

- **The form.** `NamedReferenceAssertionExtractor` emits `{callingClass}::{member}` for a
  `$this->m(` whose `m` the file does not declare. Not gated by an empty `use` block. A declared
  sibling stays the own-file move's; a property read, a dynamic call, `parent::`/`self::`/
  `static::` and an anonymous class yield nothing.
- **The walk.** `AncestryResolver`: `extends` and `use <Trait>` through project files to a fixed
  point, traits before parent, one visited-set of type names, no hop count. It returns a citation
  (type, path, line) or the boundary it stopped at. A dependency trait or parent, an unplaceable
  or unreadable type, two declaring traits, an `insteadof`/`as` block, a cycle - each is a
  boundary, each has a test. `vendor/` is never opened. It constructs no assertion and returns no
  slice; D1 holds by construction and `Oq1DepthBoundaryTest` bounds what it may hold.
- **The premise.** `inherited-member-declared`, the eighth. S1 renders ADR-A028 §5's template:
  *`ASSUMPTION: success() is not declared in MenuPdfController or in its parent
  App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at
  app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified`* -
  62 tokens. S2 is a stderr line and no item. Null - project ancestry without the member -
  keeps `unresolved-reference`, now exactly true.

## 4 · Effect on the bundles

Same `input_key` as the recorded runs; `fetched` counts unchanged on every run; nothing removed
from any bundle.

| Commit | vendor | assertions | items | tokens | added |
|---|---|---|---|---|---|
| `ec92403` D1 | installed | 11 → 13 | 18 → 20 | **606 → 730** | 2 S1: `MenuPdfController::success` → `ApiResponse.php:9`, `::deleted` → `:28`, "used by that parent". 8 S2 on stderr |
| `ec92403` | none | 30 → 32 | 37 → 39 | 986 → 1110 | the same two; S2's reason becomes *cannot place* |
| `ee5a2e6` | installed | 18 → 23 | 19 → 24 | **1078 → 1393** | 5 S1: Admin `success`/`created`/`deleted`, Public `success` (→ `ApiResponse`), `PersonalityResource::resolveLocale` → `ResolvesLocale.php:7`, "used by the class itself". 3 S2 |
| `ee5a2e6` | none | 46 → 51 | 47 → 52 | 1638 → 1953 | the same five |
| `407c110` | both | 0 / 42 | 0 / 42 | 0 / 840 | nothing - no fourth-form call in its regions |

**Against the estimates.** ADR-A027 §7 estimated D1 at +10 fourth-form assertions: it is 2 S1 +
8 S2 = 10. ADR-A028 §4 estimated "S2 dominates": on D1 it is 8 to 2, and the split keeps those
eight - the `MenuPdfTest` and `RepositoriesTest` helpers, ~385 tokens as items - off the bundle
at zero cost. ADR-A028 §5 estimated the two-hop sentence at 61 tokens; it is 62. ADR-A029 §7
predicted the M26 golden would gain a third assertion; it did (2/22/562 → 3/23/624).

**One thing the estimates got wrong**, in ADR-A028 §5's illustration: the S2 walk on these test
classes stops at their own `RefreshDatabase` trait - a dependency on the class itself - before
any parent is opened, so the line reads *walked nothing*, not *not in its project ancestor
Tests\TestCase*. Same outcome; corrected there, dated.

## 5 · Scored

Reference first: the six recorded runs re-scored against their keys before the six new ones -
every number identical to M30, M31 and M32. Then the new runs, same keys, same scorer.

| Run | assertions emitted / TP / FP / unkeyed | assertion precision | key recall | items signal / noise / unkeyed (count) | item precision, count → tokens |
|---|---|---|---|---|---|
| `ec92403` installed, v0.2.0 | 11 / 2 / 9 / 0 | 18.2% | 3/4 | 6 / 12 / 0 | 33.3% → 43.1% |
| `ec92403` installed, **option B** | 13 / 2 / 9 / **2** | **18.2%** | **3/4** | 6 / 12 / **2** (124 t) | 33.3% → 43.1% |
| `ec92403` none, v0.2.0 | 30 / 2 / 23 / 5 | 8.0% | 3/4 | 6 / 26 / 5 | 18.8% → 29.5% |
| `ec92403` none, option B | 32 / 2 / 23 / **7** | 8.0% | 3/4 | 6 / 26 / **7** (224 t) | 18.8% → 29.5% |
| `ee5a2e6` installed, v0.2.0 | 18 / 7 / 7 / 4 | 50.0% | 3/7 | 5 / 7 / 7 | 41.7% → 72.7% |
| `ee5a2e6` installed, **option B** | 23 / 7 / **12** / 4 | **36.8%** | **3/7** | 5 / **12** / 7 | **29.4% → 45.6%** |
| `ee5a2e6` none, v0.2.0 | 46 / 7 / 35 / 4 | 16.7% | 3/7 | 5 / 35 / 7 | 12.5% → 35.4% |
| `ee5a2e6` none, option B | 51 / 7 / **40** / 4 | **14.9%** | 3/7 | 5 / **40** / 7 | 11.1% → 27.5% |
| `407c110` installed, both | 0 | n/a | 0/2 | 0 | n/a |
| `407c110` none, both | 42 / 0 / 42 / 0 | 0.0% | 0/2 | 0 / 42 / 0 | 0.0% |

Unkeyed assertions and items are excluded from the precision ratios, as ADR-B002 §2 defines them;
that is why D1's ratios do not move while its bundle grew by 124 tokens. Key recall: E5.4, H.3,
H.4 **MISSED** on every run, before and after. `calling_subjects` matched zero times. OMIT rows
violated: the same sets before and after on every run.

### 5.1 · D1: unkeyed, as predicted

No experiment-05 row names `MenuPdfController`. The two S1 assertions and their two 62-token
items are unkeyed - the scorer has nothing to say about them. E5.4 stays MISSED because its row
asks for `App\Traits\ApiResponse::success` FETCHed, and what arrived is a flag under a different
subject. ADR-B002's decision of 2026-09-24 said this is what would happen without a
calling-class prediction in the key, and it did.

### 5.2 · ee5a2e6: FP, which was predicted wrong - the headline

ADR-B002's open question (2026-09-23) said *"every S1 assertion would score unkeyed"*, and gave
the reason: the bare-class rule *"matches a row's class against an assertion's member, not the
reverse"*. The reason is right and the conclusion was wrong. The held-out key's **H.11** - OMIT,
*"the other classes this PR creates"* - lists `Admin\PersonalityController`,
`Public\PersonalityController` and `PersonalityResource` as bare classes. An S1 subject is a
member of exactly those classes, so the bare-class rule claims all five at its precedence: **FP**,
items **noise**, 315 tokens. Assertion precision 7/14 → 7/19; item precision by tokens 72.7% →
45.6%.

Read carefully, both halves are true at once. The scorer is right under §2: H.11 is a row, the
assertion is a member of its class, precedence is applied blindly, and a rule that bent for this
case would be the tuning §5 forbids. And the key's author, writing H.11 on 2026-09-23 from the
diff, meant *do not fetch these classes' surfaces* - a claim about an inherited member of those
classes did not exist as a shape to predict. **The key is locked and stays as written.** The
number stands: under this key, option B costs ee5a2e6 13 points of assertion precision and buys
no recall. That is the measurement. What it means for the next key is a transcription-note
question (should a bare-class OMIT row claim a member-level assertion of a form the author could
not foresee?), recorded in ADR-B002 and not answered here.

### 5.3 · What did not move

`407c110`, both modes: byte-identical scores. The change touched nothing it should not have.

## 6 · Stated plainly

1. **The engine now says the true sentence and cites the file.** Before, `$this->success(` was
   silence (E5.4, H.3, H.4 - three keyed rows across two commits, and seven reviewers). After, it
   is a flag that names `app/Traits/ApiResponse.php:9`. Whether a reviewer with that sentence in
   front of them does better is **not measured here** - it is ADR-A027 §7's experiment, a
   reviewer with the bundle, and it has not been run.
2. **The keys cannot see it.** Both keys were written before S1's spelling existed. D1's key has
   no row; ee5a2e6's has the wrong kind of row. Key recall is unchanged everywhere. By the
   scorer, option B is a cost with no gain: +124 tokens on D1, +315 on ee5a2e6, precision down on
   ee5a2e6.
3. **The scorer did what ADR-B002 said it would, where ADR-B002 was right, and the place it was
   wrong is recorded.** The H.11 interaction is the finding: a locked OMIT row written against
   one shape of assertion claims a later shape by a precedence rule that was correct for
   everything before it.
4. **Every estimate but one held.** +10 on D1, S2 dominating 8:2, 62 tokens for 61, the golden's
   third assertion. The one that did not - the S2 stop point - changed no outcome.
5. **The cost of the loop.** From M30 (2026-09-22) to here: one decision reversed by evidence,
   four ADRs and three addenda, one guard landed alone, one commit of engine code, and a
   measurement that says the instrument now needs a row the author could not have written.

## 7 · What this does not show

- Not whether the sentence helps a reviewer. That is the next experiment and the only one that
  would justify the tokens.
- Not option B's precision on a key written *with* `calling_subjects`. No such key exists; the
  first one written that way will be the first fair score, and it must be written from the diff
  before the run, as ADR-B002 requires.
- Not anything about the S2 population beyond its size: eight on D1, three on ee5a2e6, all test
  helpers, all zero tokens. ADR-A028 §4's split is measured as a cost avoided, not as a benefit.
- n = 3 commits, 2 of them from one author's repository, 1 with nothing to discover.

## 8 · Regression

The v0.2.0 runs re-scored to their recorded numbers before anything new was scored. The ADR-A020
guard, landed alone, reproduced every recorded bundle byte for byte (ADR-A020 addendum; engine
`f735dda`). Under option
B, `fetched` counts are unchanged on every run - no surface, and no body of any ancestor, entered
any bundle. `407c110` is byte-identical in score.

## 9 · Remaining gaps

| Gap | Where it is recorded |
|---|---|
| A reviewer-facing measurement of S1 - does the citation change what a reviewer finds? | ADR-A027 §7; not run |
| A key written with `calling_subjects` before the run, so S1 can score TP | ADR-B002 decision, "for the next key" |
| Whether a bare-class OMIT row should claim a member-level assertion of a later form | ADR-B002 measured note, 2026-09-26; a transcription-note question |
| The E5.4 / H.3 FETCH rows themselves - a reviewer who needed the *body*, not the citation | ADR-A010 §4 forbids the fetch; `evidence-gaps.md` §4 records the under-build and its trigger |
| The S2 population as a possible framework-table settlement (option D) | ADR-A028 §4, ADR-A011-gated |

## Related

- [M30](M30-first-scored-bundle.md), [M31](M31-held-out-scored-bundle.md),
  [M32](M32-migrations-only-commit.md) - the measurements that found and confirmed the gap.
- Architecture: ADR-A027 (+appendix), ADR-A028 (+corrections), ADR-A029, ADR-A020 addendum,
  ADR-A009 addendum, ADR-A010 addendum, ADR-A024 addendum.
- Backend: ADR-B002 (open question, decision, measured note); keys `experiment-05`,
  `held-out-ee5a2e6`, `held-out-407c110`, all locked.
- Engine commits `f735dda` (the guard), `6a77cdb` (option B).
- Runs (backend): `01m3a6ttxanpprm8e00rfeh8be`, `01m3a6tyz42a09cv1brkfn9fm6` (D1);
  `01m3a6tzg860d5yekd759yhdkc`, `01m3a6v3pnnnqxnvxerdgbd9kp` (ee5a2e6);
  `01m3a6v47zs30sht9zzsbzkjzq`, `01m3a6v87bkk99rpqc07n050qs` (407c110).
  `01m3a6vcypp1q2q838328c9dcq` is a byte-identical duplicate of the first, from a second
  invocation; it is not scored.

---

## Addendum · 2026-09-26 · §5.2 re-read against a key that could name the shape

**Source:** [M34](M34-the-first-fair-key.md). The text above is unaltered.

A fourth held-out key, `held-out-9b8f9c6`, written knowing the fourth form exists and carrying
37 predicted `calling_subjects`, scored option B on the same engine, the same change and the
same trait: **0.0% → 57.8%**, 37 of 37 spellings matched, zero unkeyed, H.11's prefix rule never
reached. §5.2's 50.0% → 36.8% therefore stands as measured and is now read correctly: it was the
key's shape, not option B's cost - **without M34 it would have been read as option B's cost.**
§5's invariant holds on both: key recall does not move, because an S1 citation satisfies no
FETCH row while ADR-A010 §4 stands, and the K.1 row is the first that measures that bound on its
own. New at M34: the citation repeats - 2,319 tokens for four facts across 37 sites - which §4's
two- and five-site commits could not show. Recorded there as an open question.

---

## Erratum · 2026-09-26 · "ADR-A027 §7's experiment" is the two-author key experiment, not a reviewer measurement

**What was wrong, and where.** §6 item 1, §7 first bullet and §9 first row cite *"ADR-A027 §7's
experiment, a reviewer with the bundle"*. ADR-A027 §7 as written designs a **two-author key
experiment** - two keys per commit, two authors, scored by the existing scorer. It does not
design a reviewer-facing measurement; that measurement was implied by §6's pre-check (seven of
forty-five diff-only reviewer cells) and designed nowhere until [ADR-E001](../decisions/ADR-E001-reviewer-measurement-of-the-citation.md)
(this repository, `docs/decisions/`, 2026-09-26). The citations above should be read as *"the reviewer
measurement ADR-A027 §6's pre-check calls for, designed in ADR-E001"*. The claim they support -
that no reviewer evidence exists - is unchanged. The text above is unaltered.

