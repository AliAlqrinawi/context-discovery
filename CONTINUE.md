# Autonomous continuation

You may work without asking on everything below, in order, until you
hit a STOP. At a STOP, write what you have, push, and wait.

## Current position
Option B is built (engine 6a77cdb, architecture e0c276f), green,
installed in the backend, run on all three commits, NOT scored.
Nothing pushed.

## Work to do, in order

### 1. ADR-A024 addendum
The templated flag payload was built on the additive reading with no
addendum. Write it. bundle_version stays 2. The argument: payload
remains one assumption sentence; ADR-A009's ban existed against
non-determinism and unbounded scope, and a bounded template filled
from facts the walk verified has neither. Name the template as the
bound.

### 2. Correct ADR-A028 §6
Its illustrative S2 sentence does not match behaviour: the walk stops
at the class's own RefreshDatabase trait before reaching
Tests\TestCase, so the sentence reads "walked nothing". Append a
dated note. Do not rewrite the body.

### 3. Push all three repos.

### 4. Score all six runs
Three commits × two vendor modes, against their locked keys. Report
per run: assertion precision, key recall, unkeyed, item verdicts
count- and token-weighted, and the before/after against the recorded
scores.

Expected, from ADR-B002's decision: S1 turns unkeyed into TP only
where a key predicted the calling-class spelling. No locked key did,
so E5.4 and H.3 stay MISSED and S1 scores as noise by lever. If
something else happens, that is a finding — report it, do not
explain it away.

### 5. Write the milestone up
docs/research/, sibling conventions. The full loop: measurement
found a gap → gate → first decision "no change" → pre-check reversed
it → three gates → guard landed alone → option B built → measured.
Include the cost honestly: D1 606→730, ee5a2e6 1078→1393.
Append pointers to M30, M31, M32.

### 6. Push, then STOP.

## Stop conditions — wait for me, always

- Any decision not already recorded in ADR-A009/A010/A020/A027/A028/
  A029/B002.
- Any change to a closed interface or decision.
- Any content change to what is discovered, beyond what option B was
  built to do.
- Any measurement that contradicts a recorded estimate or a locked
  key.
- Anything that would rewrite history rather than append.
- Any key edit. Keys are locked; if one is wrong, that is a finding.
- Scores moving on a run the change should not have touched.

## Standing rules
- Append, never rewrite. Errata are dated.
- Report numbers as measured. If one looks wrong, say so.
- Do not tune anything against its own test set.
- Verify by running, not by argument. Establish the reference first.
- When you correct yourself, say what was wrong and where.
