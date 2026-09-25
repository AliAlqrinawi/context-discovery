# CONTINUE.md — the reviewer measurement (ADR-E001)

Work through this in order without asking, until a STOP.
At a STOP: write what you have, push, and wait.

## Where things stand
Option B is built, measured by the scorer, and recorded (M33, M34).
The scorer has reached its limit: it can say recognition is complete
(37/37) and cannot say whether a citation helps a reviewer.
The second author's four defect keys are in hand.

## This is a measurement, not a delivery
The outcome is not known. ADR-E001 §8 names three conditions under
which option B is REVERTED. A negative result is a successful
milestone, not a failure to fix. Do not adjust the design, the keys,
or the protocol to make option B look better.

Specifically forbidden:
- Editing any locked key.
- Changing classification criteria after seeing answers.
- Building the aggregation arm as a response to a negative Q-ask or
  to condition (3).
- Re-running a cell because its answer was unexpected.
- Reporting a ratio without the rows behind it.

## Work, in order

### 1. Lock the defect keys
Place the second author's four objects in
experiment-30/answer-key.json with the reused M20/M22 keys for
T3, T6, T7, T8, T0. Commit as locked.

### 2. Record three observations in ADR-E001 (dated note)
- Independent convergence on T1: the second author named the same
  defect as my context key K.4 (dropped min.array/max.array and
  attribute labels) with a fuller mechanism, without having seen it.
  Strongest evidence the context isolation held.
- Independent divergence on T2: their primary defect is the POST→PUT
  switch (405 for existing clients; PHP does not parse multipart on
  PUT, so a real update validates, empties through
  Arr::whereNotNull, and returns 200 having changed nothing). My key
  did not see it; they list Cache::tags only as a hedge.
- inherited_member_dependence is NO on all four tasks. Q-ask's
  population may be thin or empty on this corpus, and Q-harm becomes
  load-bearing. Do NOT compensate. If Q-ask has no population, that
  is a result about the corpus and is reported as one.

### 3. Fix the spread
Run option B on T4-T8 and T0. Report the S1 count per task.
If the spread is too narrow to separate 37 from 2, say so plainly —
that weakens Q-harm and must be stated before any cell runs.

### 4. Cut the packets
cd:packet:export for every (task, arm). Packets derive from stored
bytes only. Verify each arm pair differs ONLY by S1 items and their
stderr lines; if anything else differs, STOP and report.

### 5. STOP
The cells need fresh sessions I cannot open. Write the cell list and
the exact material to paste, one file per cell, and tell me what to
run. Seal the arm mapping before I start.

### 6. After the answers come back
Record them verbatim. Run the blind classifier material. Derive the
four classes mechanically once the mapping opens. Report:
- Q-ask per cell, with the Q3/Q5 text behind each class
- Q-harm: keyed-defect identification per arm per task, against the
  control's noise floor
- Which of ADR-E001 §8's three conditions hold, if any

### 7. Write it up and STOP
docs/research/, sibling conventions. State the result as measured.
If option B should be reverted, say so and stop — do not revert in
the same step.

## Stop conditions
- Any decision not already recorded in ADR-E001, A027, A028, A029,
  B002, B003.
- Any arm pair differing by more than the S1 items.
- Any measurement contradicting a recorded estimate.
- Any key edit, or any temptation to write one.
- Anything that would rewrite history rather than append.
- Q-ask turning out to have no population — report, do not redesign.

## Standing rules
- Append, never rewrite. Errata are dated.
- Verify by running. Establish the reference first.
- Report numbers as measured; if one looks wrong, say so.
- When you correct yourself, say what was wrong and where.
- Never tune anything against its own test set.
