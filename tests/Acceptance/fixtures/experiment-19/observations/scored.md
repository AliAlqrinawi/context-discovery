# Experiment 19 · scored comparison

Scored against `answer-key.json` by M17's protocol, unchanged. Both written before any reviewer ran.

## Per task

| Task | Commit | Key | Strength | A · Q1 | A ✓ | B · Q1 | B ✓ | Outcome |
|---|---|---|---|---|:--:|---|:--:|---|
| K1 | `0a6e7d1` | NO | WEAK | CANNOT_TELL | abstain | CANNOT_TELL | abstain | **COMPROMISED** — repository drift |
| K2 | `721ea3c` | NO | MODERATE | NO | ✓ | NO | ✓ | no change · confidence MEDIUM→**LOW** |
| K3 | `4411454` | NO | MODERATE | CANNOT_TELL | abstain | **NO** | ✓ | **improvement + evidence shift** |
| K4 | `e770086` | NO | MODERATE | CANNOT_TELL | abstain | **NO** | ✓ | **improvement** |
| K5 | `04328a5` | NO | MODERATE | CANNOT_TELL | abstain | **NO** | ✓ | **improvement** |
| K6 | `560b21b` | NO | **CONTESTED** | YES | ✗ | YES | ✗ | no change · both found a real defect the key missed |
| K7 | `11c0ced` | NO | MODERATE | CANNOT_TELL | abstain | CANNOT_TELL | abstain | no change |

## Totals over the 6 valid tasks (K1 excluded as compromised)

| Measure | DIFF_ONLY | DIFF_PLUS_BUNDLE | Δ |
|---|---:|---:|---:|
| correct decisions | **1/6** | **4/6** | **+3** |
| **high-confidence incorrect** | **0** | **0** | 0 |
| low-confidence incorrect | 1 | 1 | 0 |
| abstentions | 4 | **1** | **−3** |
| defect identification | *not measurable* | *not measurable* | — |
| dependency/member identified | **2/6** | **1/6** | **−1** |
| evidence-supported | 6/6 | 6/6 | 0 |

**Three improvements, zero regressions.** No task the diff alone got right was got wrong with the
bundle.

**Defect identification is not measurable here**: every task keys to NO DEFECT, so the corpus
contains no positive case. This was recorded in the key before scoring, not discovered afterwards.

**Dependency identification went down, not up** — 2/6 to 1/6. Reported because it is the one measure
that moved against the bundle.

## Where the deciding evidence came from

All 14 Q5 quotes were verified present in that reviewer's own material. Tracing them further:

| | |
|---|---|
| **K3-B** | quote `'password' => 'hashed',` exists **only in the bundle** (the `User::casts` slice) and nowhere in the diff |
| K4-B | quote appears in both the diff and the bundle |
| all others | quote is from the diff |

So of the three improvements, **one is traceable to context the diff could not have supplied**. The
other two flipped from abstention to a correct NO without citing bundle content as their basis.
