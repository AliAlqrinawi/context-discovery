# M34 · The first key that could name what the engine emits

> **Classification: a held-out, key-first measurement of option B on a key written knowing the
> fourth form exists.** `9b8f9c6` - fourteen controllers swapping hardcoded response messages
> for `__('messages.*')`, 56 calls to `ApiResponse` helpers the calling files do not declare -
> scored against a ten-row key carrying 37 predicted calling-class spellings, locked at import
> before any run. **Same engine, same change, same trait as M33:** on `ee5a2e6` option B read as
> 50.0% → 36.8%; here it reads as **0.0% → 57.8%**. Neither number is about the engine. Both are
> about whether the key could name what the engine emits. **Without this key, ee5a2e6's
> thirteen-point drop would have been read as option B's cost.** The invariant across both:
> key recall does not move. Recognition is complete - 37 of 37, every one resolved to the right
> file and line - and delivery is bounded by ADR-A010 §4, not by the extractor. The 37 citations
> cost 2,319 tokens to say four facts; that is recorded as a new open question, not a defect.
>
> Nothing was tuned. The key was locked before the engine ran. No key was edited.

## 1 · Objective

M33 left two readings of option B's ee5a2e6 result standing side by side: a precision loss, or a
key that could not name the new shape. The keys in hand all predated the fourth form, so they
could not separate the readings. This measurement is the separation: a key written from the diff
alone, *knowing* form 4 exists and *predicting* its spelling (ADR-B002's `calling_subjects`),
with every bare-class OMIT row saying whether it means members too (after H.11).

## 2 · The commit

`9b8f9c6` - *feat: add bilingual lang files (ar/en) for validation and messages* - chosen by the
user from the twelve census-only commits as one of two containing the fourth form's shape, and
the one with 56 instances of it. Nineteen files, +248/−141: fourteen controllers replace a string
literal with `__('messages.…')` in the message argument of `success()`, `created()`, `deleted()`
or `paginated()`; two `messages.php` files and `lang/en/validation.php` are created;
`lang/ar/validation.php` is rewritten; `config/app.php` flips the default locale to Arabic. Named
in no ADR, write-up, key, README, TSV or analysis before this; its M18/M24 census output was not
opened.

## 3 · The key

`held-out-9b8f9c6`, ten rows: **1 FETCH · 2 FLAG · 7 OMIT**. Written on 2026-09-26 from
`git diff 9b8f9c6^ 9b8f9c6` and the sources the diff names at that commit - the fourteen
controllers' headers, `Controller.php` (abstract, `use ApiResponse`, nothing else),
`ApiResponse.php` (the four signatures). Four tree searches at the commit were disclosed in the
key, each for one row's `why` and none for a subject. No later commit is cited. It lives in the
backend (`docs/keys/held-out-9b8f9c6.json`, backend commit `62236d5`), imported with
`runs_existing_at_write = []` and locked at import.

**Grouping.** The 56 calls are 37 distinct (controller, member) pairs across 15 hunks, and all
56 ask one question - is `$message` a `string` parameter at the position used, given `__()`
returns array|string|null - so they are one row, **K.1**, FETCH, `subjects` the declaring site
(as E5.4 and H.3 wrote it) and `calling_subjects` the 37 spellings the author expected the
engine to emit, enumerated from the diff. K.1's `why` states the expectation in advance: TP on
every assertion, noise on every item by lever, row MISSED - and that the miss would measure the
fetch ban. The fifteen resource classes re-stated as unchanged arguments are one row (K.2, OMIT,
*the classes and any member*), `CategoryRepository::getAll` one (K.3, OMIT, likewise). K.4 and
K.5 are FLAG rows with non-catalogue identifiers, H.7's shape: the Arabic rewrite drops twelve
keys whose users (three form requests with `'array', 'min:1'`, six attribute labels in use) are
outside the diff; the config default's readers are outside it too. Eight hedges are in the file.

## 4 · Result

Both vendor modes are byte-identical per engine - no changed line names a dependency class.
v0.2.0 was run beside option B so the "before" is measured, not subtracted.

| Run | assertions emitted / TP / FP / unkeyed | assertion precision | key recall | items signal / noise / unkeyed | tokens |
|---|---|---|---|---|---|
| v0.2.0 `e7c919d`, both modes | 27 / 0 / 27 / 0 | **0.0%** | 0/3 | 0 / 26 / 0 | 2,403 |
| option B `6a77cdb`, both modes | 64 / **37** / 27 / 0 | **57.8%** | 0/3 | 0 / 63 / 0 | 4,722 |

Option B added exactly the 37 S1 items (2,319 tokens) and nothing else: all 26 v0.2.0 items are
present byte for byte in the option B bundle. The 27 FP are the same on both engines - fifteen
bare resource classes, nine `::collection` members, `CategoryRepository::getAll` - every one a
subject K.2 or K.3 lists by name, and every one noise by the key's own design (nine 20-token
`unresolved-reference` flags and seventeen fetched resource `toArray` surfaces at 2,223 tokens,
the ADR-A020 fallback on `XResource::collection`, ADR-A015's shape).

### 4.1 · The contrast

| | `ee5a2e6` - key predates form 4 (M33) | `9b8f9c6` - key knows form 4 |
|---|---|---|
| S1 assertions | 5, all **FP** through H.11's bare-class prefix | 37, all **TP** through `calling_subjects` |
| assertion precision, v0.2.0 → option B | 50.0% → **36.8%** | 0.0% → **57.8%** |
| key recall | 3/7, unchanged | 0/3, unchanged |
| what the FETCH row's miss means | ambiguous: the fetch ban, or the key's shape | unambiguous: the fetch ban |

Same engine, same change, same trait, same four sentences. On one commit option B is a
thirteen-point loss; on the other a fifty-eight-point gain. **Neither number is about the
engine.** Both are about whether the key could name what the engine emits: ee5a2e6's H.11 was
written to say *do not fetch these classes' surfaces* and the scorer's prefix rule, correctly
under ADR-B002 §2, applied it to a member-level claim the author could not have foreseen;
9b8f9c6's K.1 was written with the shape in hand. **Without this key, the thirteen-point drop on
ee5a2e6 would have been read as option B's cost.** It was the key's, and this is the measurement
that shows it.

### 4.2 · The invariant

**Key recall does not move on either commit.** H.3 and H.4 stayed MISSED on ee5a2e6; K.1 is
MISSED here with all 37 of its assertions TP. Recognition is complete: every one of the 56 call
sites became an assertion, every assertion resolved to the right file and the right line - four
citations, `ApiResponse.php:9`, `:21`, `:28`, `:38`, for four members - and zero settled as S2
(`Controller` carries only the project trait). Delivery is bounded by **ADR-A010 §4**, which
forbids fetching an ancestor's body: an S1 citation is a flag, and a flag satisfies no FETCH row.

This is the first key that can distinguish the two, because TP-on-assertion and MISSED-on-row
now point at different things. On every earlier key the FETCH row's miss could have meant the
extractor never saw the call, or the resolver could not place it, or the key could not name it,
or the architecture withheld it. Here three of the four are measured out, and what remains is a
decision - recorded, deliberate, and reopenable only on its own trigger (`evidence-gaps.md` §4).

### 4.3 · The spelling prediction

37 of 37 `calling_subjects` matched, each at `calling-class subject` precedence, zero unkeyed
assertions on either run. ADR-B002 placed the risk of the spelling on the author; on this key the
risk carried none. The shape - the changed file's own FQCN plus the member, enumerated from the
diff's added lines with namespaces read from each file's header - is predictable from the diff
alone, before the run, which is the property the key-first rule needs.

### 4.4 · The repetition cost - a new open question

The 37 S1 items cost **2,319 tokens to state four facts**: `success()` is declared at
`ApiResponse.php:9` (said nine times), `created()` at `:21` (twelve), `deleted()` at `:28`
(eleven), `paginated()` at `:38` (five). Each sentence is true, each is on the right assertion,
and each after the first adds nothing a reviewer did not already have from the first.

This did not surface on D1 (2 calls, 124 tokens) or ee5a2e6 (5 calls, 315 tokens). It scales
with the number of call sites while the information is constant - a property no earlier
measurement could show, because no earlier commit had more than five sites. ADR-A021's identity
rule does not collapse them: the items differ in provenance (fourteen origin files), and the
rule collapses only what a reviewer cannot tell apart.

Two responses are possible, and this write-up chooses neither:

- **Aggregation** - one citation per declaring member rather than per calling site, so the four
  facts cost four sentences. A change to what an item is, gated by ADR-A021 (identity) and
  ADR-A024 (the contract), with the question of where the collapsed provenance goes.
- **The reviewer measurement of ADR-A027 §7** - a reviewer with the bundle in front of them,
  which is the only evidence that could say whether 37 citations, or 4, or 0, change what is
  found. Until it runs, the token count is a cost with no measured benefit on either side.

Recorded as an open question, not a defect: the engine did what it was built to do at every one
of the 56 sites.

### 4.5 · H.11 did not recur

Measured, not argued: `class prefix` appears **zero** times in either score output. Every one of
the 27 FP matched by `exact subject`; the prefix rule was never reached. The key's bare-class
rows each declare that they mean the class *and* its members, and that declaration was never
exercised - it cost nothing and, on this commit, was not needed.

### 4.6 · The hedges

None of the eight resolved against itself. H1 (FETCH, forbidden, MISSED) held as stated. H2
(spellings) held without a miss. H3 (S1 not S2) held: 37 and 0. H4 estimated 37–38 items at ~62
tokens, about 2,300; measured 37 at 62.7, 2,319. H5 and H6 were `why`-only and bore on no score.
H7 (members-too on bare-class rows) was never exercised. **H8: regions are the added lines** - no
form-1 assertion from a context line (`CreateBranchDTO::fromRequest` and the like) appeared, and
there were zero unkeyed assertions to hold one.

K.4 and K.5, the two FLAG rows, were MISSED with zero matched assertions on both engines: the
catalogue has no premise for a removed translation key's callers or a config default's readers.
H.7's shape.

## 5 · Stated plainly

1. Option B's scored effect depends on the key, not the engine: −13 points on a key that could
   not name the shape, +58 on one that could. The M33 number on ee5a2e6 stands as measured and
   is now read correctly.
2. Recognition is complete and delivery is an architectural decision. K.1's miss is ADR-A010 §4.
3. The spelling is predictable from the diff. The author's risk was real and carried nothing.
4. The citation repeats: 2,319 tokens for four facts. New, scales with call sites, not a defect,
   not decided.
5. The scorer's prefix rule did not over-claim here; H.11's failure mode is specific to keys
   written before the form existed.

## 6 · What this does not show

- **Still no reviewer evidence that the citation helps.** ADR-A027 §7's experiment has not run.
  By the scorer alone - key recall unchanged on four commits, every S1 item noise by lever -
  option B remains a cost: +124, +315, +2,319 tokens, and no row satisfied.
- Not whether aggregation would be right. It is named as a possibility with its gates, not
  proposed.
- One commit, one author, one trait, fourteen near-identical call shapes. The 37/37 is a
  measurement of predictability on the easiest possible case for it.
- Not anything about K.4 and K.5 beyond their misses: a reviewer's two real questions on this
  diff - a rewrite that drops translation keys in use, and a default locale flipped under a
  middleware whose coverage was not read - are outside every move the engine has.

## 7 · Regression

v0.2.0 on this commit: 27 assertions, 26 items, 2,403 tokens, 0.0%, both modes byte-identical.
Option B: the same 26 items byte for byte plus 37 S1, nothing removed, `fetched` count unchanged
(17). The key was locked at import with no run at the head, checked.

## 8 · Remaining gaps

| Gap | Where it is recorded |
|---|---|
| The reviewer-facing measurement of S1 - the only evidence that could justify the tokens | ADR-A027 §7; not run |
| One citation per declaring member, or per call site - the repetition question | §4.4 here; gated by ADR-A021, ADR-A024; not proposed |
| A premise for removed translation keys' callers, or a config default's readers | none; K.4, K.5 - two real reviewer questions outside every move |
| Whether keys written before a form existed should be re-read against later forms | ADR-B002 measured note (2026-09-26); a transcription-note question; no key is edited |

## Related

- [M33](M33-option-b-measured.md) - option B built and measured on the three earlier commits;
  the ee5a2e6 result this write-up re-reads.
- [M30](M30-first-scored-bundle.md), [M31](M31-held-out-scored-bundle.md) - where E5.4 and H.3
  were found and confirmed.
- Architecture: ADR-A010 §4 (the fetch ban), ADR-A021 (item identity), ADR-A027 §7 (the reviewer
  experiment), ADR-A028, ADR-A029.
- Backend: ADR-B002 (`calling_subjects`); key `held-out-9b8f9c6`
  (`01m3dbx0hn8e9vwpf437q2rd0s`, locked 2026-09-25 22:45:13 UTC, `runs_existing_at_write = []`).
- Runs (backend): option B `01m3dbx8pd0ab4h1s0ez2mxzvf` (installed), `01m3dbxczw4gw75qjtq7wsvzxm`
  (none); v0.2.0 `01m3dbzhxfnj8mpvckncga4t2d` (installed), `01m3dbzp9mjvggc7hwe559y4xm` (none).
