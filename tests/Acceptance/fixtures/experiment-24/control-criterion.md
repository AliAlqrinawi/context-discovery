# Experiment 24 · the pre-registered control criterion

**Frozen before any candidate was evaluated.** Written in full here, then applied mechanically. It is
not amended afterwards; where it fails, the failure is reported.

## The question

> Can a repository-independent criterion select control commits with sufficient evidence that the
> commit is not a defect-fixing change, without relying on commit-message conventions?

## What the criterion may not use

Commit-subject prefixes (`fix:`, `bug:`), branch names, researcher-created labels, the researcher's
sense that a change "looks harmless", whether the generated bundle is empty, whether a reviewer
agrees, or anything discoverable only after seeing reviewer answers.

## Two pieces of evidence, both required

### E1 · Content survival — *negative* evidence, mechanically checkable

For commit `C`, take every line `C` **adds** to a `.php` file that is **substantive**: after trimming,
at least 15 characters, and not a lone brace, `<?php`, `use` statement, blank line or comment.

For each such line, ask whether that exact text still exists **anywhere in the repository at HEAD**.

- If **every** substantive added line survives → **E1 passes**: no later commit altered what this
  commit wrote.
- If **any** does not → **E1 fails**: something later changed it.

E1 matches on *content*, never on messages, so it is indifferent to how the project words its
commits. Any researcher can re-run it. It is deliberately conservative: a later rename, move or
reformat also makes a line vanish, which pushes a candidate away from CONTROL rather than toward it.

### E2 · Behavioural coverage — *positive* evidence

E1 alone establishes only *"no recorded correction"*, and **absence of a correction is not evidence
of correctness** — a defect nobody has found yet survives to HEAD untouched. E2 is the requirement
that something in the repository positively asserts the behaviour.

At HEAD, at least one file under a test root (`tests/`, `spec/`, `Tests/`) must reference a symbol
the commit added or changed — a class, method or route name taken from the commit's own diff.

- If such a reference exists → **E2 passes**.
- If not → **E2 fails**: nothing in the repository asserts this behaviour, so there is no positive
  evidence and the candidate cannot be a CONTROL.

## States

| State | Threshold |
|---|---|
| **CONTROL** | **E1 passes AND E2 passes.** There is both no recorded correction and positive repository evidence asserting the behaviour. |
| **DEFECT** | E1 fails **and** the superseding commit's changed lines fall inside the same function or member the candidate wrote — a content-linked correction, established without reading either message. |
| **CONTESTED** | Any other combination. Specifically: E1 passes but E2 fails (no positive evidence); or E1 fails but the supersession is a move, rename or reformat rather than a correction of the same member; or the two disagree. |
| **UNUSABLE** | Merge commit, root commit, no substantive PHP addition, or outside the size bound (≤ 500 changed lines). |

**Ambiguity resolves to CONTESTED, never to CONTROL.** Absence of evidence is never promoted to
evidence of absence.

## What this criterion cannot do, stated in advance

It cannot establish that a commit is defect-**free**. Repository history can show that a defect *was
found* (a later correction); it can never show that none exists. E2 narrows the gap by demanding a
positive assertion, but a test can exist and still not cover the defective path. A CONTROL under this
criterion means *"no recorded correction, and the behaviour is asserted somewhere"* — not *"correct"*.

If that is too weak to support a false-positive measurement, the honest outcome is **C**.

## Pre-registered negative test

M23's controls `28f2411` (C3), `b5b2859` (C5) and `00095a9` (C6) were later shown to contain real
defects. The criterion **must not** classify any of them CONTROL. Catching them as DEFECT is a pass;
CONTESTED is a pass; CONTROL is a failure of the criterion.
