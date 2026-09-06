# Experiment 22 · the false-positive axis

**Complete — 22 of 22 cells.** The first attempt was truncated by an HTTP 429 session limit; the
seven affected DIFF_PLUS_BUNDLE cells were re-run unchanged after the reset, with fresh agents and
the identical committed packets.

11 control commits keyed NO. Bundles from **1 item / 25 tokens** to **76 / 2423**, generated with
M20's corrected same-commit-tree harness.

## Result

| Measure (n = 11) | DIFF_ONLY | DIFF_PLUS_BUNDLE |
|---|---:|---:|
| correct control decisions | 1/11 | **6/11** |
| **verified false positives** | **0/11** | **0/11** |
| confident false positives | 0 | 0 |
| abstentions | 9/11 | 4/11 |
| regressions | — | **0** |

**The bundle does not increase false positives**, and it converted five abstentions into correct
answers with nothing broken. Two of those five (**K03**, **K09**) rest on evidence verified absent
from the diff.

**But M21 did not replicate.** Its 5-of-6 (83%) false-positive rate came back as **0 of 11** here.
The axis is dominated by which controls are chosen, not by the condition — so the headline was
measured against a floor of zero, and is weaker than it sounds.

**K11 is contested** — keyed NO, but *both* reviewers independently found that
`GetEffectiveSeoAction`'s `robots_noindex` inheritance is dead code (the column is NOT NULL with a
default). Verified; the key was not changed. Sixth key miss in five milestones. The bundle carried
nothing about it.

See [`docs/research/M22-false-positive-axis.md`](../../../../docs/research/M22-false-positive-axis.md).
