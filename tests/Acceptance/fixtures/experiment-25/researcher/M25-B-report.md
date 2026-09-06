# M25-B — Human Review Results

## 1 · Classification

B — completed.

Human review was completed for all 14 reviewer tasks.

The primary scored stratum contains 7 tasks with independently verified defect ground truth. The remaining 7 tasks are CONTESTED fillers and are excluded from the primary score.

## 2 · Primary objective

Test whether human code review can identify known defect-bearing commits from blinded change packets when repository identity and commit metadata are withheld.

The primary outcome is whether the reviewer correctly answers Q1 = YES.

Exact identification of the known defect is assessed separately through Q2.

## 3 · Reviewer completion

| | Tasks | Answered |
|---|---:|---:|
| A | 7 | 7 |
| B | 7 | 7 |
| Total | 14 | 14 |

## 4 · Primary Q1 result

| Condition | n | YES | CANNOT_TELL | NO | Detection |
|---|---:|---:|---:|---:|---:|
| A | 4 | 2 | 1 | 1 | 50.0% |
| B | 3 | 1 | 2 | 0 | 33.3% |
| Total | 7 | 3 | 3 | 1 | 42.9% |

Human review detected the presence of a defect in 3/7 = 42.9% of the known-defect tasks.

## 5 · Q2 — defect identification

Q2 was evaluated separately against the frozen expected_defect field.

The three Q1=YES cases were H03, H10 and H11.

None of the three positive Q1 responses identified the frozen defect.

Exact frozen-defect identification: 0/7 = 0%.

The positive findings were technically plausible concerns, but they did not match the pre-registered ground-truth defects.

## 6 · Confidence

Among the 7 scored tasks:

- HIGH: 3/7 = 42.9%
- MEDIUM: 2/7 = 28.6%
- LOW: 2/7 = 28.6%

All three HIGH-confidence judgments occurred on Q1=YES cases.

Confidence did not imply correct ground-truth identification.

## 7 · Negative / filler stratum

The 7 CONTESTED filler tasks are excluded from the primary score.

Their Q1 responses were:

- YES: 2
- NO: 2
- CANNOT_TELL: 3

These observations are descriptive only and do not establish specificity.

## 8 · Interpretation

M25 provides evidence that human review can detect some defect-bearing changes, but the evidence is insufficient to establish a reliable repository-independent control-selection criterion.

The reviewer detected a defect in 42.9% of known-defect cases, while exact identification of the known defect was 0%.

The results demonstrate a distinction between detecting that something looks wrong and identifying the defect responsible for the known behavioural failure.

## 9 · Limitations

The single-reviewer design remains imperfectly blinded because the reviewer had prior exposure to the repositories.

The primary sample contains only 7 scored tasks.

The filler stratum is CONTESTED by design and cannot establish specificity.

Q2 exact-identification scoring requires human adjudication rather than the automated scorer.

Human review also introduces reviewer judgment, which prevents it from serving as a repository-independent automated criterion.

## 10 · Decision

M25-B — COMPLETE.

Human review provides a measurable signal, but this experiment does not establish a strong, repository-independent control-selection criterion.

Primary result:

42.9% defect detection; 0% exact ground-truth defect identification.

M25 should not be used to claim that human review solves the control-construction problem identified in M24.

No new control rule is introduced.
