# Experiment 21 · scored — DIFF_ONLY baseline

## Per task

| Task | Key | Strength | Q1 | Correct | **Keyed defect identified** | Conf |
|---|---|---|---|:--:|:--:|---|
| T01 | YES | **STRONG** | CANNOT_TELL | abstain | ✗ | LOW |
| T03 | YES | MODERATE | CANNOT_TELL | abstain | ✗ | LOW |
| T04 | YES | **STRONG** | YES | ✓ | ✗ *(found the composer constraint instead)* | MEDIUM |
| T05 | YES | **STRONG** | CANNOT_TELL | abstain | ✗ | LOW |
| T06 | YES | **STRONG** | CANNOT_TELL | abstain | ✗ | LOW |
| T07 | YES | MODERATE | CANNOT_TELL | abstain | ✗ | LOW |
| T08 | YES | MODERATE | CANNOT_TELL | abstain | ✗ | MEDIUM |
| T09 | YES | **STRONG** | YES | ✓ | ✗ *(found delete-before-store instead)* | MEDIUM |
| T10 | YES | MODERATE | YES | ✓ | ✗ *(found an ignored `$id` instead)* | MEDIUM |
| C01 | NO | MODERATE | **YES** | ✗ FP | — | MEDIUM |
| C02 | NO | MODERATE | **YES** | ✗ FP | — | MEDIUM |
| C03 | NO | MODERATE | **YES** | ✗ FP | — | MEDIUM |
| C04 | NO | MODERATE | CANNOT_TELL | abstain | — | LOW |
| C05 | NO | WEAK | **YES** | ✗ FP *(verified false)* | — | MEDIUM |
| C06 | NO | WEAK | **YES** | ✗ FP by key, **but verified real** | — | MEDIUM |

## Totals

| | |
|---|---:|
| correct decisions | **3/15 · 20%** |
| **keyed defect identified — 9 defect tasks** | **0/9 · 0%** |
| **keyed defect identified — 5 STRONG tasks** | **0/5 · 0%** |
| Q1 = YES on a defect task | 3/9 |
| abstained on a defect task | 6/9 |
| **confidently wrong (HIGH + incorrect)** | **0/15** |
| controls correctly classified | **0/6** |
| **false positives on controls** | **5/6 · 83%** |
| evidence-supported | 15/15 |
| confidence: HIGH / MEDIUM / LOW | 0 / 9 / 6 |

## A / B / C analysis — the point of the milestone

| | | Tasks |
|---|---|---|
| **A** | diff has the evidence **and** the reviewer detected the keyed defect | **0** |
| **B** | diff has the evidence, reviewer **missed** it | **6** — T05, T06, T07, T08, T09, T10 |
| **C** | diff genuinely does not expose what is needed | **3** — T01, T03, and T04(a)'s transaction |

**B dominates.** In six of nine defect tasks the keyed evidence was literally in the diff the reviewer
read, and the reviewer did not use it. That is not an information-availability problem, and more
context cannot fix it.
