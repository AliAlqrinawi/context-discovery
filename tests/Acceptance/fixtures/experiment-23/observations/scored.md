# Experiment 23 · scored — M22 replicated on an independent repository

All 22 cells complete. Two agents failed to launch on a classifier timeout and were relaunched
unchanged with the identical committed packets.

## Per task

| Task | Key | bundle | A · Q1 (conf) | B · Q1 (conf) | Outcome |
|---|---|---:|---|---|---|
| D1 | YES | 5 / 124 | **YES** (MED) | **YES** (HIGH) | both YES — but a *different* defect from the key's |
| D2 | YES | 26 / 1387 | CANNOT_TELL (LOW) | **YES** (MED) | abstain → YES, different defect |
| D3 | YES | 40 / 1508 | **YES** (HIGH) | **YES** (HIGH) | both YES — different defect, **verified real** |
| D4 | YES | 30 / 1603 | CANNOT_TELL (MED) | **YES** (MED) | abstain → YES, different defect (missing authz) |
| D5 | YES | 21 / 494 | **YES** (MED) | **YES** (MED) | both YES — **verified SPURIOUS**, identically |
| C1 | NO | 30 / 1672 | **YES** (MED) | **YES** (MED) | both YES on a control |
| C2 | NO | 20 / 1630 | CANNOT_TELL (LOW) | **YES** (MED) | abstain → YES on a control |
| C3 | NO | 31 / 1624 | **YES** (MED) | **YES** (MED) | both YES — **verified real** |
| C4 | NO | 32 / 1645 | **YES** (MED) | **YES** (MED) | both YES — ar/en swap |
| C5 | NO | 2 / 40 | **YES** (HIGH) | **YES** (HIGH) | both YES — **verified real, later fixed** |
| C6 | NO | 35 / 1719 | **YES** (HIGH) | **YES** (HIGH) | both YES — **verified real** |

## Totals

| Measure | DIFF_ONLY | DIFF_PLUS_BUNDLE | Δ |
|---|---:|---:|---:|
| **keyed defect identified — 5 defect tasks** | **0/5** | **0/5** | 0 |
| Q1 = YES on a defect task | 3/5 | **5/5** | +2 |
| **correct control decisions (NO)** | **0/6** | **0/6** | **0** |
| defect reported on a control | 5/6 | **6/6** | +1 |
| **confident (HIGH) reports on a control** | **2** | **2** | 0 |
| abstentions | 3/11 | **0/11** | −3 |
| regressions | — | **0** *(none possible: A had no correct decision)* | — |
| evidence-supported | 11/11 | 11/11 | 0 |

## M22 did not replicate

| | M22 (abouelsid) | M23 (halaw) |
|---|---|---|
| correct control decisions | 1/11 → **6/11** | 0/6 → **0/6** |
| verified false positives | **0/11 both** | see below |
| abstentions | 9 → 4 | 3 → **0** |

## The reason, and it is about the key rather than the reviewers

Of the six control commits, **at least four contain a real defect**, verified against the source:

- **C3** `28f2411` — the rewritten import drops `id => $row[0]` and `is_international => 0`.
- **C5** `b5b2859` — `'location' => 'nullable|required|max:255'`, a contradictory rule. **Later fixed**
  by commit `9ba34b3`, whose subject is `bugs`.
- **C6** `00095a9` — `ProductsExport` headings say `en, ar` while the select is `ar, en`.
- **C4** `1a55793` — the same ar/en misalignment, found independently.

**My control criterion (S3) was "no later commit whose subject begins with `fix`".** It missed
`9ba34b3 "bugs"` — a fix by any reasonable reading. The criterion that worked on abouelsid's
descriptive commit messages does not transfer to a repository with terse ones.

So the controls are not controls, and the "false positive" column cannot be computed for this
corpus. That is a defect in **this milestone's method**, discovered by the reviewers.
