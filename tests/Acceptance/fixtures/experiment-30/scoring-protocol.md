# Experiment 30 · scoring protocol

**Pre-registered.** Written and committed (2026-09-26) before any packet was cut, before any
defect key for T1/T2/T4/T5 existed, and before any reviewer cell ran. Nothing in it may be
adjusted afterwards; if it turns out to be the wrong protocol, that is a finding about the
protocol, recorded as such. The design it implements is
[ADR-E001](../../../../docs/decisions/ADR-E001-reviewer-measurement-of-the-citation.md).

## 1 · What a review task is

One real commit from `abouelsid-backend`, presented as a **packet**: its unified diff
(`change.diff`), the tool's Markdown context bundle (`context-bundle.md`) and the bundle's stderr
diagnostics (`context-diagnostics.txt`), each exported by the backend from a stored run
(`cd:packet:export`, ADR-B003) with a `provenance.txt` naming that run. Nine tasks, T0–T8, listed
in ADR-E001 §4 and in `corpus.tsv`. No task is synthetic.

## 2 · What the reviewer must decide

The same five questions as experiment 17, unchanged, for every task, both arms and all
replicates, answered from the packet alone:

| | Question | Answer form |
|---|---|---|
| **Q1** | Does this change contain a correctness defect? | `YES` / `NO` / `CANNOT_TELL` |
| **Q2** | If YES, state it in one sentence. | free text, or `-` |
| **Q3** | What is the single most important piece of code **not shown** that you would need in order to be sure? | `file::member`, or `NONE` if the material is sufficient |
| **Q4** | Confidence in Q1. | `HIGH` / `MEDIUM` / `LOW` |
| **Q5** | Quote the exact text you based Q1 on. | a quotation from the packet, or `NONE_VISIBLE` |

Q5 is checked mechanically: the quoted string must occur in that cell's packet.

## 3 · The arms

| Arm | Engine | Packet |
|---|---|---|
| **A** | `v0.2.0` (`e7c919d3328f`) | diff + bundle + diagnostics |
| **B** | option B (`6a77cdbd5ea9`) | the same diff, byte for byte, and the bundle and diagnostics from the same tree under the other engine |

A reviewer is never told which arm it is in, that another arm exists, or what option B is.

## 4 · Cells and replication

- A **cell** is (task, arm). Each cell is run by **three** fresh reviewer agents (r1, r2, r3) on
  the identical packet directory. No agent sees more than one packet, has any history, or has
  access to anything outside its packet.
- A cell's **Q1 answer** is the majority of its three replicates. A three-way split is
  **UNSTABLE**: recorded, reported, and counted for neither arm.
- Every observation file records the reviewer model's **id, version and sampling settings**. The
  model that wrote the context keys `held-out-*` and built option B is **excluded** from every
  cell; the observation file's header says which model was used so this can be checked.

## 5 · What counts as correct

Experiment 17 §3, unchanged:

- **Correct decision** — Q1 equals the key's `defect_present`. `CANNOT_TELL` is an abstention,
  never correct, reported separately.
- **Defect identified** — for `defect_present: YES` tasks only. Q2 names the same mechanism the
  key names. A different defect, or a statement that would fit any change, does not count.
- **Dependency identified** — Q3 names a file or member in the key's `required_context`, or is
  `NONE` where the key says the diff suffices.
- **Evidence-supported** — Q5 occurs verbatim in the cell's packet.

## 6 · The two questions this experiment scores

### 6.1 Q-ask

For each task, if the **arm-A majority Q3 names the ancestor path** (§6.3), every arm-B replicate
of that task is classified as exactly one of **SATISFIED / RE-ASKED / UNCHANGED / DISPLACED** by
the criteria in ADR-E001 §2.1, reproduced here so the classifier reads one document:

| Class | Criterion |
|---|---|
| SATISFIED | Q3 does not name the ancestor path, **and** Q5 quotes any part of an S1 sentence (a line beginning `ASSUMPTION:` containing both `is not declared in` and `it is declared in`) |
| RE-ASKED | Q3 names the body, signature, implementation or definition of a member an S1 sentence in that packet cites |
| UNCHANGED | Q3 names the ancestor path, and Q5 quotes no S1 sentence |
| DISPLACED | Q3 names something the arm-A majority had as `NONE` or already had, **and** Q1 or Q2 is worse than arm A's by the key |

If a replicate fits none, it is recorded **UNCLASSIFIABLE** with the Q3 text, and the design is
noted short. The classifier does not extend the criteria.

### 6.2 Q-harm

For each task whose key marks the defect **unrelated to any S1 citation** (the key says so in its
`citation_relevance` field, written before the run), **defect identified** (§5) is compared
between arms. Harm on a task = arm B's identification below arm A's by more than the noise
floor (§6.4).

### 6.3 "Names the ancestor path" — closed list

Q3 mentions any of: `ApiResponse`; `Controller` as a class or file (`Controller.php`,
`App\Http\Controllers\Controller`), not the word inside another controller's name; the trait or
parent class an S1 sentence in that task's arm-B packet names; or the phrase *where `success` /
`created` / `deleted` / `paginated` is defined* or *declared*. Nothing else.

### 6.4 The noise floor

T0's two packets are byte-identical. Whatever T0's arm A and arm B differ by — in correct
decisions, in defect identification, in Q3 classification — across three replicates each is the
floor. A between-arm difference on any other task is **readable only if it exceeds T0's**. If T0's
arms differ on all three replicates, nothing here is readable, and that is the reported result.

## 7 · Reader blindness

The Q-ask classification (§6.1) and the defect-identified judgement (§5) are made **without
knowing the arm**:

1. After all cells have run, every observation file is copied to `classification/` under an
   opaque name `cell-NN` (NN = 01…54, order shuffled).
2. The mapping `cell-NN → (task, arm, replicate)` is written to `classification/mapping.sealed`
   and **not committed** until step 4.
3. The classifier records, in `classification/classes.tsv`, one class per cell for Q-ask and one
   defect-identified verdict per cell, reading only the `cell-NN` files and the keys. The
   classifier is not a reviewer cell and is not the reviewer model.
4. `classes.tsv` is committed; **then** `mapping.sealed` is committed as `mapping.txt`; then the
   arms are joined. The two commits, in that order, are the evidence the rule was followed.

A class assigned after the mapping is opened is void.

## 8 · What would count — pre-registered

Fixed in advance. **No statistical test will be computed or reported**; with nine tasks there is
no threshold at which a difference becomes significant, and inventing one after seeing the
numbers is the failure this protocol exists to prevent. Direction, mechanism, and the floor.

**Option B is shown not worth keeping if any one holds** (ADR-E001 §8):

1. among tasks whose arm-A majority names the ancestor path, **SATISFIED < half** of arm-B
   replicates, or **DISPLACED ≥ SATISFIED**;
2. on **T1**, defect identified in arm B is below arm A by more than the floor, and no such drop
   appears on T2 or T3;
3. **no** arm-B replicate on any task quotes an S1 sentence in Q5.

Then the response is a **revert** of engine `6a77cdb` under ADR-A003 — not tuning, and **not the
aggregation arm** in response to (1) or (3). Aggregation is a permitted response only to (2)
with (1) passing, as its own gate step.

**Supportive** only if none of the three holds **and** SATISFIED is a majority on at least the
three tasks (T1–T3) whose S1 sentences are the same four facts. Anything else is
**inconclusive**, reported as such.

## 9 · Uncertainty, false confidence, time

Experiment 17 §§6–8, unchanged: `CANNOT_TELL` and `LOW` reported as rates, never discarded;
high-confidence-incorrect reported apart from low-confidence-incorrect, and a rise in it counts
against the citation arm whatever else moves; time not measured.

## 10 · Reproducibility

`corpus.tsv` lists the tasks with the run ids their packets came from. `packets/{T}-{A|B}/` holds
the exact material, each with `provenance.txt`. `observations/{T}-{A|B}-r{n}.md` holds the raw
answers with the model header. `answer-key.json` holds the defect keys, with each key's
`written` date, author and `citation_relevance`. `classification/` holds §7's files. Every packet
can be regenerated from the backend by run id, byte for byte.

---

## Note · 2026-09-26 · the name of §6.2's key field

Appended, not edited. The defect keys are written by a second author who is told nothing about
what is being compared, so their template cannot carry a field called `citation_relevance`. The
field is named **`inherited_member_dependence`** (YES / NO, with a reason): *does the defect, if
any, turn on the behaviour or contract of a method the changed file calls via `$this->` but does
not itself declare?* §6.2's "unrelated to any S1 citation" is read as `inherited_member_dependence:
NO`. The criterion is unchanged; only its name in the key is.

---

## Note · 2026-09-26 · how the blind classifier records, and how §6.1's classes are derived

Appended, not edited. §6.1's DISPLACED class and the gate on §6.1 (*if the arm-A majority Q3
names the ancestor path*) refer to the other arm's majority, which a blind classifier cannot
know. So the classifier does not assign the four classes. It records, per cell and blind, the
**primitive facts** the classes are built from (`handoff/classifier-handoff.md`):
`correct_decision`, `defect_identified`, `q5_verbatim`, `q3_names_path` (with the matched term
from §6.3's closed list), `q5_quotes_assumption` (the S1 sentence shape), and
`q3_names_cited_member`. After `classes.tsv` is committed and the mapping opened, the four
classes are **derived mechanically** from those columns and the arm-A majorities:

| Class | Derivation |
|---|---|
| SATISFIED | `q3_names_path = NO` and `q5_quotes_assumption = YES` |
| RE-ASKED | `q3_names_cited_member = YES` |
| UNCHANGED | `q3_names_path = YES` and `q5_quotes_assumption = NO` |
| DISPLACED | none of the above, `q3_text` names something the task's arm-A majority Q3 had as `NONE` or already named, and `correct_decision` or `defect_identified` is worse than the arm-A majority's |
| UNCLASSIFIABLE | none of the above |

The criteria are unchanged; only where the judgement sits is stated. Every judgement that
requires reading for meaning (`defect_identified`, `q3_names_path`) is made blind; every step
that uses the arm is mechanical and reproducible from the committed TSV.

## Note · 2026-09-26 · the handoff material

`handoff/defect-keys-handoff.md` (the four diffs and the key template, for the second author),
`handoff/reviewer-handoff.md` (the five questions, for a reviewer cell) and
`handoff/classifier-handoff.md` are written for sessions that know nothing of this project. None
names the tool, the arms, or what is being compared. The second author's material was checked
to contain nothing from the context keys `held-out-9b8f9c6` and `held-out-ee5a2e6`.

