# ADR-E001 · The reviewer measurement of option B's citation

- **Status:** Accepted, pre-registered, **not yet run**. Nothing in §§2–7 may be adjusted after
  a cell runs; if the design turns out wrong, that is a finding about the design, recorded as
  such (experiment-17's rule).
- **Date:** 2026-09-26
- **Depends on:** ADR-A003 (the gate), ADR-A010 §4 (the fetch ban), ADR-A021 (item identity),
  ADR-A024 (the contract), ADR-A027 §6 (the pre-check: seven of forty-five diff-only reviewer
  cells asked for the Controller → ApiResponse path), ADR-A028, ADR-A029; backend ADR-B002
  (keys, `calling_subjects`) and ADR-B003 (packets from stored bytes); M17–M22 (the reviewer
  protocol and its limits), M33, M34.
- **Decided by the user, 2026-09-26, and recorded here:** a **second author** writes the defect
  keys; the reviewer model is **pinned and recorded per cell** and the key author's model is
  **excluded**; **r = 3** with the identical-packet control as the noise floor; **key-first
  holds** - defect keys are written and locked before option B runs on any commit that has no
  run yet, and the citation spread is measured after, not before.

## 1 · Why

The scorer has reached its limit. M34 established that option B's recognition is complete -
37 of 37 predicted spellings matched, every one resolved to the right file and line - and that
its delivery is bounded by ADR-A010 §4: a citation is a flag, and a flag satisfies no FETCH row.
Key recall did not move on four commits. By the scorer alone, option B is a cost: +124, +315 and
+2,319 tokens, no row satisfied.

Whether a *citation* - "`success()` is declared in trait `App\Traits\ApiResponse` at
`app/Traits/ApiResponse.php:9`; body not fetched" - changes what a reviewer finds is not a
question a scorer can answer. Seven of forty-five diff-only reviewers asked for that path
(ADR-A027 §6). None has been asked since getting it. This is that measurement.

## 2 · The two questions

Both are answerable per cell and both can come out wrong.

### 2.1 Q-ask · does the citation settle what reviewers asked for?

For a task, take the **silence-arm** cells (§3, arm A) whose Q3 answer - *the single most
important piece of code not shown* - **names the ancestor path**. For each such task, classify
the **citation-arm** cells' (arm B) Q3 and Q5 answers as exactly one of:

| Class | Criterion (literal, applied blind - §6) |
|---|---|
| **SATISFIED** | Q3 does **not** name the ancestor path, **and** Q5 quotes any part of an S1 sentence (a line beginning `ASSUMPTION:` and containing `is not declared in` and `it is declared in`) |
| **RE-ASKED** | Q3 names the **body or signature** of a member the S1 sentence cited - the cited `file::member`, or the words *body*, *signature*, *implementation*, *definition* of that member - i.e. the citation located it and was not enough |
| **UNCHANGED** | Q3 names the ancestor path in the same terms the silence arm did (the trait, the parent, or *where `m()` is defined*), and Q5 does not quote an S1 sentence |
| **DISPLACED** | Q3 names something the **silence arm's majority Q3 had as `NONE`** or had already, and the reviewer's Q1 or Q2 is worse than the silence arm's by the key |

"Names the ancestor path" means: the Q3 text mentions `ApiResponse`, `Controller` (as a file or
class, not the word in a controller's own name), the trait or parent named in the task's S1
sentences, or the phrase *where `success` / `created` / `deleted` / `paginated` is defined* or
*declared*. The list is closed; a classifier who wants to add to it records that the design was
short and does not add.

### 2.2 Q-harm · do the repetitions cost a finding the reviewer had?

For a task whose defect key marks a defect **unrelated** to any S1 citation (the key says so,
before the run), compare **keyed-defect identification** (experiment-17 §3's rule, unchanged)
between arms. Harm is: identification in arm B below arm A **by more than the noise floor** (§5)
on that task.

The design's own prediction, recorded so it can be wrong: harm, if it appears, appears on the
37-citation task and not on the 2- and 5-citation tasks.

## 3 · The arms, and why no third

| Arm | Engine | What the reviewer reads |
|---|---|---|
| **A · silence** | `v0.2.0` (`e7c919d`) | `change.diff` + `context-bundle.md` + `context-diagnostics.txt` |
| **B · citation** | option B (`6a77cdb`) | the same three files from the same diff and tree, differing **only** by the S1 items and their stderr lines - established byte for byte in M33 §4 and M34 §4 |

Same diff, same tree, `installed` vendor only, budget 8000, `caller-scope app/`,
`max-call-sites 20` - the recorded inputs.

**No third arm.** An aggregated-citation engine (one citation per declaring member) does not
exist. Building it before this measurement would be building on a guess, which ADR-A003 forbids.
The repetition question is answered *within arm B* by the corpus spread (§4): if Q-harm appears
at 37 citations and not at 2 or 5, that is the evidence an aggregation arm would need, and it
then becomes a gated engine change (ADR-A021, ADR-A024) with a measurement behind it. If Q-ask
fails, aggregation is moot. The design is re-runnable with an arm C on the same packets and the
same protocol; nothing else in it depends on C.

## 4 · The corpus

| Task | Commit | S1 citations in arm B | Why it is here | Defect key |
|---|---|---|---|---|
| T1 | `9b8f9c6` | **37** (M34) | the repetition end of the spread; carries a citation-unrelated defect visible in the diff (`min.array` dropped while three form requests use `'array', 'min:1'` - M34 K.4) for Q-harm | **second author** |
| T2 | `ee5a2e6` | 5 (M33) | middle of the spread; H.3, H.4 | **second author** |
| T3 | `ec92403` | 2 (M33) | low end; E5.4; M20's D1, whose diff-only reviewer named `Controller.php::deleted` | M20 D1 (YES, STRONG) - reused as written |
| T4 | `f3a7fcd` | 1 call in the diff; S1 count not yet run | the other census-only commit with the shape | **second author** |
| T5 | `2996b89` | not yet run | ADR-A027 §7's list | **second author** |
| T6 | `e5e48ce` | not yet run | ADR-A027 §7's list | M20 D3 (YES, STRONG) - reused |
| T7 | `b2eebe7` | not yet run | ADR-A027 §7's list; `9b8f9c6`'s parent | M22 K10 (NO, WEAK) - reused |
| T8 | `4411454` | not yet run | ADR-A027 §7's list; named by **three** diff-only reviewers, so Q-ask cells with a known prior | M20 C1 / M22 K03 (NO, MODERATE) - reused |
| **T0 · control** | `e770086` | **0** - no `$this->m(` to an undeclared member in any added line, counted from the diff | **both arms' packets are byte-identical**, so any difference between them is the reviewer alone: the noise floor (§5) | M20 C2 / M22 K02 (NO, MODERATE) - reused |

Counted from the diff alone, the M22 controls with zero fourth-form calls are `e770086`,
`721ea3c` and `fdef4a9`; `e770086` is chosen because it has a key in both M20 and M22. If its
arm-A bundle turns out empty, `fdef4a9` (10 files) replaces it, and this ADR is appended, not
edited.

**Key-first, in this order:** (1) the second author writes and locks the defect keys for T1, T2,
T4, T5 (T3, T6, T7, T8, T0 reuse keys written before any bundle existed - M22's rule that answers
are never reused, only keys, holds); (2) **then** option B and v0.2.0 run on T4–T8 and T0, in the
backend, producing the runs the packets are cut from; (3) the S1 counts for T4–T8 are read from
those runs and appended to the table above; (4) packets are cut (§7); (5) cells run.

T1, T2, T3 already have option B and v0.2.0 runs without Markdown (ADR-B003 came later); their
packets are cut from **new** runs whose `stdout_sha256` must equal the recorded runs' before a
packet is accepted - P8, checked, recorded.

## 5 · Cells, replication, the noise floor

- **r = 3** fresh agents per (task, arm), identical packet. A cell's answer is the majority of
  its three replicates (≥ 2/3). A 1/1/1 split on Q1 is **UNSTABLE** and counts for nothing on
  either side; it is reported.
- **The noise floor** is T0. Its two arms are the same bytes. Whatever T0's arm A and arm B
  differ by - on Q1 decisions, on keyed-defect identification, on Q3 classification - across
  three replicates each is what a reviewer does on its own. **A difference between arms on any
  other task is readable only if it exceeds T0's.** If T0's arms differ on all three replicates,
  nothing in this measurement is readable and that is the result.
- **Cells:** 9 tasks × 2 arms × 3 = **54**. No statistical test is computed or reported; n
  admits none (experiment-17 §9's rule). Direction, mechanism, and a measured floor.
- **The reviewer** is a fresh language-model agent per cell, no history, no filesystem, no
  repository, pointed at its own packet directory only (M17 §10). Its model id, version and
  sampling settings are **recorded in every observation file**. The model that wrote the context
  keys and built option B (this session's) is **excluded** from every cell.

## 6 · Reader blindness

The classification in §2.1 and the keyed-defect judgement in §2.2 are made from the observation
text with the **arm hidden**: observation files are copied to a classification directory under
opaque names (`cell-01` … `cell-54`, shuffled, the mapping sealed in a file committed *after*
classification), and the classifier records a class for each before the mapping is opened. The
classifier is not a reviewer cell and not the reviewer model. `scoring-protocol.md` §7 states the
rule; the mapping file is the evidence it was followed.

## 7 · Packets and provenance

Every packet is `cd:packet:export <run> <dir>` (backend ADR-B003): `change.diff`,
`context-bundle.md`, `context-diagnostics.txt`, from stored bytes and nothing else. The export's
provenance line - run id, engine SHA, head SHA, vendor mode, budget, the three files' SHA-256s
and the JSON's `stdout_sha256` - is committed as `provenance.txt` beside each packet. Packets live
in `tests/Acceptance/fixtures/experiment-30/packets/{T}-{A|B}/`; the three replicates of a cell
read the same packet directory.

## 8 · Pre-registered: what would show option B is not worth keeping

Any **one** of the three is sufficient. Named before any cell runs:

1. **Q-ask fails.** Among cells whose silence-arm majority names the ancestor path, **SATISFIED
   is fewer than half**, or **DISPLACED ≥ SATISFIED**. The citation does not settle the ask it
   was built for.
2. **Q-harm.** On T1, keyed-defect identification in arm B is below arm A by more than T0's
   floor, **and** no such drop appears on T2 or T3. The repetitions cost a finding.
3. **Never read.** No arm-B cell's Q5, on any task, quotes any part of an S1 sentence. Tokens
   nobody used.

Then ADR-A003's gate points at **reverting** `6a77cdb` - the form, the premise and the walk
together, as they landed (ADR-A029 §6: they are one change); `POLICY` 4; the M26 golden
regenerated - **not at tuning**.

**Not a permitted response to (1) or to (3): building the aggregation arm.** A citation that does
not settle the ask, or is not read, is not improved by being said once instead of thirty-seven
times. Aggregation is a permitted response **only** to (2) with (1) passing - help exists and
repetition hurts - and then only as its own gate step (ADR-A021, ADR-A024), with this
measurement's T1 numbers as its evidence.

If none of the three holds, the citation stays, and the repetition question is decided on (2)'s
numbers as measured, not on the token count.

## 9 · What this measures and does not

It measures what a **language-model proxy** concludes from a packet (M17 §10's limit, in full
force). It cannot be reported as evidence about human reviewers. It measures one trait's
citation on one author's repository; T1–T3 carry the same four sentences. A SATISFIED majority
says the citation settled the ask on this corpus; it does not say the fetch ban should move
(ADR-A010 §4 has its own trigger and this is not it).

## 10 · What must exist before a cell runs

| Piece | Where | State on this date |
|---|---|---|
| Markdown artifact per run, checked against the JSON; `cd:packet:export` | backend, ADR-B003 | **built** (`900b40d`) |
| `scoring-protocol.md`, with §6's reader-blindness rule | `tests/Acceptance/fixtures/experiment-30/` | **committed with this ADR** |
| Defect keys for T1, T2, T4, T5, written from the diff and locked | second author; `experiment-30/answer-key.json` | **waiting for the second author** |
| Runs for T4–T8, T0 (both engines) and new Markdown-bearing runs for T1–T3 | backend | after the keys |
| Packets with provenance | `experiment-30/packets/` | after the runs |
| The reviewer model, pinned | protocol §8 | to be named when the second author is arranged |

## Related

- ADR-A027 §6 (the pre-check), §7 (the two-author key experiment - a different design, mis-cited
  as this one in M33 and M34; errata appended there 2026-09-26).
- M33, M34 - why the scorer cannot answer this.
- Backend ADR-B002, ADR-B003.
- `tests/Acceptance/fixtures/experiment-17/scoring-protocol.md` - the five questions and the
  correctness rules this design keeps unchanged.

---

## Limitation · 2026-09-26 · the second author, the reviewers and the classifier are one model family

Recorded here as a limitation of the design, not as a footnote, so a later reader weighs the
result correctly.

**Who does what.** The context keys (`held-out-*`) and option B itself were written and built by
a Claude session. The **second author** of the defect keys (§4, §10) is **a fresh Claude session
with none of this context**. The **reviewer cells** (§5) are fresh language-model agents of the
same family. The **blind classifier** (§6) is another session of the same family.

**What that independence buys: independence of context.** The second author will not have seen
option B, the context keys, the scorer, or the fact that an engine is being measured at all; the
handoff (`tests/Acceptance/fixtures/experiment-30/handoff/`) reads as *here is a commit; is there
a defect; what would a reviewer need*, and names no tool. The reviewers see a packet and nothing
else. The classifier sees opaque cells and the criteria. None of them can be steered by what the
key author knows, and that is the failure ADR-A027's first decision had - both keys from one
author's context.

**What it does not buy: independence of training or of reasoning style.** The same family's
priors about what a Laravel defect looks like, what "context a reviewer needs" means, and how to
read a diff are shared across all four roles. A defect the family systematically does not see
will be absent from the defect keys *and* from the reviewers' answers *and* invisible to the
classifier - and the measurement will read that as agreement. A citation the family finds
persuasive for reasons of style rather than content will read as SATISFIED. The noise floor (§5)
measures the family's run-to-run variance; it does not measure its bias.

**Consequences for reading the result.** A supportive outcome under §8 is evidence that *this
model family*, given a packet, does what the citation was built for; it is not evidence about
reviewers in general, and it is weaker than the same outcome from an author, a reviewer and a
classifier of different provenance would be. A negative outcome under §8 is stronger than a
positive one, for the same reason: a citation that does not help the family that built it is
unlikely to help anyone. M17 §10's limit - *the result is evidence about a proxy* - applies with
this addition: the proxy is also the author.

**Field name.** The defect-key template handed to the second author names §2.2's
`citation_relevance` as **`inherited_member_dependence`** - *does the defect turn on a method the
changed file calls via `$this->` but does not declare?* - because the second author must not be
told what a citation is. §6.2 of the protocol reads that field: `citation_relevance = unrelated`
iff `inherited_member_dependence = NO`.

---

## Note · 2026-09-26 · the second author's keys, locked; three observations before any run

The four objects came back from a fresh session given `handoff/defect-keys-handoff.md` and
nothing else, and are locked verbatim in `experiment-30/answer-key.json` (engine `67225f2`,
sha256 `dd650f5e…`) before any engine ran on T4, T5, T6, T7, T8 or T0 and before any packet was
cut. Nothing in them is edited. Three things are recorded now, so they cannot be shaped by what
follows.

**1 · Independent convergence on T1.** The second author's T1 defect is the loss of
`min.array`, `max.array` and ten attribute labels in the `lang/ar/validation.php` rewrite, with
the fallback path traced further than the context key did (per-key fallback to `en`, where the
app file also lacks them, so the framework's English answers - or, if that path is not loaded,
the raw key). The context key `held-out-9b8f9c6`'s K.4 named the same twelve keys and the same
consequence. The second author had not seen K.4, the context key, or any of this programme. Two
readers of the diff alone, in different contexts, named the same defect and the same twelve
keys: the strongest evidence available that the context isolation held. T1's `defect` also
carries the default-locale flip as a secondary, "either counts".

**2 · Independent divergence on T2.** The second author's primary T2 defect is one the context
key `held-out-ee5a2e6` did not see: `routes/admin.php` switches four existing update routes and
the new one from `POST /{id}` to `PUT /{id}`, so existing POST clients get 405, and - since PHP
does not parse multipart bodies on PUT - a genuine multipart update passes validation on
all-nullable rules, empties through `Arr::whereNotNull`, and returns 200 having changed nothing.
The context key's H.7 (`Cache::tags` on a non-tagging store) appears in the second author's key
only as a hedge. So the two keys for this commit differ in their primary mechanism, and T2's
Q-harm is measured against the second author's, as locked. Recorded as a divergence, not
reconciled.

**3 · `inherited_member_dependence` is NO on all four tasks.** Each second-author key says the
defect (T1, T2) or the question most needed (T4, T5) does not turn on the inherited `$this->`
helpers. Two consequences, stated before any cell runs and **not compensated for**:

- **Q-ask's population may be thin or empty.** Q-ask is gated on silence-arm cells whose Q3
  names the ancestor path (§2.1). If the second author's reading is the reviewers' reading, few
  or no arm-A cells will name it, and Q-ask has no population. That is a result about the
  corpus - the citation answers a question these commits' reviewers may not ask - and it is
  reported as one, not redesigned around.
- **Q-harm becomes load-bearing**, and its population is fixed here: **T1 and T2** carry a
  `defect_present: YES` with `inherited_member_dependence: NO`. T3 and T6 carry a YES from M20
  but **no** `inherited_member_dependence` field - the field did not exist when those keys were
  written, and deriving it now would be writing a key. §2.2's "the key says so, before the run"
  is therefore satisfied by T1 and T2 only. §2.2's own prediction - harm on the 37-citation task
  and not on the 2- and 5-citation ones - can be tested on T1 (37) against T2 (5), but **not
  against T3 (2)**. That narrows the design's stated comparison, and is recorded as such.

---

## Note · 2026-09-26 · the spread measured; two arm-pair differences beyond the S1 items - stopped at §4 step (4)

**The spread** (option B, `installed`, S1 items / tokens): T1 `9b8f9c6` 37 / 2,319 · T7 `b2eebe7`
**37** / 2,319 · T5 `2996b89` **37** / 2,317 · T6 `e5e48ce` 14 / 876 · T2 `ee5a2e6` 5 / 315 · T3
`ec92403` 2 / 124 · T8 `4411454` 2 / 124 · T4 `f3a7fcd` **0** · T0 `e770086` 0. It separates
37 from 2. Two facts weaken it: T7 is *"refactor: add ApiResponse trait and clean up all
controllers"* - the commit that introduced the trait, and it produces the same 37 pairs as T1, so
the 37-citation end has three tasks, not one; and T4's single call is a test helper (S2), so both
its arms are **empty bundles** - T4 is a second identical-packet pair, not a spread point. §4's
table row for T4 ("1 call in the diff; S1 count not yet run") resolves to S1 = 0.

The six new runs for T1–T3 reproduce the recorded `stdout_sha256` byte for byte. All eighteen
packets are cut from stored bytes with provenance (`experiment-30/packets/`, `corpus.tsv`).

**The arm-pair check, and why this stopped.** Every pair's `change.diff` is byte-identical.
Beyond the S1 items and their own stderr lines, two kinds of difference exist:

1. **T5's budget.** Both arms hit the 8,000-token budget (7,969 / 7,970). Under option B the 37
   S1 items entered and **20 items present in arm A were dropped** (54 drops vs 34) - among them
   fetched surfaces of `LoginAction`, `CreateTestimonialAction`, `UpdateTestimonialAction` - and
   the `## Dropped` sections differ. Arm B is not "arm A plus citations"; it is arm A minus twenty
   items plus citations. §3's premise does not hold for T5.
2. **S2 stderr lines.** Arm B's `context-diagnostics.txt` carries `inherited member unresolved:
   …` lines that arm A's does not: T2 (3), T3 (8), T4 (1), T6 (2 distinct), T8 (6). These are
   the settled-negative lines for test helpers (ADR-A028 §4), zero tokens, no item - but a
   reviewer in arm B reads them and a reviewer in arm A does not. §3 described the difference as
   "the S1 items and their stderr lines", citing M33 §4; M33 §4's table did list the S2 lines,
   and this note says plainly that they are a difference beyond S1's own.

Per CONTINUE.md step (4) and this ADR's stop conditions, nothing further was done: no mapping
sealed, no cell material written. What (1) and (2) mean for the corpus and for §3's premise is a
decision this ADR does not record, and it is put to the user.

---

## Rulings · 2026-09-26 · T5 kept as Q-evict; S2 stderr lines are part of arm B; the spread's weaknesses

Two rulings by the user on the note above, recorded before any cell runs.

### (1) T5 is kept, outside Q-ask and Q-harm, as **Q-evict** - a third pre-registered question

T5 is not dropped and its budget is not raised. The twenty arm-A items evicted under option B are
**a finding, not noise**: under budget pressure option B does not merely add, it **displaces**
real evidence - the fetched surfaces of `LoginAction`, `CreateTestimonialAction` and
`UpdateTestimonialAction` among the twenty. It never surfaced before because D1 and `ee5a2e6`
never reached the ceiling.

**Q-evict · does the arm-B reviewer miss something the arm-A reviewer used?** Its comparison,
fixed now:

- **EVICTED-USED**: an arm-A replicate whose Q5 quotation lies inside the payload of one of the
  twenty evicted items (the list is `experiment-30/t5-evicted.txt`, written from the two stored
  bundles before any cell). Count over three replicates.
- **MISSED-AFTER-EVICTION**: for each EVICTED-USED replicate, whether arm B's majority is worse
  on `correct_decision` or `defect_identified`, or any arm-B replicate's Q3 names the evicted
  item's subject or file. Reported per replicate, with the Q5 and Q3 text.
- **n = 1 task.** T5 is Q-evict's only task. Whatever it shows is one commit's worth of
  evidence and is reported as such.

**Q-evict does not feed §8.** The three revert conditions were pre-registered and stay exactly
as written. If Q-evict shows displacement harming a review, that is a **new finding for its own
gate** - a budget-interaction question about what an added item is allowed to push out - not a
retroactive fourth condition.

### (2) The S2 stderr lines are **accepted as part of arm B**

They are what option B emits, and the packet is the engine's output; excluding the diagnostics
file from both arms would measure something other than what was built.

**Clarification to §3, appended, dated:** "the S1 items and their stderr lines" **includes the S2
lines** (`inherited member unresolved: …`), as M33 §4's table already listed them. §3's wording
was loose; its source was not.

**Consequence, stated plainly:** arm B carries extra information at zero tokens - on T2 (3
lines), T3 (8), T4 (1), T6 (2 distinct), T8 (6) - so a positive Q-ask result **cannot be
attributed to S1 alone**. S2 is a confound. Any Q-ask improvement reported from this
measurement must say so, and must say on which tasks the S2 lines were present.

### The spread's weaknesses, recorded before any cell

- **T7 duplicates T1's 37.** `b2eebe7` introduced the trait and emits the same 37
  (controller, member) pairs; the 37-citation end of the spread is **three tasks** (T1, T5, T7),
  not four, and two of the three carry the same four sentences 37 times. T5 is outside Q-ask and
  Q-harm by ruling (1), so for those two questions the top end is T1 and T7.
- **T4 is a second control, not a spread point.** Its one fourth-form call is a test helper
  (S2); both arms are empty bundles, differing only by one S2 stderr line. It contributes to the
  noise floor's reading (an almost-identical pair) and to nothing else.

