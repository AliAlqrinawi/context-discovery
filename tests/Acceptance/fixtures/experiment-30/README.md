# Experiment 30 · the reviewer measurement of option B's citation

**Not yet run.** Pre-registered by [ADR-E001](../../../../docs/decisions/ADR-E001-reviewer-measurement-of-the-citation.md)
and [`scoring-protocol.md`](scoring-protocol.md), both committed 2026-09-26 before any packet was
cut and before any defect key for T1/T2/T4/T5 existed.

What waits: a **second author** for the defect keys (T1 `9b8f9c6`, T2 `ee5a2e6`, T4 `f3a7fcd`,
T5 `2996b89`), then the runs, then the packets, then the cells - in that order (ADR-E001 §4).

Handoff material for clean sessions - the second author (`handoff/defect-keys-handoff.md`:
instructions, template, the four diffs), a reviewer cell (`handoff/reviewer-handoff.md`) and the
blind classifier (`handoff/classifier-handoff.md`) - names no tool, arm or comparison.

Layout, when it runs: `corpus.tsv` · `answer-key.json` · `packets/{T}-{A|B}/` (with
`provenance.txt`) · `observations/{T}-{A|B}-r{n}.md` · `classification/`.
