# Experiment 22 · the false-positive axis

**Incomplete.** All 11 DIFF_ONLY reviewers finished; **7 of 11 DIFF_PLUS_BUNDLE agents terminated
with HTTP 429** (session rate limit). Four complete pairs. Missing cells are recorded as `MISSING` —
nothing was substituted or inferred.

11 control commits keyed NO, bundles from **1 item / 25 tokens** to **76 / 2423**, generated with
M20's corrected same-commit-tree harness.

## What survives the truncation

**K03 replicated a third time** — DIFF_ONLY abstains, DIFF_PLUS_BUNDLE answers NO quoting
`'password' => 'hashed',` from the bundle's `User::casts` slice. M19 K3, M20 C1, M22 K03: three
reviewers, three milestones, one commit, the same transition.

**M21 did not replicate.** Its 5-of-6 (83%) false-positive rate came back as **1 of 11 (9%)** here —
and that one is a **verified real defect**, so the spurious rate on this set is **0/11**. The axis is
dominated by which controls are chosen, not by the condition.

**K11 is contested** — keyed NO, but `GetEffectiveSeoAction`'s `robots_noindex` inheritance is dead
code (the column is NOT NULL with a default). Verified; the key was not changed. Sixth key miss in
five milestones.

To finish: re-run the seven `K05–K11` B-arm packets in `packets/*-B/` once the limit resets. The key,
packets and harness are committed and deterministic.

See [`docs/research/M22-false-positive-axis.md`](../../../../docs/research/M22-false-positive-axis.md).
