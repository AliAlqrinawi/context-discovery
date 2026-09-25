# Classification · reviewer answers against a key

You are classifying the answers that code reviewers gave about commits from one Laravel
application. You did not review anything yourself and you must not form your own opinion of the
commits; your job is to apply the literal rules below to each review, one at a time, and record
what you find.

## What you are given

A directory `classification/` containing:

- `keys.json` — for each task id (`T0` … `T8`), the reference answer: `defect_present`
  (`YES`/`NO`), `defect` (the mechanism, one paragraph, or `-`), `required_context` (a list of
  `path::member` strings), and `inherited_member_dependence` (`YES`/`NO`).
- `cell-01/` … `cell-54/` — one directory per review, in an order that means nothing. Each holds:
  - `task.txt` — the task id (which commit this review was of), nothing else;
  - `answers.md` — the reviewer's five answers, Q1–Q5;
  - `change.diff`, `context-bundle.md`, `context-diagnostics.txt` — the exact three files that
    reviewer was given.

You will not be told anything else about a cell, and you must not try to infer anything about
it beyond what the rules ask. Cells that share a task id are different reviews of the same
commit; treat each on its own.

## What to record

One row per cell in `classes.tsv`, tab-separated, header row first, columns exactly:

```
cell	task	q1	q4	correct_decision	defect_identified	q5_verbatim	q3_text	q3_names_path	q3_path_term	q5_quotes_assumption	q3_names_cited_member	notes
```

Fill each column by these rules and no others:

| Column | Rule |
|---|---|
| `q1`, `q4` | copied from `answers.md` |
| `correct_decision` | `YES` if Q1 equals the key's `defect_present`; `NO` if Q1 is the other of `YES`/`NO`; `ABSTAIN` if Q1 is `CANNOT_TELL` |
| `defect_identified` | Only when the key's `defect_present` is `YES`: `YES` if Q2 names **the same mechanism** the key's `defect` names — the same code path going wrong for the same reason; `NO` if Q2 names a different defect, or a statement that would fit any change. `NA` when the key says `NO`. Do not credit a vague match. |
| `q5_verbatim` | `YES` if the Q5 quotation, with its surrounding quote marks removed, occurs **character for character** in at least one of the three files given to that reviewer; `NO` otherwise; `NONE` if Q5 is `NONE_VISIBLE` |
| `q3_text` | Q3 copied exactly |
| `q3_names_path` | `YES` if the Q3 text mentions **any** of the following, `NO` otherwise: the word `ApiResponse`; `Controller` used as a class or file on its own (`Controller.php`, `App\Http\Controllers\Controller`, "the base controller", "the parent controller") — **not** the word inside another class's name such as `DishController`; the name of any trait or parent class that appears after the words `it is declared in` in a sentence beginning `ASSUMPTION:` anywhere in that cell's `context-bundle.md`; or a phrase of the form *where `success` / `created` / `deleted` / `paginated` is defined* or *declared*. This list is closed. If you think something else should count, record it in `notes` and answer `NO`. |
| `q3_path_term` | the matched term from the list above, or `-` |
| `q5_quotes_assumption` | `YES` if the Q5 quotation is part of, or contains, a line in `context-bundle.md` that begins `ASSUMPTION:` **and** contains both `is not declared in` and `it is declared in`; `NO` otherwise (including when Q5 is `NONE_VISIBLE`) |
| `q3_names_cited_member` | `YES` if Q3 names the **body, signature, implementation or definition** of a member that some `ASSUMPTION:` line in that cell's `context-bundle.md` cites after `it is declared in … at` — for example, Q3 is `app/Traits/ApiResponse.php::success` and such a line cites `app/Traits/ApiResponse.php:9` for `success()`; `NO` otherwise, including when no such line exists in the file |
| `notes` | anything the rules did not let you express, in one line; otherwise `-` |

## Rules

1. Work through the cells in the order the directory lists them. Do not group them by task first.
2. Apply each rule as written. Where a rule is a closed list, it is closed. Where it says
   "character for character", check it mechanically (search the file for the string).
3. The `defect_identified` judgement is the only one that requires reading for meaning. Make it
   against the key's `defect` paragraph only — not against your own view of the commit, which you
   must not form.
4. Do not read `answers.md` files from other cells while judging one, except that you may
   re-open a cell to correct a transcription error.
5. Record, do not interpret. There is no summary, no tally, no conclusion to write. The TSV is the
   whole output.
6. When you have finished, do not revise earlier rows in the light of later ones.
