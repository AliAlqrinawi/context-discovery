# Experiment 22 · scored — the false-positive axis

> **The run is incomplete.** All 11 DIFF_ONLY reviewers finished; **7 of 11 DIFF_PLUS_BUNDLE agents
> terminated with HTTP 429** (session rate limit). Only **K01–K04** have both arms. Nothing has been
> substituted or inferred; missing cells are marked MISSING.

## The 4 complete pairs

| Task | bundle | A · DIFF_ONLY | B · DIFF_PLUS_BUNDLE | Outcome |
|---|---:|---|---|---|
| K01 | 1 item / 25 tok | CANNOT_TELL · MED | CANNOT_TELL · MED | no change |
| K02 | 30 / 504 | CANNOT_TELL · MED | **NO** · MED | **abstention → correct** |
| K03 | 16 / 479 | CANNOT_TELL · MED | **NO** · MED | **abstention → correct, bundle-only evidence** |
| K04 | 76 / 1372 | CANNOT_TELL · MED | CANNOT_TELL · MED | no change |

| Measure (n = 4) | DIFF_ONLY | DIFF_PLUS_BUNDLE |
|---|---:|---:|
| correct control decisions (NO) | **0/4** | **2/4** |
| **false positives** | **0/4** | **0/4** |
| confident false positives (YES + HIGH) | **0** | **0** |
| abstentions | 4/4 | 2/4 |

**On these four the bundle raised correct control decisions from 0 to 2 and added no false
positive.** n = 4. No further claim is available.

## K03 — a third replication

DIFF_ONLY abstained. DIFF_PLUS_BUNDLE answered **NO** quoting `'password' => 'hashed',` — verified
absent from the diff and present in the bundle's `User::casts` slice.

| | milestone | harness | bundle | result |
|---|---|---|---|---|
| 1 | M19 K3 | at HEAD (defective) | 7 items / 299 tok | abstain → NO, same quote |
| 2 | M20 C1 | corrected | 16 / 479 | abstain → NO, same quote |
| 3 | **M22 K03** | corrected | 16 / 479 | abstain → NO, same quote |

Three independent reviewers, three milestones, the same commit, the same transition, the same
bundle-sourced quotation. This is the most reproducible result the scored series has produced.

## The DIFF_ONLY arm alone — complete, 11 controls

| | |
|---|---:|
| correct control decisions (NO) | **1/11** |
| **defect reported (YES)** | **1/11 · 9%** |
| abstentions | **9/11 · 82%** |
| confident false positives | **0** |

Of the single YES — **K11 — the claim is VERIFIED REAL**, so the false-positive rate on this control
set is **0/11**, not 1/11.

### This flatly contradicts M21

M21 measured DIFF_ONLY on 6 controls and got **5 YES (83%)**. M22 measures 11 controls and gets
**1 YES (9%)**, and that one is a real defect.

Same protocol, same reviewer model, same repository, different control commits. **The
false-positive rate is not stable across control sets**, which means M21's 5/6 was not a baseline
this milestone could be compared against — and that neither figure supports a claim about "the"
false-positive rate.
