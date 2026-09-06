# Experiment 23 · independent replication of M22

**halaw** (Raiyansoft) — 260 commits, Laravel ^10.10, 21 merges, predominantly different authors,
none Claude-authored. Selected by a rule fixed before any inspection: highest `fix:`-to-total ratio
among Laravel repos with vendor installed and 100–600 commits (26/260 = 10.00%).

11 tasks: 5 defect (3 STRONG), 6 control. Key frozen at `4d1ab29a14b09de6` before any reviewer ran.
Nothing reused from M17–M22.

## Result — M22 did not replicate

| | DIFF_ONLY | DIFF_PLUS_BUNDLE |
|---|---:|---:|
| keyed defect identified (5) | **0/5** | **0/5** |
| correct control decisions (6) | **0/6** | **0/6** |
| abstentions | 3/11 | **0/11** |

## Why it is not interpretable

**At least 4 of the 6 controls contain a real defect**, verified in source — including
`'location' => 'nullable|required'` (C5), later fixed by a commit titled **`bugs`**, which the
`fix:`-prefix control filter missed. The control-construction method that worked on abouelsid does
not transfer to a repository with terse commit subjects.

Reviewers also found a **live, never-fixed pricing defect** on D3, at HIGH confidence in both arms:
`$request['sub_total'] += $product->getPrice();` with no quantity multiplier.

See [`docs/research/M23-independent-replication.md`](../../../../docs/research/M23-independent-replication.md).
