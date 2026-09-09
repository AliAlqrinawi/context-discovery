# M26 · A changed return contract, and the first precision number for the caller grep

## 1 · Classification

**A — the move is implemented, and the imprecision it inherits is now measured rather than assumed.**

The tool catches the defect it was built for: the one genuine caller of the changed method is in the
bundle. It reaches that caller inside a set of twenty fetched slices of which **nineteen are not
callers of it**, and that ratio — **1/20** — is the first precision number anyone has for
`CallerResolver`, on any move. It is recorded here because [ADR-A006](../../../context-discovery-architecture/architecture/decisions/ADR-A006-grep-not-graph.md)
accepted the bounded grep *on the condition that its cost be measured*, and until now nothing had
measured it.

This document proposes nothing. The move it describes was already gated through
[ADR-A023](../../../context-discovery-architecture/architecture/decisions/ADR-A023-changed-return-contract.md);
this is the write-up that gate required and did not have.

## 2 · The case

One line of a real Laravel repository (`abouelsid`, Laravel 12), in
`app/Repositories/BranchRepository.php`:

```php
     public function getAll(?bool $showInFooter = null): Collection
     {
         $query = Branch::orderBy('order');

-        return $query->get();
+        return $query->first();
     }
```

The declared signature does not move. The declared return type still reads `: Collection`, and it is
now a lie the language will not catch — `first()` returns a model or null. Every call site that
iterates the result is broken, and **not one of those call sites is in the diff**.

**Why the existing move could not see it.** `ChangedSignatureAssertionExtractor` compares the
parenthesised parameter span and nothing else; its docblock says so in as many words: *"Only the
parameter list decides. A body-only change … produces nothing."* The omission is structural rather
than an oversight — for a body-only change `UnifiedDiffParser` records **zero** `ChangedMember`s,
because a member's declaration survives only in the discarded `@@` header text. There is no signature
pair to compare, so widening the existing extractor would not have reached this either.

## 3 · The decision

Recorded in full in ADR-A023; in brief:

- a sixth `AssertionKind::ChangedReturnContract`, raised by a fifth extractor,
  `ChangedReturnContractAssertionExtractor`, when a region removes a `return` and adds one, both
  terminal calls are names a **closed** framework cardinality table classifies, the two classes
  differ, and the added return is the member's **own**;
- resolution reuses the existing `CallerResolver` rather than adding a fourth resolver. Both kinds
  ask the one question a bounded grep answers — *who calls this member?* — so the resolver count
  stays at **three** while the extractor count goes to five.

**Conformance.** *P3* — depth is untouched: one search, one scope, one bound, and the callers of a
caller are not followed. *P6* — the engine judges nothing; the closed table supplies the naming fact
and the extractor supplies none, and an unknown finisher yields silence rather than a guess. *P8* —
three consecutive runs of the reproduction below produce a byte-identical bundle.

## 4 · The measurement

This is the point of the document.

### 4.1 · The reproduction

```
./bin/context-discover \
  --diff /tmp/abouelsid-m26.diff \
  --repo <abouelsid-backend, post-image> \
  --budget 8000 \
  --format json
```

```
exit 0
bundle_version 1 · budget_tokens 8000 · used_tokens 562 · items 22 · dropped 0
stderr: call sites truncated at 20 for getAll under app/
```

One assertion is raised — one distinct `reason` — from the one changed repository file. It carries
21 items. The 22nd item is an unrelated `named_reference`.

### 4.2 · What the 21 items actually are

| | Count | What it is |
|---|---:|---|
| True caller | **1** | `app/Actions/Branch/GetBranchesAction.php:25`, whose constructor declares `readonly BranchRepository $repository` |
| Call sites of `getAll` on an **unrelated** repository | 13 | 9 Actions (`CateringPackage`, `SampleMenu`, `DeliveryApp`, `Dish`, `Personality`, `Setting`, `Testimonial`, `Timeline`, `User`) and 4 Controllers (`Category` ×2, `QuoteRequest`, `SeoSetting`) |
| Method **declarations**, not calls | 6 | `BranchRepository`'s own, plus `Category`, `CateringPackage`, `DeliveryApp`, `Dish`, `Personality` |
| Truncation flag (`lever: flagged`) | 1 | `ASSUMPTION: additional call sites exist beyond the search bound; not all verified` |
| | **21** | |

Every receiver was resolved by reading the declared constructor property, not inferred from the file
name.

> **`CallerResolver` precision on this move: 1 / 20 fetched items = 5%.**

### 4.3 · The cost, in tokens

| Assertion | Items | Tokens | Share of `used_tokens` |
|---|---:|---:|---:|
| `changed_return_contract` | 21 | **324** | **57.65%** |
| `named_reference` (`BranchResource::toArray`) | 1 | 238 | 42.35% |
| | 22 | 562 | 100% |

The assertion with 5% precision consumes **~58%** of the budget actually spent.

### 4.4 · The imprecision is the grep's, not the new move's

`ScopedGrepCallSiteSearch` matches `/\b<name>\s*\(/` — the **bare member name**, with no class
context — and says so in its own docblock: *"A match is any occurrence of the identifier followed by
`(`, so results include a same-named method on an unrelated class — and the changed method's own
declaration. Both are recorded rather than filtered."* `Assertion->subject` is likewise a bare string,
never class-qualified.

Measured as an A/B on a purpose-built two-repository fixture, a **`ChangedSignature`** change to the
same member fetches exactly the same four locations as a `ChangedReturnContract` change: the true
caller, an unrelated Action, the changed method's own declaration, and an unrelated repository's
`getAll` declaration. The breadth is inherited, not introduced. `ScopedGrepCallSiteSearch` was last
modified by commit `d3ca2c9`, months before this move.

### 4.5 · Concrete evidence

`app/Repositories/DishRepository.php:10` is in the bundle:

```php
    public function getAll(array $filters = []): LengthAwarePaginator
```

A **different return type entirely** — a paginator, not a collection — on a different class, matched
purely because the identifier is `getAll`. No stronger demonstration of name-only matching exists in
this corpus.

### 4.6 · The bound, and a piece of luck

`app/` holds **27** occurrences of `getAll(` across 27 files; `--max-call-sites` is 20, so 7 were
truncated and the flag says so (P10 — nothing is dropped silently). All 7 lost were unrelated
repository declarations, so nothing of value was lost **here** — but only because the true caller,
`app/Actions/Branch/GetBranchesAction.php`, happens to sort **first** lexicographically. Noise and
signal compete for the bound on equal terms. For a changed class whose caller sorts late, 19
unrelated hits could push it past the bound; the flag would still fire, so the loss would be
announced rather than silent, but the reviewer would receive noise in its place.

## 5 · What the bundle does not carry

Observations, recorded as inputs to a later milestone — not defects to fix here.

- **No explicit `subject` field.** The subject (`getAll`) exists only inside the English `reason`
  string. Anything downstream that wants it must parse prose.
- **No assertion entity.** The 21 items are one logical assertion, but the JSON has no grouping
  construct; that they belong together is inferable only from identical `reason` text.
- **`provenance.member` is inconsistent by design.** Present on the flagged item and on the
  `named_reference`; absent on all 20 fetched call sites, because `CallerResolver` builds every slice
  with a null member — a call site is a line, not a member. Correct per the schema, but it means the
  fetched items carry no machine-readable link back to what they are evidence *about*.

## 6 · Known limits

Stated in full in ADR-A023 and not restated here. In summary: `findOrFail` violated the same
name-alone invariant that removed `find` — Laravel declares the identical conditional return and its
body delegates to `find($id, $columns)` — and was taken out during review, so `e36c364` ships the
table without it. `get` remains a pragmatic exception, since `Collection::get($key)` and
`Cache::get($key)` both return a single value. `value()` sits on the scalar/one boundary. A hunk
changing returns in two members can merge both cardinality classes into one member's description.

One limit deserves emphasis because it is invisible in output: **the post-image tree is assumed,
never enforced.** `LocalSourceRepository` validates only that `--repo` is a readable directory; the
tool shells out to nothing and reads no git object database. Pointed at a pre-image tree, the added
line is simply not found, and the run yields a quietly smaller bundle — no warning, no diagnostic, no
non-zero exit. The contract is stated in `03-interfaces.md` §1; nothing checks it.

## 7 · Regression and determinism

Full suite **1137 tests, 5041 assertions, 4 failures** — the four `ExperimentKeyTest` experiments,
which fail at their fixture gate (`fixture diff absent — acceptance not run`) because the Phase 0
Laravel + Plaid corpus is not checked in. Identical at baseline; unrelated to this move.

Determinism: three consecutive runs of the reproduction produced one SHA. `git diff --check` clean.

## 8 · References

- [ADR-A006](../../../context-discovery-architecture/architecture/decisions/ADR-A006-grep-not-graph.md)
  — the bounded grep, accepted deliberately and on the condition that its cost be measured. This
  document is the first measurement.
- [ADR-A023](../../../context-discovery-architecture/architecture/decisions/ADR-A023-changed-return-contract.md)
  — the design, the cardinality table, and the accepted limits in full.
- Commits: `e36c364` (implementation) and `ffa6520` (kind-order sync) in `context-discovery`;
  `638694c` (ADR-A023 and the spec updates) in `context-discovery-architecture`.
