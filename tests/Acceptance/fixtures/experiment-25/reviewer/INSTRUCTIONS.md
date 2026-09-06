# Code review tasks

Fourteen independent review tasks, `H01` … `H14`. They are unrelated to one another: nothing you
learn in one applies to any other, and they are in no meaningful order.

## What each folder contains

- `change.diff` — a change proposed to a PHP (Laravel) codebase.
- Some folders also contain `context-bundle.md` and `context-notes.txt` — machine-generated
  supplementary material about code the diff refers to. Where present, treat it as reference
  material you may use or ignore as you see fit.

Folders differ in what they contain. That is expected and carries no meaning.

## How to review

Work through the tasks in whatever order you like. For each one, read what is in its folder and
answer the five questions below in `responses.md`.

Please review only from the material in the task folder. Do not open the repositories the changes
came from, and do not look up the commits — the point is to record what the supplied material
supports.

If you want to stop partway, finish the task you are on and note where you stopped.

## The five questions

For every task:

**Q1. Is there a defect?** `YES` / `NO` / `CANNOT_TELL`

**Q2.** If YES, describe the defect in one sentence.

**Q3.** What is the most important thing you would want to inspect that is not shown?

**Q4. Confidence:** `HIGH` / `MEDIUM` / `LOW`

**Q5.** Quote the exact evidence that supports your answer.

`CANNOT_TELL` and `LOW` are ordinary answers. There is no expectation that a task does or does not
contain a defect, and no benefit to reporting one you are not persuaded by.
