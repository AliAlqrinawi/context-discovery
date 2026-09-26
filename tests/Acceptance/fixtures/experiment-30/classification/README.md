# Experiment 30 · classification

Thirty-six cells under the same opaque names as `../cells/` and `../observations/`, built after
all observations were committed (`c5c1ab5`). Each holds the reviewer's five answers (the model
header stripped), the three packet files that reviewer read, and `reference.json` - the locked
key's `defect_present`, `defect` and `required_context` for that change, with the task id,
commit, subject, strength and every other field removed. No file names a task, an arm, a
commit, a count, or this project.

The classifier's material is `../handoff/classifier-handoff.md`. It writes `classes.tsv` here.
`mapping.sha256` is the seal; the plaintext mapping is committed as `mapping.txt` only after
`classes.tsv` is committed (`../scoring-protocol.md` §7).
