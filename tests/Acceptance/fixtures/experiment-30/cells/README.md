# Experiment 30 · cells

Thirty-six files, `cell-01.md` … `cell-36.md`, one per (task, arm, replicate) over the six-task
corpus (T0, T1, T2, T3, T5, T6 - ADR-E001's note of 2026-09-26), in a **shuffled order sealed
before any cell ran** (`../classification/mapping.sha256`; the plaintext mapping is committed only
after classification, protocol §7). Each file is a complete paste for one fresh reviewer session:
the instructions, then the three packet files inline between `===== BEGIN … =====` /
`===== END … =====` lines, byte for byte from `../packets/`.

Run them in numeric order. Save each session's five-line answer, verbatim, as
`../observations/cell-NN.md`, with a header naming the model id, version and sampling settings.
Do not re-run a cell because its answer was unexpected.
