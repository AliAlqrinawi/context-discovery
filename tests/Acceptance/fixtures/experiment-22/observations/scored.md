# Experiment 22 · scored — the false-positive axis

All 22 cells complete. The first attempt was truncated by an HTTP 429 session limit; the seven
affected DIFF_PLUS_BUNDLE cells were re-run unchanged after the reset, with fresh agents and the
identical packets.

## Per task

| Task | bundle | A · DIFF_ONLY | B · DIFF_PLUS_BUNDLE | Outcome |
|---|---:|---|---|---|
| K01 | 1 / 25 | CANNOT_TELL | CANNOT_TELL | no change |
| K02 | 30 / 504 | CANNOT_TELL | **NO** | abstain → correct |
| K03 | 16 / 479 | CANNOT_TELL | **NO** | abstain → correct · **bundle-only evidence** |
| K04 | 76 / 1372 | CANNOT_TELL | CANNOT_TELL | no change |
| K05 | 4 / 449 | CANNOT_TELL | CANNOT_TELL | no change |
| K06 | 4 / 185 | CANNOT_TELL | **NO** | abstain → correct |
| K07 | 2 / 40 | CANNOT_TELL | CANNOT_TELL | no change |
| K08 | 12 / 888 | CANNOT_TELL | **NO** | abstain → correct |
| K09 | 41 / 2115 | CANNOT_TELL | **NO** | abstain → correct · **bundle-only evidence** |
| K10 | 27 / 2423 | NO | NO | no change (both correct) |
| K11 | 53 / 1468 | **YES** | **YES** | no change — both found the same **verified-real** defect |

## Totals (n = 11)

| Measure | DIFF_ONLY | DIFF_PLUS_BUNDLE | Δ |
|---|---:|---:|---:|
| correct control decisions (NO) | **1/11 · 9%** | **6/11 · 55%** | **+5** |
| defect reported (YES) | 1/11 | 1/11 | **0** |
| **verified false positives** | **0/11** | **0/11** | **0** |
| confident false positives (YES + HIGH) | **0** | **0** | 0 |
| abstentions | **9/11 · 82%** | **4/11 · 36%** | **−5** |
| evidence-supported | 11/11 | 11/11 | 0 |
| regressions (correct → incorrect) | — | **0** | — |

**The bundle did not increase false positives.** Both arms report exactly one defect, on K11, and it
is verified real — so the spurious rate is **0/11 in both**.

**It converted five abstentions into correct control decisions**, with **zero** regressions.

## Evidence provenance

All 22 Q5 quotes verified in their own material. **Bundle-only** (absent from the diff): **K03-B**
`'password' => 'hashed',` · **K07-B** a flag statement · **K09-B** `public readonly UploadedFile
$image,`. K08-B appears in both. Every other quote is from the diff.

So of the five improvements, **two (K03, K09) rest on evidence the diff could not have supplied**.

## K03 — the third replication

M19 K3 → M20 C1 → M22 K03: three reviewers, three milestones, one commit, the same abstain → NO
transition on the same bundle-sourced quotation.
