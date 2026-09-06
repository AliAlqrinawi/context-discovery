# Experiment 20 · a corpus that actually contains defects

M17 could not measure usefulness (4 of 5 bundles empty). M19 measured a narrow signal but **no task
had STRONG ground truth and the corpus contained no defect at all** — every task keyed to "no
defect", so "did the bundle help find the bug?" was unanswerable.

This corpus is built from **bad-commit → later-real-fix pairs**, so the ground truth is the
repository's own history rather than the author's judgement.

## The harness defect, corrected first

M19 found that bundles were generated with `--repo` pointing at the corpus repository's **HEAD**
while `--diff` was a historical commit. Source resolution therefore used today's files.

`harness/bundle-at-commit.sh` checks the commit out into a detached `git worktree` and points
`--repo` there. Pinned by `tests/Acceptance/HarnessResolvesAtCommitTreeTest.php`; recorded as
**ADR-A022**. **No production file changed.**

Measured effect on M19's compromised task `0a6e7d1`: 2 items / 221 tokens (with the reviewed
controller unreadable) → **4 items / 449 tokens**. Across this corpus the corrected harness produces
materially larger bundles than M18/M19 recorded — `ec92403` goes from 18 items / 606 tokens to
**37 / 986** — so **M18's and M19's figures are understated, not inflated**.

## Corpus

| | Commit | Key | Strength | Fixed by |
|---|---|---|---|---|
| D1 | `ec92403` | YES | **STRONG** | `04328a5`, `2c2a7b4` |
| D2 | `fc573d2` | YES | **STRONG** | `11c0ced` |
| D3 | `e5e48ce` | YES | **STRONG** | `1fa1f66` |
| D4 | `ec76dc4` | YES | **STRONG** | `dae67ab` |
| D5 | `44726d0` | YES | MODERATE | `721ea3c` |
| C1 | `4411454` | NO | MODERATE | — control |
| C2 | `e770086` | NO | MODERATE | — control |

**4 of 7 STRONG**, meeting the "at least half" standard. Selection criteria S1–S4 were fixed before
selecting; `8054003` and `4c6a83b` were excluded on size despite having real later fixes.

## Files

`answer-key.json` · `answer-key.md` — written first, unchanged after scoring
`harness/bundle-at-commit.sh` — the corrected harness
`captured/` — each candidate's diff, JSON and Markdown bundles, stderr
`packets/D*-A`, `packets/D*-B` — exactly what each reviewer saw
`observations/` — all 14 answers verbatim, and the scoring

## Result

See [`docs/research/M20-defect-corpus.md`](../../../../docs/research/M20-defect-corpus.md).
