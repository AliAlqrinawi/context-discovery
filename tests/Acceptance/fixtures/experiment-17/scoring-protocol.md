# Experiment 17 · scoring protocol

**Pre-registered.** Written and committed before any task was run and before any scored output was
seen. Nothing in it may be adjusted afterwards; if it turns out to be the wrong protocol, that is a
finding about the protocol, recorded as such.

## 1 · What a review task is

One real commit from `abouelsid-backend`, presented as its unified diff, with the question set in §2.
Five tasks, listed in `answer-key.json`. No task is synthetic.

## 2 · What the reviewer must decide

The same five questions for every task and both conditions, answered from the supplied material
alone:

| | Question | Answer form |
|---|---|---|
| **Q1** | Does this change contain a correctness defect? | `YES` / `NO` / `CANNOT_TELL` |
| **Q2** | If YES, state it in one sentence. | free text, or `-` |
| **Q3** | What is the single most important piece of code **not shown** that you would need in order to be sure? | `file::member`, or `NONE` if the material is sufficient |
| **Q4** | Confidence in Q1. | `HIGH` / `MEDIUM` / `LOW` |
| **Q5** | Quote the exact text you based Q1 on. | a quotation from the supplied material, or `NONE_VISIBLE` |

Q3 and Q5 exist to separate *being right* from *being right for a visible reason*. Q5 is checked
mechanically: the quoted string must occur in the material that reviewer was given.

## 3 · What counts as correct

- **Correct decision** — Q1 equals the key's `defect_present`. `CANNOT_TELL` is never counted correct;
  it is counted as an abstention and reported separately under uncertainty.
- **Defect identified** — only for tasks whose key has `defect_present: YES`. Q2 must name the same
  mechanism the key names. A different defect, or a vague statement that would fit any change, does
  not count. Scored by the milestone author against the key's `defect` field, which was written
  first.
- **Dependency identified** — Q3 names a file or member listed in the key's `required_context`; or,
  where the key says the diff is sufficient, Q3 is `NONE`.
- **Evidence-supported** — Q5's quotation occurs verbatim in that condition's material.

## 4 · Scoring DIFF_ONLY

The reviewer receives the commit's unified diff and nothing else. No repository access, no file
reads, no search, no prior knowledge of the project.

## 5 · Scoring DIFF_PLUS_BUNDLE

The reviewer receives **the same diff, byte for byte**, plus the Markdown context bundle the tool
produces for that commit at budget 8000, plus the bundle's stderr diagnostics. No repository access.

## 6 · Uncertainty

Recorded, never discarded, on two axes: `CANNOT_TELL` on Q1, and `LOW` on Q4. Both are reported as
rates. An abstention is not a wrong answer and is not scored as one.

## 7 · False confidence versus ordinary error

Reported as distinct categories and never merged:

| Category | Definition |
|---|---|
| **high-confidence incorrect** | Q1 wrong **and** Q4 = `HIGH` |
| **low-confidence incorrect** | Q1 wrong **and** Q4 ∈ {`MEDIUM`, `LOW`} |
| **abstention** | Q1 = `CANNOT_TELL` |

A rise in high-confidence-incorrect answers counts **against** the bundle even if raw accuracy rises.
Supplying context that makes a reviewer confidently wrong is worse than supplying nothing.

## 8 · Time

**Not measured.** The reviewer is a language model, and its wall-clock time is not a proxy for a
human reviewer's. Recording it would invite a comparison the design cannot support.

## 9 · What would count as a meaningful improvement

Fixed in advance, and deliberately conservative because **n = 5**.

**No statistical test will be computed or reported.** With five tasks per condition there is no
threshold at which a difference becomes significant, and inventing one after seeing the numbers is
the failure this protocol exists to prevent. The experiment can establish *direction and mechanism*,
per task, and nothing stronger.

The result is **supportive** only if all three hold:

1. DIFF_PLUS_BUNDLE's correct decisions ≥ DIFF_ONLY's; **and**
2. DIFF_PLUS_BUNDLE's high-confidence-incorrect count ≤ DIFF_ONLY's; **and**
3. on **T1 and T2** — the two tasks whose key says the diff alone is insufficient — DIFF_PLUS_BUNDLE
   identifies the defect where DIFF_ONLY does not.

The result is **against** the bundle if high-confidence-incorrect rises, or if a task the reviewer
got right on the diff alone is got wrong with the bundle.

Anything else is **inconclusive**, and M17 is then classified **C**.

`T5` is a deliberate **negative control**: its question turns on a configuration value the tool does
not resolve (X3), so the bundle is expected *not* to help. If it appears to help there, the effect is
an artefact and the whole result is suspect.

## 10 · Reviewer, and the limits of what this measures

The reviewer is a **fresh language-model agent** with no conversation history, no filesystem access
and no prior exposure to the repository — one agent per (task, condition), so no agent sees both
conditions of the same task, and no agent can browse for the context the other condition was given.

**This is the central limitation of the experiment and it is stated here rather than in a footnote.**
The hypothesis is about a *reviewer*; what is measured is what a *language model* concludes. The
result is evidence about a proxy. It cannot be reported as evidence about human reviewers, and no
conclusion in the M17 report may be phrased as though it were.

Two further limits:

- **n = 5**, from one repository, one author, one framework.
- The tasks were selected by the milestone author, who also wrote the key. Selection was made from
  the repository's history *before* any bundle was generated, and three of five tasks take their
  ground truth from a **later real fix commit** rather than from the author's judgement — but the
  selection itself is not blind.

## 11 · Reproducibility

`answer-key.json` holds the key. `tasks/` holds the exact material given to each condition.
`observations/` holds the raw answers. The bundles are regenerated by
`bin/context-discover --diff tasks/<id>.diff --repo <abouelsid-backend> --budget 8000 --format markdown`.
