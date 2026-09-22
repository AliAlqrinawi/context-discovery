# M29 · The symlinked `vendor/` was never read, and what that costs M19–M23

> **Classification: a defect in the measuring instrument, confirmed and corrected.** From M20 to
> M23 the experiment harness gave the tool a `vendor/` it could not read, so every dependency class
> a reviewed commit named became a spurious *"could not be resolved on disk"* flag. **Every
> conclusion counted from reviewer decisions stands. Every bundle-size and cost figure from M20
> onward is wrong, and the claim that M18's and M19's figures were "understated" is falsified.**
> Three tasks entered their corpora only because of the spurious flags. Two of those flags were
> counted as verified bundle evidence.
>
> No production file changed. No scoring was re-run. No corpus was re-run. Nothing recorded was
> rewritten; each affected record carries an appended, dated erratum pointing here.

## 1 · Objective

Verify, against the harness itself rather than the reader it wraps, a finding made while designing
the Phase 3 backend: that `bundle-at-commit.sh`'s symlinked `vendor/` is invisible to the tool. Then
say exactly which recorded conclusions depend on it.

## 2 · The defect

ADR-A022's harness checks the reviewed commit out into a detached worktree and points `--repo` at
it. `vendor/` is not committed, so the script linked the main checkout's:

```sh
if [ -d "$REPO/vendor" ] && [ ! -e "$WT/vendor" ]; then ln -s "$REPO/vendor" "$WT/vendor"; fi
```

`LocalSourceRepository` resolves every path through `realpath()` and refuses one that lands outside
the root — the guard ADR-A014 cites as protecting the generated-map read *"like every other read"*.
The link resolves to the main checkout, which is outside the worktree, so `vendor/composer/autoload_psr4.php`
reads as absent and `ComposerPsr4ClassLocator` falls back to the project's own map. That fallback is
by design silent: *"Absent, unreadable or unparseable metadata contributes nothing and raises
nothing."* The tool therefore behaved exactly as on a bare checkout, and the only trace was the one a
genuinely missing dependency leaves — a `missing PSR-4 entry` diagnostic and a `named_reference /
flagged` item per dependency class.

The harness's own comment described the opposite: *"the historical tree borrows the one on disk."*
Nothing was borrowed. The comment was carried into ADR-A022 as a caveat about *today's* vendor
being used for a *historical* commit — a caveat about a condition that never obtained.

## 3 · Method

Engine `e7c919d` (`v0.2.0`), tree clean. Corpus `abouelsid-backend` at `450d91f`, remote
`github.com/AliAlqrinawi/abouelsid-backend`, with `composer install` run in it. The corpus
repository's location had been recorded nowhere and was recovered by searching the disk for commit
`ec92403`; it is now in the harness header.

All seven M20 commits and ADR-A022's proof commit, each generated three ways:

| Arm | Harness | `vendor/` in the worktree |
|---|---|---|
| **A** | the committed script, unchanged (`ln -s`) | a symlink |
| **B** | one line changed, `ln -s` → `cp -R` | a real directory |
| **C** | the vendor line removed | none |

Outputs compared with `cmp`, bundle and stderr. Items compared on ADR-A021's identity — every
reviewer-visible field. Everything is committed under `tests/Acceptance/fixtures/experiment-29/`.

## 4 · Result

| Commit | A vs C | A (items · tokens) | B (items · tokens) | only in A | only in B | M18 recorded |
|---|---|---:|---:|---:|---:|---:|
| D1 `ec92403` | **byte-identical** | 37 · 986 | **18 · 606** | 19 | 0 | 18 · 606 |
| D2 `fc573d2` | byte-identical | 2 · 40 | **0 · 0** | 2 | 0 | 0 · 0 |
| D3 `e5e48ce` | byte-identical | 85 · 4694 | 57 · 4134 | 28 | 0 | 52 · 4123 |
| D4 `ec76dc4` | byte-identical | 66 · 3386 | 65 · 3366 | 1 | 0 | 66 · 3758 |
| D5 `44726d0` | byte-identical | 97 · 2700 | 78 · 2320 | 19 | 0 | 79 · 2377 |
| C1 `4411454` | byte-identical | 16 · 479 | **7 · 299** | 9 | 0 | 7 · 299 |
| C2 `e770086` | byte-identical | 30 · 504 | 27 · 444 | 3 | 0 | 29 · 483 |
| proof `0a6e7d1` | — | 4 · 449 | 4 · 449 | 0 | 0 | — |

Three facts, each checked:

1. **The symlink does nothing.** In 7 of 7 commits, arm A is byte-identical to arm C in both the
   bundle and stderr.
2. **The record was made in arm A.** Arm A reproduces every `experiment-20/captured/` count exactly
   — items, tokens and flags — and the packets are byte-identical to the captures.
3. **Every extra item is the same item.** All 81 items present in A and absent in B are
   `named_reference / flagged`, payload *"ASSUMPTION: named reference could not be resolved on
   disk; contract unverified"*, subject a dependency class. B is a strict subset of A in every
   commit. Nothing a real `vendor/` adds; it only removes.

**The "18 / 606 → 37 / 986" attribution is wrong.** ADR-A022 and M20 present D1's growth as the
effect of resolving at the commit's tree. Arm B — the commit's tree *with* a readable vendor — is
18 · 606, identical to M18's HEAD-tree figure; so is D1's diff run against the main checkout today
(`experiment-29/analysis/D1-at-head.json`, 18 · 606). The whole difference is the vendor
disappearing. D2 and C1 likewise return exactly to M18's figures. D3, D4, D5 and C2 differ from M18
in both directions, but the engine changed between M18 (`22f2913`) and this run, so no direction is
claimed for them.

**ADR-A022's decision stands.** The proof commit `0a6e7d1` is 4 · 449 with and without a vendor;
its improvement over M19's 2 · 221 is the tree, not the flags. Resolving at the reviewed commit's
tree is right. Only the *measured size* of that correction was wrong.

## 5 · What reached the reviewers

Each M20 `DIFF_PLUS_BUNDLE` packet holds `context-bundle.md` — byte-identical to the capture — and
`context-diagnostics.txt`, which repeats the same subjects as `missing PSR-4 entry` lines.

| Task | unresolved flags in the packet | of which spurious | `missing PSR-4 entry` lines |
|---|---:|---:|---:|
| D1 | 28 | 19 | 19 |
| D2 | 2 | **2 — the whole bundle** | 2 |
| D3 | 36 | 28 | 28 |
| D4 | 15 | 1 | 2 |
| D5 | 53 | 19 | 19 |
| C1 | 11 | 9 | 9 |
| C2 | 3 | 3 | 3 |

**The rubric and the scoring do not depend on them.** M17's protocol asks five questions about the
commit; `scored.md` counts decisions, defect identification, confidence and evidence support. No
score is computed from an item, token or flag count.

**One M20 reviewer answer rests on a spurious flag.** D2-B's Q5 evidence quotation is, verbatim,
*"ASSUMPTION: named reference could not be resolved on disk; contract unverified"* — one of the two
flags that were D2's entire bundle, on `Illuminate\Database\Schema\Blueprint` and
`Illuminate\Support\Facades\Schema::table`. `scored.md` line 39 lists D2-B among the *"bundle-only
quotes (verified absent from the diff)"*. The quotation was indeed absent from the diff; it was also
false.

## 6 · M22 and M23, estimated

Neither corpus was re-run. The captures were classified by the namespace of each unresolved flag's
subject — a subject outside `App\`, `Database\` and `Tests\` is a dependency class the map would have
placed. On the seven commits above, where the truth is measured, the heuristic is exact on six and
over-counts D4 by one. **Every M22 and M23 figure below is an estimate and a close upper bound.**

| Task | commit | recorded | estimated with a readable vendor | note |
|---|---|---:|---:|---|
| M22 K02 | `e770086` | 30 · 504 | 27 · 444 | = M20 C2, measured |
| M22 K03 | `4411454` | 16 · 479 | 7 · 299 | = M20 C1, measured |
| M22 K04 | `cb66103` | 76 · 1372 | ≈ 70 · 1252 | |
| M22 K05 | `0a6e7d1` | 4 · 449 | 4 · 449 | measured |
| M22 K07 | `fdef4a9` | 2 · 40 | **≈ 0 · 0** | same two subjects as D2 |
| M22 K08 | `11c0ced` | 12 · 888 | ≈ 11 · 868 | |
| M22 K09 | `04328a5` | 41 · 2115 | ≈ 38 · 2055 | |
| M22 K10 | `b2eebe7` | 27 · 2423 | ≈ 26 · 2403 | |
| M22 K11 | `ebbd1d6` | 53 · 1468 | **≈ 24 · 888** | 29 spurious flags |
| M23 C5 | `b5b2859` | 2 · 40 | **≈ 0 · 0** | same two subjects as D2 |
| M23 C6 | `00095a9` | 35 · 1719 | ≈ 32 · 1659 | |
| M23 D1 | `1bc5470` | 5 · 124 | ≈ 3 · 84 | |
| M23 D2–D4 | | | −2 to −4 items each | |
| M23 D5 | `5cc91c3` | 21 · 494 | ≈ 12 · 314 | |

M22 K01 and K06, and M23 C1–C4, are unaffected. M23's selection criterion **R1**, *"`vendor/`
installed"*, had no bearing on any halaw bundle.

## 7 · Three tasks admitted on spurious flags alone

M20's **S4**, M22's **CT3** and M23's **S4** each require a *non-empty bundle under the corrected
harness*. Three tasks met it with a bundle made only of spurious flags — the same two, on
`Blueprint` and `Schema::table`, from a migration that names nothing else:

| Task | recorded | with a readable vendor | evidence |
|---|---:|---:|---|
| M20 **D2** `fc573d2` | 2 · 40 | **0 · 0** | measured; M18 also recorded 0 · 0 |
| M22 **K07** `fdef4a9` | 2 · 40 | ≈ 0 · 0 | estimated |
| M23 **C5** `b5b2859` | 2 · 40 | ≈ 0 · 0 | estimated |

Each would have been excluded by its own criterion. Each `DIFF_PLUS_BUNDLE` arm compared the diff
against the diff plus two false statements.

## 8 · Two spurious flags counted as bundle evidence

- **M20 D2-B** (§5) — counted in `scored.md` line 39 and in the write-up's evidence check, *"Bundle-only
  (absent from the diff): D2-B, D4-B, C1-B, C2-B."*
- **M22 K07-B** — Q5 is the same sentence. Counted in the write-up §5, *"K07-B — a flag statement
  (on a task that did not change outcome)"*, and in `scored.md` line 43.

Both tasks were "no change" in both arms, so no decision moved on them. M23's raw answers are not
committed; its `scored.md` carries no flag quotation.

## 9 · Classification, M19–M23

**M19 — not exposed.** Its bundles were generated against the main checkout, whose `vendor/` is a
real directory; the tree-drift defect it suffered is a different one and is real. Arm B reproduces
K3 (`4411454`) at M19's 7 · 299.

| Conclusion | Depends on the spurious flags? | Verdict |
|---|---|---|
| Correct decisions 1/6 → 4/6, zero regressions, abstentions 4 → 1 | no | **stands** |
| K3: deciding evidence `'password' => 'hashed'` exists only in the bundle | no — a fetched slice | **stands** |
| §10 cost table; "the clearest result was also the cheapest" | no — the sizes are real | **stands** |
| §6 "M18's coverage figure is, if anything, *understated*" | no — rests on tree drift | **stands** (see §11) |

**M20 — exposed.**

| Conclusion | Depends on the spurious flags? | Verdict |
|---|---|---|
| Keyed defects 0/5 and 0/4 in both arms; no discriminating power; classification B | no — the A arm is independent, and every fetched item survives a real vendor | **stands** |
| C1 replicates M19: abstain → NO on the same bundle-sourced quote | no — `User::casts` is a fetched slice, present in arm B | **stands** |
| D1: the transaction premise was present twice and unused | no — an `unverifiable_premise` flag | **stands** |
| D4: bundle-only evidence, wrong target | no — a fetched slice | **stands** |
| Zero false positives; zero confident errors; the composer constraint, a third key miss | no | **stands** |
| §2 "the corrected harness produces materially larger bundles — `ec92403` moves from 18 items / 606 tokens to 37 / 986" | yes — a size claim | **falsified.** Arm B is 18 · 606. D1, D2 and C1 equal M18 exactly; D3–D5 and C2 are confounded by engine change |
| §2 "M18's and M19's figures are therefore understated, not inflated" | yes | **falsified** |
| §10 cost table: mean 1827 / 47.6, median 986 / 37, max 4694 / 97; "C1 cost 479 tokens, the second cheapest" | yes — token counts | **falsified as figures.** C1 is 299, D1 is 606, D5's flags are 47 of 78 not 66 of 97. The observation that expense and effect were not aligned survives |
| §8 D2 "the prediction held"; D2 satisfies S4; D2-B a "bundle-only quote" | yes — bundle presence | **weakened.** The defect is still unreachable (P4/X1) and the prediction still holds; but D2 should have failed S4, and its bundle arm was given noise |
| §6 correct decisions 1/7 → 2/7; abstentions 6 → 4 | only through reviewer behaviour | **unknown**, low risk: the one improvement, C1, rests on a fetched slice |
| §8 D5 "not a case of misleading bundle content so much as a reviewer becoming decisive without becoming right" | yes — 19 spurious flags were in front of that reviewer | **unknown.** The answer quoted the diff; nothing shows the flags caused it, nothing rules it out |
| §13 "`vendor/` is borrowed from the main checkout … Sound for placement (ADR-A014)" | — | **falsified** |
| §11 "Zero vendor items" | — | stands, trivially |

**M21 — not exposed.** No bundle was generated. Every conclusion **stands**, including §8's
reinterpretation of M19's and M20's abstention transitions.

**M22 — exposed.**

| Conclusion | Depends on the spurious flags? | Verdict |
|---|---|---|
| Correct control decisions 1/11 → 6/11; abstentions 9 → 4; zero regressions | only through K07's membership | **weakened, arithmetic only.** Without K07: **1/10 → 6/10**, abstentions **8/10 → 3/10**, false positives 0/10, regressions 0. K07 was "no change" |
| False positives 0/11 in both arms | same | **stands** (0/10) |
| Two of five improvements rest on evidence absent from the diff (K03, K09) | no — fetched slices | **stands** |
| §5 "K07-B — a flag statement" as bundle-only evidence | yes — a spurious flag | **falsified as evidence.** Already recorded as not changing the outcome |
| §7 K03 table: M19 "7 / 299" *at HEAD (defective)* against M20/M22 "16 / 479" *corrected* | yes — a size claim | **falsified as a size claim; the replication stands**, and is cleaner than stated: the substantive bundle was 7 · 299 all three times |
| §2 and §9 sizes and costs (mean 904 / 24, median 504 / 16, range 25 → 2423; "the two with bundle-only evidence cost 479 and 2115") | yes | **falsified as figures** (estimated: K03 299, K09 ≈ 2055, K11 ≈ 888) |
| §6 M21's 83% did not replicate | no — the DIFF_ONLY arm | **stands** |
| §8 K11 contested; sixth key miss | no — the bundle carried nothing on `robots_noindex` | **stands** |
| K11's reviewers' behaviour with ≈ 29 spurious flags present | — | **unknown** |

**M23 — exposed, lightly.**

| Conclusion | Depends on the spurious flags? | Verdict |
|---|---|---|
| Classification C: the controls are not controls; control construction does not transfer | no — verified in the source | **stands** |
| Keyed defects 0/5 both arms; abstentions 3 → 0; no K03 analogue; eleven key misses | no | **stands** |
| C5 satisfies S4; §9 "the smallest (C5, 2 items / 40 tokens)" | yes — bundle presence | **weakened.** C5's bundle is estimated empty; C5 was already CONTESTED, and the milestone's primary measure was already void |
| §9 sizes (2–40 items, 40–1719 tokens, mean 22 / 1231) | yes | **falsified as figures**, lightly — only D5 moves much |
| §2 R1 "`vendor/` installed" as a selection criterion | — | **falsified in effect**: it selected for nothing the tool could see |
| §12 "`vendor/` borrowed from the main checkout (ADR-A022's caveat)" | — | **falsified** |

## 10 · The unknowns, stated as unknown

Whether nineteen to twenty-nine false *"contract unverified"* statements changed what any reviewer
did — **M20 D5**'s regression and **M22 K11**'s two answers most of all — cannot be answered from the
record. Both answers quoted the diff. Answering it means re-scoring those cells with arm-B bundles
against fresh reviewers, which this milestone does not do and which the record's honesty does not
require: the recorded answers are what the reviewers said to what they were shown, and both are
preserved.

**Phase 6 is where this can be answered.** Its purpose is exactly this comparison — the same diff,
two engine or harness conditions, reviewed side by side — and the arm-A and arm-B bundles for all
seven commits are now committed as its first pair.

## 11 · A retraction of my own

The first report of this finding (2026-09-21) listed M19 §6's sentence — *"M18's coverage figure
is, if anything, understated"* — among the claims the vendor defect falsifies. **That was wrong.**
The sentence rests on tree drift and the six `unreadable path` diagnostics, both real. It stands,
and M19 carries no erratum.

## 12 · What "pinned by" pinned

M20, ADR-A022 and the architecture README say the correction is *"pinned by
`tests/Acceptance/HarnessResolvesAtCommitTreeTest.php`."* That test builds its own worktree with
`git worktree add` and never executes `bundle-at-commit.sh`. It pinned the *idea* — resolve at the
commit's tree — and left the script free to be wrong about everything else. Four milestones ran
through the link without a red test.

`tests/Acceptance/HarnessPlacesAReadableVendorTest.php` now runs the script itself against a
synthetic repository whose gitignored `vendor/` holds a PSR-4 package, and asserts the reference is
settled with its location cited and no *"could not be resolved on disk"* flag appears. Reverting the
script to `ln -s` fails it at the first assertion (verified by reverting it once). A second test
symlinks a `vendor/` by hand and asserts the flag *does* appear, so the suite records the failure
mode as well as the guard.

## 13 · What changed, and what did not

- `experiment-20/harness/bundle-at-commit.sh` copies `vendor/` (`ba14403`) and records the corpus
  repository. **ADR-A022's original caveat — today's `vendor/` against the commit's dependency set
  — is now real for the first time**; ADR-A026 states it.
- Errata appended, dated, none of the original text altered: M20, M22 and M23 write-ups;
  `experiment-20/README.md`; M20 and M22 `scored.md`; the architecture README; ADR-A022's status.
- **Not changed:** captures, packets, observations, answer keys, `src/`, `bin/`, the schema, any
  test that existed. The v1 captures stay v1 (ADR-A024). No score was recomputed.

## 14 · Regression

`git status src/ bin/` empty — **no production file changed.** Full suite **1196 tests** green,
none skipped, the two new tests included.

## 15 · Limitations

- **M22 and M23 figures are estimates** from a heuristic validated on seven commits. Re-running
  both corpora through the corrected harness is cheap and was not done here by instruction.
- **One engine version.** The measurement used `v0.2.0`; the captures were made at `8cdbebb`
  through `ffa6520` (bundle v1). Arm A reproducing every v1 count is evidence the discovery
  behaviour is unchanged for these commits, not proof of it.
- **The reviewer-behaviour question is open** (§10).
- The corpus repository's `vendor/` is *today's* install. Arm B measures what the commit's tree
  resolves against a current dependency set — the caveat ADR-A022 described and now actually has.

## 16 · Remaining gaps

1. **Re-run M22 and M23 through the corrected harness** to replace §6's estimates with
   measurements. No scoring is needed for that; only the bundles.
2. **Phase 6's first comparison** is arm A against arm B on M20's seven commits, with the D5 and
   K11 cells as the ones worth re-scoring.
3. **A harness test must run the harness.** `HarnessResolvesAtCommitTreeTest` should be read as a
   test of the tool's behaviour on a moved file, not of the script; the script is now covered.
4. **The tool cannot tell "no vendor" from "vendor refused".** By design (ADR-A014's silent
   fallback) and P10's flag-not-silence rule together, a refused symlink is indistinguishable from
   a bare checkout. Whether a `vendor/` present as a symlink deserves a diagnostic of its own is a
   question for the key-first gate, not for this correction.
