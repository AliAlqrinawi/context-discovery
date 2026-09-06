# Experiment 17 · scored comparison

Scored against `answer-key.json` by the protocol in `scoring-protocol.md`, both written first.

## Per task

| Task | Key Q1 | A · Q1 | A ✓ | B · Q1 | B ✓ | bundle items |
|---|---|---|:--:|---|:--:|---:|
| T1 | YES | YES | ✓ | YES | ✓ | 18 |
| T2 | YES | CANNOT_TELL | abstain | CANNOT_TELL | abstain | **0** |
| T3 | NO | YES | ✗ | YES | ✗ | **0** |
| T4 | NO | CANNOT_TELL | abstain | CANNOT_TELL | abstain | **0** |
| T5 | CANNOT_TELL | CANNOT_TELL | ✓ | CANNOT_TELL | ✓ | **0** |

## Totals

| Measure | DIFF_ONLY | DIFF_PLUS_BUNDLE | Δ |
|---|---:|---:|---:|
| tasks | 5 | 5 | — |
| correct decisions | **2/5** | **2/5** | **0** |
| accuracy | 40% | 40% | 0 |
| **high-confidence incorrect** | **0** | **0** | **0** |
| low-confidence incorrect | 1 | 1 | 0 |
| abstentions | 3 | 3 | 0 |
| defect identified (of 2 YES tasks) | 0/2 | 0/2 | 0 |
| dependency/member identified | 1/5 | 1/5 | 0 |
| uncertainty rate | 60% | 60% | 0 |
| evidence-supported | 5/5 | 5/5 | 0 |

**Every measure is identical between the two conditions.**

## Against the pre-registered criteria (protocol §9)

| | Criterion | Met |
|---|---|:--:|
| 1 | B's correct decisions ≥ A's | ✅ 2 = 2 |
| 2 | B's high-confidence-incorrect ≤ A's | ✅ 0 = 0 |
| 3 | On T1 and T2, B identifies the defect where A does not | ❌ **B identified neither** |

Not supportive. Not against — nothing got worse, and no confident error appeared.
**Inconclusive → classification C**, exactly as §9 pre-committed.

## Why it is inconclusive, in one number

**The bundle was empty on 4 of the 5 tasks.** In those cells DIFF_PLUS_BUNDLE was informationally
identical to DIFF_ONLY, so they cannot discriminate anything, and it is unsurprising — not
informative — that the answers match. The effective sample for the actual comparison is **n = 1**.
