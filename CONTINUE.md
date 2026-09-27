# CONTINUE.md — the dashboard

Work through this in order without asking, until a STOP.
At a STOP: write what you have, push, and wait.

## Where things stand
The engine is closed. The contract is frozen at v2. The backend,
its API and the scorer work. Five measurements exist (M30-M35) and
the reviewer experiment is resolved and recorded (ADR-A030).

Everything this project has built has only ever been looked at
through raw JSON. This builds the thing that makes it visible.

## Part 1 — ADR-B004: scope and stack

Pick the stack yourself and justify it. Constraints:
- internal research tool, one user, local only
- runs with one command
- no build step the project cannot reproduce
- served by the existing API; the backend is the only source of
  truth

Record what you rejected and why.

## Part 2 — what it must show, in this order

**Runs list.** Repository, commit, engine, vendor mode, budget,
status, item count, token count, when. Filter by repository,
engine, status, input_key.

**Run detail.**
- the run block: engine / policy / framework-table versions,
  diff sha, repo sha
- assertions as entities, each with kind, subject, reason, origin,
  and its items nested under it. This is the structure v2 exists
  for — do not flatten it back
- each item: lever, provenance, payload, tokens
- diagnostics, dropped
- raw bundle, stderr and diff, each one click away

**Diff view.** The stored diff with discovered context beside the
region that caused it. An assertion's origin points at a place in
the diff; make that visible.

**Score view.** For a scored run: assertion precision, key recall,
unkeyed count, the four item verdicts, count- and token-weighted.
Every ratio expands to the rows behind it. A bare number with no
rows is what this project has spent its whole history avoiding.

**Key view.** A locked key's rows, with each row's verdict on each
scored run: satisfied, missed, violated, vacuous.

## Part 3 — what it must NOT do
- No editing. Read-only, entirely.
- No number computed in the browser. Everything from the API.
- No comparison view — that is Phase 6 and needs its own design.
- No auth beyond what the API has.
- No charts implying a trend across three data points.

## Part 4 — tests, push, STOP
Report: the stack and why, how to run it, and what you would
build next.

## Stop conditions
- Any change to the engine. It is finished; this is a viewer.
- Any number computed in the frontend rather than read from the API.
- Any API change beyond a read endpoint the dashboard needs — and
  if you add one, record it in an ADR first.
- Any edit to a locked key, a golden, or a recorded measurement.

## Standing rules
- Append, never rewrite. Errata are dated.
- Verify by running, not by argument.
- Report numbers as measured; if one looks wrong, say so.
- When you correct yourself, say what was wrong and where.