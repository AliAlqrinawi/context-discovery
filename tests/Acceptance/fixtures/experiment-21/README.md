# Experiment 21 · baseline defect detectability

**No bundle is generated or supplied to anyone.** M20 ended at a floor — 0/4 STRONG defects detected
in *both* conditions — which means its comparison had no discriminating power. Before another bundle
comparison can be interpreted, one question has to be answered:

> How often can an isolated reviewer identify a real defect from the **diff alone**?

## Corpus

15 tasks from bad-commit → later-real-fix pairs: **9 defect · 6 control · STRONG 5 · MODERATE 8 ·
WEAK 2**. Diff sizes span **8 to 2375 changed lines**, deliberately, so that *"the defect was buried"*
can be separated from *"the defect was invisible"*.

Selection criteria S1–S4 were fixed before scoring. `c48963d` was excluded at 12,631 lines despite
being a genuine STRONG pair.

## Files

| | |
|---|---|
| `corpus.tsv` | the task list — id, sha, key, strength, fixing commit |
| `answer-key.json` · `answer-key.md` | written first, unchanged after scoring |
| `tasks/*.diff` · `packets/*/change.diff` | exactly what each reviewer saw |
| `observations/` | all answers verbatim, and the scoring |

One fresh reviewer per task. No history, no repository, no bundle, no key.

See [`docs/research/M21-baseline-defect-detectability.md`](../../../../docs/research/M21-baseline-defect-detectability.md).
