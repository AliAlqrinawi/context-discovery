# CONTINUE.md — Phase 8: the review layer

Work through this in order without asking, until a STOP.
At a STOP: write what you have, push, and wait.

## What M36 established
The bundle's value is scoped: it decides rules about contracts and
callers, where the evidence is outside the diff by definition, and
adds nothing to structural rules, where the violation is in the
added line. Trial 2 was done by hand. This automates it.

## Part 1 — close the M36 gap

No backend run carries the M26 diff, so the trial that proved the
bundle's value cannot be reproduced from the system.

Create one: register the M26 commit pair as a run in the backend
on engine 6a77cdb, both vendor modes, and verify its stdout is
byte-identical to the golden fixture. If it is not, STOP and
report the difference before anything else.

## Part 2 — ADR-B005: the review layer

Design before building. Record:

- The rules file: where it lives in the reviewed repository, its
  format, and how the backend reads it. Keep it plain — a markdown
  or text file of numbered rules, not a schema. M36 showed the
  rules that matter are prose a reviewer reads, not patterns a
  parser matches.
- What is sent to Claude: the diff, the bundle, the rules. Say
  exactly how each is rendered, and what is left out.
- Which model, pinned and recorded per review, as ADR-E001 pins
  reviewers.
- What is stored: the prompt as sent, the response as returned,
  the model and settings — bytes, not summaries, the same
  discipline as run_artifacts.
- The boundary: Claude judges the code. It does not judge the
  bundle, score a run, or write to any repository. ADR-E001's
  "no LLM judges a bundle" is not violated by this — say why, so a
  later reader does not read a contradiction.
- What this cannot do, from M36: structural rules gain nothing;
  a rule's verdict is only as good as the bundle beneath it; at 5%
  caller precision the reviewer must be able to name noise as
  noise, so the noise is NOT filtered before sending.

## Part 3 — build it

- A reviews table and a review job, following the run pipeline's
  shape: queued, immutable once terminal, artifacts as bytes.
- POST /runs/{run}/reviews — creates a review for that run against
  the repository's rules file at that commit.
- GET /reviews/{review} — the report, the model, the rules as read.
- In the dashboard: a Review tab on a run. It shows the report and,
  behind a click, the exact prompt that produced it. A report whose
  prompt cannot be inspected is not evidence.

## Part 4 — prove it end to end
Run a review on the M26 run and on ec92403. Report both reports in
full. Compare them against M36's two trials: same findings, better,
or worse? Report the difference as measured, not as expected.

Push, then STOP.

## Stop conditions
- Any change to the engine.
- Any filtering, reranking, or summarising of the bundle before it
  is sent. M36 showed the reviewer handles noise; do not pre-empt it.
- Any review that writes anywhere except its own row.
- Any number computed outside the backend.
- Claude being asked to judge the bundle rather than the code.

## Standing rules
- Append, never rewrite. Errata are dated.
- Verify by running, not by argument.
- Report numbers as measured; if one looks wrong, say so.
- When you correct yourself, say what was wrong and where.
