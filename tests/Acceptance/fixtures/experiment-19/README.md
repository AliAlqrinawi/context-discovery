# Experiment 19 · the scored re-run, on commits where the tool actually speaks

M17's scored run was inconclusive: 4 of its 5 commits produced an empty bundle, so the effective
sample was 1. M18 measured the whole repository and identified **seven** commits that produce
meaningful context. This re-runs M17's protocol, unchanged, on exactly those seven.

## Files

| | |
|---|---|
| `answer-key.json` | written before any reviewer ran; states the corpus's weakness before scoring |
| `packets/K*-A/` | DIFF_ONLY material — one file |
| `packets/K*-B/` | DIFF_PLUS_BUNDLE — the identical diff, plus bundle and diagnostics |
| `captured/` | each candidate's diff, JSON bundle, Markdown bundle and stderr |
| `observations/raw-observations.json` | all 14 answers verbatim |
| `observations/scored.md` | the comparison |

Protocol: `../experiment-17/scoring-protocol.md`, unchanged and untouched since M17.

## Result, over the 6 valid tasks

| | DIFF_ONLY | DIFF_PLUS_BUNDLE |
|---|---:|---:|
| correct | **1/6** | **4/6** |
| abstentions | 4 | 1 |
| high-confidence incorrect | 0 | 0 |
| dependency identified | 2/6 | **1/6** |

Three improvements, zero regressions — and **one** of the three (K3) rests on evidence that exists
only in the bundle: `'password' => 'hashed'`, from the `User::casts` slice, quoted by a reviewer
deciding that a mass-assignment was safe.

## Two things that go against the bundle, kept here rather than in a footnote

1. **Dependency identification fell**, 2/6 → 1/6.
2. **K6's key is contested.** Both reviewers, in both conditions, reported a real defect in the new
   `CacheGroup` service that the hand-written key missed — the key index's TTL is never refreshed
   while entry TTLs restart, so `flush()` eventually forgets nothing. Verified against the source.
   The key was **not** changed. This is the second time in two scored milestones that blind reviewers
   have found a real defect a hand-written key missed.

## K1 is compromised, not merely negative

Bundles are generated against the repository at **HEAD**, not against each commit's own tree. Two of
`0a6e7d1`'s three changed files no longer exist there, and both of its diagnostics read
`unreadable path`. K1 is excluded from the totals rather than silently included. Across M18's 46
commits the same drift affects **6**.

See [`docs/research/M19-scored-review-rerun.md`](../../../../docs/research/M19-scored-review-rerun.md).
