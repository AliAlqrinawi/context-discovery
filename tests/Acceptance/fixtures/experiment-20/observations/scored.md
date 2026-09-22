# Experiment 20 · scored comparison

Scored against `answer-key.json` by M17's protocol, unchanged. Both written before any reviewer ran.

## Per task

| Task | Key | Strength | A · Q1 | A ✓ | B · Q1 | B ✓ | Outcome |
|---|---|---|---|:--:|---|:--:|---|
| **D1** | YES | **STRONG** | YES | ✓ | YES | ✓ | no change — **but neither named a keyed defect** |
| **D2** | YES | **STRONG** | CANNOT_TELL | ✗ | CANNOT_TELL | ✗ | no change *(predicted)* |
| **D3** | YES | **STRONG** | CANNOT_TELL | ✗ | CANNOT_TELL | ✗ | no change |
| **D4** | YES | **STRONG** | CANNOT_TELL | ✗ | CANNOT_TELL | ✗ | no change |
| **D5** | YES | MODERATE | CANNOT_TELL | ✗ | **NO** | ✗ | **REGRESSION** — abstention → wrong |
| **C1** | NO | MODERATE | CANNOT_TELL | ✗ | **NO** | ✓ | **improvement**, bundle-only evidence |
| **C2** | NO | MODERATE | CANNOT_TELL | ✗ | CANNOT_TELL | ✗ | no change |

## Totals

| Measure | DIFF_ONLY | DIFF_PLUS_BUNDLE | Δ |
|---|---:|---:|---:|
| correct decisions | 1/7 | **2/7** | +1 |
| **keyed defect identified (5 defect tasks)** | **0/5** | **0/5** | **0** |
| **keyed defect identified (4 STRONG tasks)** | **0/4** | **0/4** | **0** |
| Q1 = YES on a defect task | 1/5 | 1/5 | 0 |
| high-confidence incorrect | 0 | 0 | 0 |
| **false positives** (YES where key says NO) | **0** | **0** | 0 |
| abstentions | 6/7 | 4/7 | −2 |
| dependency identified (file level) | **1/7** | **0/7** | **−1** |
| evidence-supported | 7/7 | 7/7 | 0 |

## The floor

**Neither condition identified a single keyed defect on any of the four STRONG tasks.** 0/4 against
0/4. An experiment in which both arms score zero cannot measure a difference between them: the
result is a **floor effect**, not a finding that the bundle is useless.

## Where the deciding evidence came from

Bundle-only quotes (verified absent from the diff): **D2-B, D4-B, C1-B, C2-B**. Of those four, only
**C1-B** accompanies a correct answer — and C1 is a *control with no defect*, so it measures
"correctly concluding nothing is wrong", not detection.

---

## Erratum · 2026-09-22 · the harness's `vendor/` was never read

**Source:** ADR-A026 (architecture repository) · [M29](../../../../../docs/research/M29-vendor-symlink-correction.md).
The text above is unaltered; no cell was re-scored.

| Claim | Verdict |
|---|---|
| "Bundle-only quotes (verified absent from the diff): **D2-B**, D4-B, C1-B, C2-B" | **D2-B falsified as evidence.** Its Q5 is *"ASSUMPTION: named reference could not be resolved on disk; contract unverified"* — a spurious flag produced because the harness's symlinked `vendor/` was refused. It was absent from the diff; it was also false. D2's entire bundle was two such flags. D4-B, C1-B and C2-B are fetched slices and **stand** |
| Every decision, detection and confidence score | **Stands** as recorded — none is computed from bundle contents |
