# M18 · bundle coverage distribution

> **Classification: A — coverage is sufficient for M19.** 22 of 46 real commits (47.8%) produce a
> non-empty bundle; 7 meet a candidate standard fixed before selection. M17's "1 in 5" was a
> five-commit accident, not the population rate.
>
> Measurement only. No production file changed.

## 1 · Objective

M17 could not measure whether the bundle helps, because 4 of its 5 commits produced nothing. This
asks the narrower question first:

> Across a representative set of real commits, how often does the tool produce a non-empty bundle,
> how large are those bundles, and what decisions account for the empty ones?

## 2 · Corpus and selection

The corpus repository holds **47 commits and no merge commits**. The selection rule is *every commit
reachable from HEAD that has a parent* — all **46**. That is the complete population minus the root
commit, which has no diff. Nothing was sampled and nothing filtered, so the "do not select commits
based on whether you already know they produce bundles" constraint is satisfied structurally rather
than by good intentions.

Measured at CLI **`b7623af`** (M17) against corpus **`450d91f`**. Both trees clean at start,
confirmed before the run. Every commit processed normally; **0 unprocessable**.

Per-commit records — SHA, subject, file count, changed lines, file categories, merge status, and
whether the tool processed it — are in `analysis/per-commit.json`, with the diff, JSON, Markdown and
stderr for each in `outputs/`.

## 3 · Pre-registered taxonomy

`empty-bundle-taxonomy.md`, written before the run. E0 no assertion · E1 settled as needing nothing
(A011/A013) · E2 excluded by ownership (A012) · E3 created-file suppression (A018) · E4 visibility
suppression (A019) · E5 budgeted away · E6 other. Each empty commit gets a **set** of observed
categories and one **primary**, the first of E5, E4, E3, E2, E1, E0 that applies.

The distinction it exists to protect:

> **the tool decided no context was needed** (a decision, with an ADR behind it)
> versus **the tool never had a signal to decide about** (E0)

## 4 · Aggregate coverage

| | |
|---|---:|
| commits | **46** |
| non-empty | **22 · 47.8%** |
| empty | 24 · 52.2% |
| mean items | 18.04 |
| **median items** | **0** |
| max items | 163 |
| mean tokens | 789.8 |
| **median tokens** | **0** |
| max tokens | 7894 |
| mean fetched / commit | 15.5 |
| mean flags / commit | 2.6 |
| commits producing flags only | 1 |
| commits producing fetched context | 21 |
| commits producing both | 19 |
| **vendor items, whole corpus** | **0** |

Restricted to the 22 non-empty: **median 18.5 items, median 881 tokens.**

## 5 · Distributions, not averages

**Items per commit**

| 0 | 1–2 | 3–5 | 6–10 | 11+ |
|---:|---:|---:|---:|---:|
| **24** | 2 | 3 | 1 | **16** |

**Tokens per commit**

| 0 | 1–100 | 101–300 | 301–600 | 601–1000 | 1001+ |
|---:|---:|---:|---:|---:|---:|
| **24** | 2 | 4 | 2 | 5 | **9** |

The shape is **bimodal**: a commit almost always produces either nothing or a lot. Only 6 of 46 land
in between. The mean of 18 items describes no commit in the corpus, which is exactly why the brief
asked for buckets.

## 6 · Empty-bundle reasons

| Primary | Commits | % of empty | % of corpus |
|---|---:|---:|---:|
| **E0** — no assertion produced | **18** | 75.0% | 39.1% |
| E3 — created-file suppression | 5 | 20.8% | 10.9% |
| E2 — dependency ownership | 1 | 4.2% | 2.2% |
| E1 / E4 / E5 | 0 | 0% | 0% |

Observed **sets**, which show that most non-E0 silences are several correct decisions at once:

| Set | Commits |
|---|---:|
| E0 | 18 |
| E3+E1+E2 | 3 |
| E1+E2 | 1 |
| E3+E2 | 1 |
| E3 | 1 |

Worked examples, commit → classification → evidence → decision:

- `fdef4a9` → **E3** (observed E3+E1+E2) → stderr `new file: …add_whatsapp_number… own-file context
  is in the diff, not fetched`, `framework reference: …Schema::table…`, `dependency class:
  …Blueprint… surface not fetched` → ADR-A018, then A011, then A012. Three decisions; their sum is
  nothing.
- `0cdb910` → **E2** (observed E1+E2) → `dependency class: … surface not fetched` → ADR-A012.
- `1fa1f66` → **E0** → **no diagnostics at all** → no extractor recognised anything: the added lines
  hold `$request->has(...)`, `$request->boolean(...)`, `$action->execute(...)`, none of which is in
  the closed three-form list.

### E0 splits in two, and the corpus forced it

The taxonomy said to add a category when the corpus produces a genuinely distinct behaviour. It did:

| | Commits | |
|---|---:|---|
| **E0a** — the commit contains **no PHP file at all** | **8** | CI pipelines, Kubernetes manifests, Postman collections, deploy scripts, a composer path fix |
| **E0b** — PHP that **names no class** on any added line | **10** | settings seeds, a middleware tweak, a query-param cast, config-string moves |

These are not the same outcome. E0a is outside a PHP context tool's subject matter and no extraction
rule could or should change it. **E0b is the real coverage gap** — PHP the tool read and had nothing
to say about.

**So of 46 commits: 22 produce context · 6 are silent by a decision with an ADR behind it · 8 are not
PHP · 10 are the gap.** The gap is **10 of 46 (21.7%)**, or 10 of the 38 commits containing PHP
(26.3%) — materially smaller than M17's five-commit sample implied.

## 7 · Non-empty bundle analysis

Of the 22:

| | |
|---|---:|
| contain fetched source (not flags alone) | **21 / 22** |
| flags only | 1 / 22 |
| contain both fetched and flagged | 19 / 22 |
| contain a **model surface** (a fetched slice from `app/Models/`) | **16 / 22** |
| had items dropped at budget 8000 | **1 / 22** |

Assertion kinds represented:

| Kind | in N of 22 |
|---|---:|
| `named_reference` | 19 |
| `unverifiable_premise` | 12 |
| `same_file_symbol_absence` | 7 |
| `same_file_reference` | 3 |
| `changed_signature` | 2 |

Four of the five kinds appear regularly; `changed_signature` — the reverse-caller move — fires
twice in 46 commits. The model surface M14 built reaches **16 of 22** non-empty bundles, which is the
first population-level evidence that it is the tool's most frequently exercised fetch.

## 8 · Change-shape analysis — descriptive only

Correlational. No causal claim is made, and the groups overlap.

| Shape | Commits | Non-empty rate | Median items | Median tokens |
|---|---:|---|---:|---:|
| has a static `Name::member` on an added line | 25 | **21/25 · 84%** | 18 | 848 |
| no static `Name::member` on any added line | 21 | **1/21 · 5%** | 0 | 0 |
| creates ≥ 1 file | 21 | 12/21 · 57% | 12 | 415 |
| modifies only | 25 | 10/25 · 40% | 0 | 0 |
| touches a model | 11 | 9/11 · 82% | 15 | 848 |
| touches a controller | 15 | 13/15 · 87% | 24 | 1078 |
| touches an action/service | 13 | 11/13 · 85% | 15 | 848 |
| touches tests | 12 | 7/12 · 58% | 8 | 296 |
| touches a migration | 6 | 3/6 · 50% | 1 | 25 |
| touches routes | 8 | 7/8 · 88% | 24 | 1078 |
| touches config | 6 | 2/6 · 33% | 0 | 0 |
| contains a non-PHP file | 17 | 6/17 · 35% | 0 | 0 |
| no PHP file at all | 8 | **0/8 · 0%** | 0 | 0 |

The sharpest split in the table — 84% against 5% — is between commits that do and do not put a
static `Name::member` on an added line. That is the closed form list showing through in the data,
described here and **not acted on**: it says where the silence is, not that the silence is wrong.

## 9 · M19 candidate set

Criteria fixed **before** the subset was computed:

- **C1** non-empty bundle;
- **C2** at least one **fetched** item — meaningful context, not a diagnostic alone;
- **C3** ≤ 40 items — a reviewer can actually read the bundle;
- **C4** ≤ 400 changed lines — the diff is reviewable in one sitting;
- **C5** touches `app/` PHP — a ground-truth key can be written from code rather than from CI config.

**7 of 46 qualify.**

| SHA | items | fetched | flags | tokens | Why it qualifies |
|---|---:|---:|---:|---:|---|
| `0a6e7d1` | 2 | 2 | 0 | 221 | pagination across three controllers; small, pure fetched context |
| `721ea3c` | 4 | 3 | 1 | 185 | a default-value fix; the smallest bundle with both levers |
| `4411454` | 7 | 4 | 3 | 299 | profile endpoints; balanced fetched/flagged |
| `e770086` | 29 | 28 | 1 | 483 | **an existing `fix:`** — model scope and repository; ground truth from its own message |
| `04328a5` | 40 | 26 | 14 | 2198 | **the fix for M17's T1 defect**; the largest still readable |
| `560b21b` | 15 | 11 | 4 | 848 | **a `fix:`** turning on a runtime cache driver — a premise case |
| `11c0ced` | 12 | 8 | 4 | 888 | **the fix for M17's T2 defect**; the DTO/action pair M17 could not reach |

Four are `fix:` commits, so a key can be written from the repository's own account of what was wrong
rather than from the author's judgement — the method that made M17's key defensible. Seven is enough
to run a scored experiment that is better powered than M17's effective n of 1, and still small enough
that no statistical claim will be available.

## 10 · Cost distribution

Over 46 commits: mean 790 tokens, **median 0**. Over the 22 non-empty: median **881**, mean 1652.
Mean cost per item across non-empty bundles: **43.8 tokens**.

**Five highest-cost**

| SHA | items | fetched | flags | tokens | dropped |
|---|---:|---:|---:|---:|---:|
| `2996b89` | 163 | 152 | 11 | **7894** | **36** |
| `8054003` | 155 | 137 | 18 | 6483 | 0 |
| `e5e48ce` | 52 | 44 | 8 | 4123 | 0 |
| `ec76dc4` | 66 | 51 | 15 | 3758 | 0 |
| `44726d0` | 79 | 32 | 47 | 2377 | 0 |

**Five lowest-cost non-empty**: `f490fb9` 1 item / 25 tokens · `c48963d` 3 / 60 · `dae67ab` 3 / 126 ·
`721ea3c` 4 / 185 · `0a6e7d1` 2 / 221.

**Exactly one commit in 46 hit the 8000 budget** — `2996b89`, a whole-tree controller refactor, which
dropped 36 items. Budget pressure is a property of sweeping refactors, not of ordinary commits. Not
optimised, as instructed; recorded.

## 11 · Regression

- Full suite **1077 tests, 4831 assertions, 4 failures** — the same four pre-existing
  `ExperimentKeyTest` failures (private Phase 0 fixtures absent). Unchanged from M17.
- M1 baseline: **30 tests, 150 assertions, green.** No entry moved.
- M1–M17 targeted suites: **272 tests, 1287 assertions, green.**
- **Zero vendor items across all 46 bundles.**
- `git status src/ bin/` empty — no production file changed.

## 12 · Determinism

The whole corpus was run **three independent times**. All **138 output files** — 46 × (JSON,
Markdown, stderr) — are byte-identical across all three passes: **0 differing files**. That covers
JSON, Markdown, stderr, item ordering and token accounting in one check, on 46 real inputs rather
than a fixture.

## 13 · Limitations

- **One repository, one author, one framework.** 46 commits from a single Laravel application. The
  coverage rate is a fact about this repository, not about Laravel or about PHP.
- **The corpus is a whole history, not a review queue.** It includes scaffolding commits ("add all
  DTOs, Actions and ImageService") that no reviewer receives as a unit of work, and eight commits
  that are not PHP at all. Both directions of bias are present and neither is corrected.
- **Shape analysis is correlational**, the groups overlap, and no group has a large n.
- **Coverage is not usefulness.** A non-empty bundle is not a helpful one. M17 measured a helpful
  bundle exactly once and found no effect; this milestone deliberately does not revisit that.

## 14 · Classification

**A — coverage is sufficient for M19.**

The evidence: 47.8% of all commits and 55% of PHP-containing commits produce a non-empty bundle;
21 of 22 contain real fetched source rather than diagnostics alone; 16 of 22 contain a model surface;
and **7 commits meet a candidate standard fixed before selection**, four of them `fix:` commits whose
ground truth the repository states itself.

M17's "1 in 5" was a small-sample accident. The population rate is roughly ten times better than the
sample suggested, and a second scored run would be adequately supplied with material.

The counterweight, stated rather than buried: the median commit still produces **nothing**, and
**10 of 46 commits are PHP the tool read and had nothing to say about**. That is a real gap. It is
measured here and deliberately not acted on.

## 15 · Recommended M19 — not started

1. **Re-run M17's protocol, unchanged, on the 7 candidates.** Same five questions, same blind
   reviewers, same pre-registered criteria, a fresh key. The two milestones then compose: M18 says
   how often the tool speaks, M19 says whether it helps when it does. Do not modify the protocol to
   suit the new corpus — the comparison depends on it being the same instrument.
2. **Not an extraction rule.** §8's 84%-versus-5% split will tempt a reader toward instance-call
   extraction. That is a correlation in 46 commits with no key behind it, and ADR-A003's gate exists
   for exactly this moment. If E0b is to be closed, it needs its own experiment, after M19 has
   established whether a non-empty bundle is worth anything at all.
3. **Carry forward, unresolved:** M17's finding that a flag naming a risk did not make a reviewer act
   on it, and that both blind reviewers found a real defect two hand-written keys missed.

Stopping at M18 as instructed.
