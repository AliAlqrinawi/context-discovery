# M35 · The citation in front of a reviewer: condition (3) holds, and the instrument showed no resolution

> **Classification: the pre-registered reviewer measurement of option B's citation (ADR-E001),
> run to completion, reported as measured.** Six tasks, two arms, three replicates, thirty-six
> cells, one blind classifier. **ADR-E001 §8 condition (3) holds:** across all eighteen arm-B
> cells, zero quoted an S1 sentence and zero named the ancestor path. **Q-ask's population is
> empty** - the possibility pre-registered in §2.1 and foreshadowed, before any cell ran, by the
> second author's `inherited_member_dependence = NO` on all four keys. **Q-harm measured
> nothing** - not "measured no harm": with a zero floor and a zero between-arm difference on
> every task, the comparison had no resolution. Behind all three is one finding about the
> instrument: six independent sessions per task returned byte-identical Q1–Q4 on every task,
> Q2 texts of 297–507 characters identical to the byte, whether or not 24 KB of citations sat
> above the diff. At default sampling this reviewer returns one fixed answer per diff
> regardless of what accompanies it, and r = 3 gave nothing to decide with.
>
> Nothing was tuned, no key was edited, no cell was re-run for its answer. Option B is not
> reverted here; the decision comes after this is read.

## 1 · Objective

M34 left the scorer at its limit: recognition complete (37/37), delivery bounded by ADR-A010
§4, and no way to say whether the citation - *"`success()` is declared in trait
`App\Traits\ApiResponse` at `app/Traits/ApiResponse.php:9`; body not fetched"* - changes what a
reviewer finds. Seven of forty-five diff-only reviewers had asked for that path (ADR-A027 §6).
ADR-E001 designed the measurement before any cell ran: two questions (Q-ask, Q-harm), a third by
ruling (Q-evict), two arms, a control as the noise floor, r = 3, a blind classifier, and three
conditions under which option B is reverted. This is that measurement's result.

## 2 · What ran

| | |
|---|---|
| Corpus | six tasks after a pre-run reduction (ADR-E001, 2026-09-26): T0 `e770086` control (0 citations), T1 `9b8f9c6` (37), T2 `ee5a2e6` (5), T3 `ec92403` (2), T5 `2996b89` (37, Q-evict only, budget-truncated), T6 `e5e48ce` (14) |
| Arms | A = v0.2.0 `e7c919d`; B = option B `6a77cdb`. Same diff byte for byte; bundle differs by exactly the S1 items (and, on T5, twenty evicted items); diagnostics differ by the S1 and S2 lines |
| Packets | cut from stored runs by `cd:packet:export` (backend ADR-B003), each with provenance; every T1–T3 run reproduced the recorded `stdout_sha256` |
| Reviewers | 36 fresh Claude Sonnet 4.5 sessions on claude.ai, default sampling, one per cell, in a sealed shuffled order; the key author's model excluded |
| Defect keys | T1, T2, T5 by a second author (a fresh session given the diffs and nothing else), locked before any run; T3, T6, T0 reused from M20/M22 |
| Classifier | 36 fresh Claude Opus sessions, one per cell, arm hidden, recording primitive facts only; classes derived mechanically after the mapping opened |
| Files | `tests/Acceptance/fixtures/experiment-30/`: `scoring-protocol.md`, `answer-key.json`, `corpus.tsv`, `packets/`, `cells/`, `observations/`, `classification/{classes.tsv, mapping.txt, derived.tsv}`, `t5-evicted.txt` |

## 3 · Condition (3) holds

Pre-registered in ADR-E001 §8 before any cell ran: *"no arm-B cell's Q5, on any task, quotes any
part of an S1 sentence."*

**Measured:** 18 arm-B cells; `q5_quotes_assumption = NO` on 18; `q3_names_path = NO` on 18;
`q3_names_cited_member = NO` on 18. No Q5 quotation in any arm-B cell contains any part of an
`ASSUMPTION: … is not declared in … it is declared in …` line. No Q3 in any arm-B cell names
`ApiResponse`, `Controller`, a cited trait or parent, or where any of the four members is
declared. The rows are in `classification/derived.tsv`; the Q3 and Q5 texts behind them are in
§5's table.

**Condition (1)** - Q-ask fails - has no cells to fail on (§4). **Condition (2)** - Q-harm on T1 -
does not hold: 0 vs 0 (§6). One of three holds, and under §8 one is sufficient.

## 4 · Q-ask's population is empty

Q-ask is gated: it classifies arm-B cells only on tasks whose arm-A majority Q3 names the
ancestor path. **The gate is met on 0 of 6 tasks.** No arm-A cell, on any task, asked for
`ApiResponse`, `Controller`, or where the helpers are declared. The class SATISFIED / RE-ASKED /
UNCHANGED / DISPLACED was therefore never applicable; mechanically, all eighteen arm-B cells are
UNCLASSIFIABLE with the gate unmet, because each arm-B Q3 equals its arm-A Q3 and nothing is
worse.

**The warning preceded the result.** ADR-E001's note of 2026-09-26, written when the second
author's keys were locked and before any run: *"`inherited_member_dependence` is NO on all four
tasks … Q-ask's population may be thin or empty … that is a result about the corpus … and it is
reported as one, not redesigned around."* The second author, reading the diffs with no knowledge
of the citation, found that none of the four commits' defects or open questions turned on the
inherited helpers. The reviewers, reading the same diffs, asked about the same things the second
author did - `SetLocale::handle`, `UpdateDishDTO::fromRequest`, `routes/api.php` - and not about
the helpers. Q-ask has no population because, on this corpus, the question the citation answers
is not one the reviewers asked. ADR-A027 §6's seven-of-forty-five were on other commits and
other keys; on these six, the ask did not recur.

## 5 · The rows

Every replicate in both arms gave the same Q3 on every task, so one line per task carries all
six:

| Task | Q1 (×6) | Q3 (×6) | Q5 (both arms) |
|---|---|---|---|
| T0 | CANNOT_TELL | `app/Repositories/MediaItemRepository.php::getByPage` | `return MediaItem::forPage($page, $section)` / `->get()` (2 per arm) · `$key = 'media_items_' …` (1 per arm) |
| T1 | NO | `app/Http/Middleware/SetLocale.php::handle` | `'locale' => env('APP_LOCALE', 'ar'),` |
| T2 | YES | `app/DTOs/Dish/UpdateDishDTO.php::fromRequest` | `Route::put('/{id}',   'update');` |
| T3 | YES | `composer.json::config.platform.php` | `"php": "^8.4"` |
| T5 | YES | `routes/api.php` | `public function store(StoreQuoteRequestRequest $request, SubmitQuoteRequestAction $action): JsonResponse` |
| T6 | YES | `app/Repositories/DishRepository.php::getAll` | `$filters = $request->only([...]);` |

## 6 · Q-harm measured nothing - not "measured no harm"

These are different claims, and this section exists to keep them apart.

**The numbers.** Keyed-defect identification, arm A vs arm B: T1 NO/NO/NO vs NO/NO/NO; T2 YES ×3
vs YES ×3; T3 NO ×3 vs NO ×3 (M20 key); T6 YES ×3 vs YES ×3 (M20 key). Correct decisions
identical across arms on all six tasks. **The floor, T0: 0 of 3 replicates differ between arms.**
Every between-arm difference is zero; the floor it must exceed is zero.

**Why that is "nothing", not "no harm".** T1's arms differ by 24,553 bytes of bundle and 37
citations - arm B's bundle is 2.3 times arm A's. The reviewer's Q3 in both arms is the same
request, `app/Http/Middleware/SetLocale.php::handle`; its Q1, Q2 and Q5 are the same bytes. A
comparison in which the treatment cannot move the answer *and* the control cannot move the
answer has no resolution: it cannot tell "the citations did no harm" from "this reviewer returns
one fixed answer per diff whatever sits above it." The second reading is what the data supports
directly (§7). Q-harm's pre-registered prediction - harm at 37 and not at 2 or 5 - was neither
confirmed nor refuted; it was not tested, because the instrument could not have registered it.
Q-evict, likewise: on T5, 0 of 3 arm-A Q5s lay inside an evicted item and arm B's answers were
identical to arm A's; n = 1, and the same absence of resolution applies.

## 7 · A finding about the instrument

**Six independent sessions per task returned byte-identical Q1, Q2, Q3 and Q4 on every task.**
Character for character: T1's Q2 is `-` in all six; T2's is 459 characters, T3's 297, T5's 507,
T6's 421 - identical to the byte across both arms and all three replicates. Q5 is one text on
five tasks. Across thirty-six files there are seven distinct answer blocks: one per task, plus
T0's second Q5.

**The sessions were separate.** The material cannot prove thirty-six sessions from the bytes -
every file shares one signature (no trailing newline, no CR, no tab, the same header, identical
sizes within each task) - but T0's Q5 splits 2/1 in *each* arm independently (A: cells 02 and 08
vs 35; B: cells 29 and 05 vs 30), a split a single output pasted six times would not produce.
And the arms were genuinely different: the bundle section of each arm-B cell paste hashes
differently from its arm-A counterpart on T1, T2, T3, T5 and T6 (§2), so the reviewers on those
tasks were given different material and returned the same words.

**What the data supports:** at default sampling on claude.ai, this reviewer - Claude Sonnet 4.5 -
returns one fixed answer per diff regardless of what accompanies it, on every one of six diffs
and at 0, 2, 5, 14 and 37 citations. **r = 3 gave nothing to decide with.** M21 §11 asked for a
variance estimate; this measurement supplies one, and it is zero - which is not the absence of
noise but the absence of an instrument that responds. Whether the reviewer *read* arm B's extra
material and found it irrelevant, or produced its answer from the diff before reaching it,
cannot be told from the output; the output is the same either way.

## 8 · What this does not establish

- **That the citation is useless to a human reviewer.** The reviewer here is a language-model
  proxy (M17 §10), and this one showed no resolution.
- **That it is useless to another model, or to this model at settings that produce variance.**
  Nothing here was varied except the packet, and the packet did not move the answer.
- **That the fetch ban should move**, or that S2's confound mattered - S2 lines were present in
  arm B on T2, T3 and T6 and left no trace either, so the confound ADR-E001 §3 warned of could
  not have contributed to a positive result, because there was none.

ADR-E001's limitation section says a negative result is stronger than a positive one, because
all roles are one model family. **This negative is bounded by an instrument that showed no
resolution.** It says the citation left no trace in this reviewer's answers; it does not say the
citation cannot leave a trace in a reviewer's answers. The distinction is the same as §6's.

## 9 · Audit trail

Recorded as they happened, each in `scoring-protocol.md`'s dated notes or ADR-E001's:

| What | Where | Effect |
|---|---|---|
| The corpus reduced from nine tasks to six before any cell ran (T4, T7, T8 dropped; a cost decision) | ADR-E001 note, 2026-09-26 | the spread still separates 37 from 2; T7's replication of the top end and T8's low-end task lost |
| T5's budget eviction and the S2 stderr lines - two arm-pair differences beyond S1 - ruled on before any cell | ADR-E001 rulings, 2026-09-26 | T5 kept for Q-evict only; S2 accepted as part of arm B and named a confound |
| Model headers on observation cells 02–36 added after the fact on the author's attestation | protocol note, 2026-09-26 | weaker than per-session capture; cell-01's was captured |
| A build error in the classification material: the model header stripped at character length from bytes, leaving a stray `t` line in all 36 `answers.md` and pastes; pastes named their cell only in an HTML comment | protocol note, 2026-09-27 | rebuilt by line, verified byte-identical to the observations (36/36); observations at `c5c1ab5` untouched |
| cell-01's first classifier output, produced on that corrupted input | protocol note, verbatim | superseded by the re-run on corrected input - a correction of the author's error, not a re-run for an answer |
| cell-02's first classifier output lost unsaved on the author's side | protocol note, 2026-09-27 | re-run; the first output does not exist; the one lost-and-re-run cell, weaker than first-pass capture |
| cell-10's row pasted twice, identical | protocol note | one kept |
| Tab separators collapsed to spaces in transit for most rows | protocol note | split unambiguously (fields 1–11 carry no spaces); no value altered; all 36 rows validated against `answers.md` and `reference.json` with the mapping sealed |
| Mapping sealed (sha256 `90c4702b…`) before any cell; `classes.tsv` committed (`24671c1`) before `mapping.txt` (`b31e12b`) | `classification/` | §7 of the protocol followed; the hash matches |

## 10 · Regression

Every packet's JSON came from a run whose `stdout_sha256` matched the recorded runs where a
recorded run existed (T1, T2, T3), and the Markdown of every run passed ADR-B003's conformance
check. No engine, key, protocol or criterion changed between pre-registration and derivation.
Option B is unchanged at `6a77cdb`.

## Related

- [ADR-E001](../decisions/ADR-E001-reviewer-measurement-of-the-citation.md) - the design, the
  rulings, the limitation, and the pre-registered §8.
- [M33](M33-option-b-measured.md), [M34](M34-the-first-fair-key.md) - why the scorer could not
  answer this.
- ADR-A027 §6 (the pre-check), ADR-A010 §4 (the fetch ban), backend ADR-B002 and ADR-B003.
- `tests/Acceptance/fixtures/experiment-30/` - everything this write-up reads from.
