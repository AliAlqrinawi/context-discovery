# M19 · the scored re-run, where the tool actually speaks

> **Classification: A — there is a measurable usefulness signal, and it is narrow.** Over 6 valid
> tasks the bundle took correct decisions from **1/6 to 4/6** with **zero regressions**, and one of
> those three improvements rests on evidence that exists *only* in the bundle. Against it: dependency
> identification **fell** 2/6 → 1/6, one task was **compromised** by repository drift, one key entry
> is **contested** by both reviewers, and the corpus contains **no defect to find**.
>
> No production file changed. The M17 instrument was used exactly as written.

## 1 · Objective

M17 could not answer whether the bundle helps, because 4 of 5 commits produced nothing. M18 found
seven commits that produce meaningful context. This asks M17's question again, on those seven.

## 2 · Candidate set

M18's seven, used exactly, none added or removed: `0a6e7d1`, `721ea3c`, `4411454`, `e770086`,
`04328a5`, `560b21b`, `11c0ced`. All reproduce at the recorded item and token counts. Measured at
CLI **`22f2913`** against corpus **`450d91f`**, all three trees clean at start.

## 3 · Reviewer isolation

Fourteen fresh general-purpose language-model agents — one per (task, condition) — with no
conversation history, no prior exposure to the repository, each pointed at a directory containing
only its own packet. No agent saw both conditions of any task, and no agent saw another's answers.

**The reviewer is an LLM proxy, not a human reviewer.** This measures what a language model
concludes. It is evidence about a proxy for the hypothesis, not about the hypothesis, and nothing
below may be read as a claim about human reviewers.

Isolation was enforced **by instruction**, not by sandboxing: the agents had file tools and were told
which files to read. Packets were grepped for key terms before the run; the only hit was the word
"Expected" inside a commit's own test code.

## 4 · Protocol

M17's, unchanged — `tests/Acceptance/fixtures/experiment-17/scoring-protocol.md`, untouched since
M17's commit. Same five questions, same wording, none added or removed.

## 5 · Answer key, and a weakness recorded before scoring

Written from the repository's history and source before any reviewer ran. **All seven tasks key to
NO DEFECT**, because M18's candidate criteria (C1–C5) selected on bundle size, diff size and
readability — never on whether a defect was present — and five of the seven are `fix:` commits.

> **This corpus cannot measure defect detection.** There is no positive case in it. It can measure
> the false-positive rate, evidence access and confidence, and nothing else.

That was written into the key, not discovered afterwards. Ground-truth strength is labelled per task:
one **WEAK** (author-derived, K1), six **MODERATE** (the commit is itself a fix whose mechanism is
verifiable in its own diff, with no later commit amending it). **No task reached STRONG** — none of
the seven has a later real commit fixing something it introduced.

## 6 · Blind-measurement integrity

| Check | Result |
|---|---|
| answer key existed before reviewers ran | ✅ |
| packets contained no key material | ✅ verified by grep |
| reviewers had no repository access | ⚠️ **by instruction, not sandboxed** |
| A and B received byte-identical diffs | ✅ verified by `cmp`, all seven |
| B received only the generated bundle as extra | ✅ |

**K1 is classified COMPROMISED and excluded from the totals.** Bundles are generated against the
repository at HEAD, not against each commit's own tree. Two of `0a6e7d1`'s three changed files no
longer exist there — a later refactor moved the controller — and *both* of K1's diagnostics read
`unreadable path`. Its bundle therefore resolved against drifted code.

The same drift affects **6 of M18's 46 commits (13%)**, all six carrying an `unreadable path`
diagnostic. This is a limitation of M18's measurement discovered by M19; it means M18's coverage
figure is, if anything, *understated*. M18's classification is not reopened.

## 7 · Results over the 6 valid tasks

| Measure | DIFF_ONLY | DIFF_PLUS_BUNDLE | Δ |
|---|---:|---:|---:|
| **correct decisions** | **1/6** | **4/6** | **+3** |
| high-confidence incorrect | 0 | 0 | 0 |
| low-confidence incorrect | 1 | 1 | 0 |
| **abstentions** | 4 | **1** | **−3** |
| defect identification | *not measurable* | *not measurable* | — |
| **dependency/member identified** | **2/6** | **1/6** | **−1** |
| evidence-supported | 6/6 | 6/6 | 0 |

All fourteen Q5 quotations verified verbatim in that reviewer's own material.

## 8 · Pairwise analysis

**K3 `4411454` — improvement *and* evidence shift. The single most informative cell.**
`ProfileController` mass-assigns `name`, `email` and `password` through `$user->update([...])`. If
any were absent from `User::$fillable`, Eloquent would discard the write while the endpoint still
answered `password_updated`. The diff cannot settle it. DIFF_ONLY **abstained**. DIFF_PLUS_BUNDLE
answered **NO defect** and quoted `'password' => 'hashed',` — a string that appears **nowhere in the
diff** and comes from the bundle's `User::casts` slice. This is the one cell where a reviewer
demonstrably used context that DIFF_ONLY could not have had.

**K4 `e770086` — improvement.** DIFF_ONLY abstained, worried that a literal `'all'` page value could
collide with the new sentinel cache key. DIFF_PLUS_BUNDLE answered NO. Its Q3 cites a **bundle
diagnostic** — *"call sites truncated at 20 for execute under app/"* — so the bundle shaped what it
asked for next, but its Q5 quote is from the diff.

**K5 `04328a5` — improvement.** Both conditions identified `ImageService::store` as the missing
piece; A abstained on it, B decided NO. Q5 from the diff in both. The improvement is real but not
traceable to bundle content.

**K2 `721ea3c` — no change, confidence *fell*.** Both answered NO; B dropped from MEDIUM to **LOW**.
Its bundle is 4 items/185 tokens about pagination defaults. The only confidence movement in the run
is downward, on the task with the least to resolve.

**K6 `560b21b` — no change; both incorrect against a contested key.** See §9.

**K7 `11c0ced` — no change.** Both abstained. Notably DIFF_ONLY named exactly the key's required
context, `UpdateDishDTO::fromRequest`; DIFF_PLUS_BUNDLE named `UpdateDishAction::execute` instead.
This is one of the two cells where the bundle moved dependency identification the wrong way.

**Distraction.** No task regressed and no confident error appeared. M14's retained Eloquent flags,
whose statements ADR-A016 records as arguably false, are present in these bundles and pushed no
reviewer to a wrong conclusion. But the bundle did **redirect attention**: in K7 away from the right
file, and in K4 toward a truncation diagnostic. Redirection is not distraction, and it is not free.

## 9 · Ground-truth surprise — the second in two milestones

**Both K6 reviewers, in both conditions, reported a defect the key does not contain**, and they are
substantively right. From the source at that commit:

```php
private static function index(string $group, string $key): void
{
    $keys = self::keys($group);
    if (in_array($key, $keys, true)) { return; }   // <- index TTL never refreshed
    $keys[] = $key;
    Cache::put(self::indexKey($group), $keys, now()->addDays(self::INDEX_TTL_DAYS));
}
```

The index is written once with a 7-day TTL and never refreshed, while entry TTLs are an hour and
restart on every miss. A hot key's effective lifetime is unbounded; the index's is fixed. After seven
days `flush()` forgets nothing for those keys and stale content is served. The read-modify-write of
the index is also non-atomic across concurrent requests. The class's own docblock states the
invariant the code then fails to maintain: *"Must outlive the longest entry TTL used by callers,
otherwise the index expires while its entries are still cached and they leak."*

**The key was not changed**, per the brief. K6 is scored as both conditions incorrect, and its key
entry is marked **CONTESTED**. The honest reading is that K6's key is unreliable and its two cells
should carry little weight either way.

This is the **second consecutive scored milestone** in which blind reviewers found a real defect that
a hand-written key missed — M17's was a PHP version constraint in `composer.lock`. Two for two is a
finding about the method the whole programme rests on, not about this milestone.

## 10 · Cost

| Task | items | fetched | flagged | tokens |
|---|---:|---:|---:|---:|
| K1 *(compromised)* | 2 | 2 | 0 | 221 |
| K2 | 4 | 3 | 1 | 185 |
| K3 | 7 | 4 | 3 | **299** |
| K4 | 29 | 28 | 1 | 483 |
| K5 | 40 | 26 | 14 | 2198 |
| K6 | 15 | 11 | 4 | 848 |
| K7 | 12 | 8 | 4 | 888 |

Aggregate over the 6 valid: **107 items, 5901 tokens**; median **13.5 items, 665 tokens**.

Relating cost to effect: the three improvements cost 299, 483 and 2198 tokens. **The clearest result
was also the cheapest** — K3, at 299 tokens and 7 items, is the only task where bundle-sourced
evidence demonstrably decided the answer. The most expensive bundle, K5 at 2198 tokens, produced an
improvement whose evidence came from the diff. No relationship between size and effect is visible in
six tasks, and none is claimed.

## 11 · Regression and determinism

- `git status src/ bin/` empty — **no production file changed.**
- Full suite **1077 tests, 4831 assertions, 4 failures** — the same four pre-existing
  `ExperimentKeyTest` failures. Unchanged from M18.
- M1 baseline **30/150 green**; M1–M18 targeted **272/1287 green**.
- **Zero vendor items** in all seven bundles.
- Determinism: every candidate re-run **5 times** across JSON, Markdown and stderr — **0 differing
  outputs of 105 runs**.
- **M18 corpus behaviour identical** across all 46 commits on a full re-run (items, fetched, flagged,
  tokens).

## 12 · Limitations

- **n = 6 valid tasks. No statistical claim is made or available.**
- **No defect in the corpus.** The headline measure is "did not invent a defect and stopped
  abstaining", not "found the bug". M18's criteria selected for readable bundles, not for defects —
  the single biggest weakness of this experiment, and it is inherited, not introduced.
- **The reviewer is a language model.**
- **One key entry (K6) is contested by the evidence**, and one (K1) rests on a compromised bundle.
- **No task has STRONG ground truth.** Six are "the commit is a fix and nothing later amends it",
  which is absence of contrary evidence rather than positive confirmation.
- **Isolation was by instruction, not sandboxing.**
- Bundles resolve against HEAD, not against each commit's tree — 6 of 46 commits affected.

## 13 · Classification

**A — the evidence supports a measurable usefulness signal.**

Choosing A on: correct decisions 1/6 → 4/6 with **zero regressions**; abstentions 4 → 1; and one task
where the deciding evidence provably came from the bundle and could not have come from the diff. The
brief's caution — *"do not choose A merely because one task improves"* — is met: three tasks improved
and none got worse.

The brief also asks that "better correctness", "better evidence access" and "no observable effect"
be distinguished. They are:

| | Tasks |
|---|---|
| **better evidence access** *(bundle-sourced evidence decided it)* | **K3** |
| **better correctness only** *(abstention → correct, evidence from the diff)* | K4, K5 |
| **no observable effect** | K2 (confidence fell), K6, K7 |

Stated plainly: **the strong form of the claim is supported by one task out of six.** The weaker form
— that the bundle reduces unnecessary abstention without introducing confident error — is supported
by three. Neither is supported by enough tasks to be more than a signal, and the corpus could not
have shown the thing the tool is ultimately for, which is helping someone find a bug.

## 14 · Recommended M20 — not started

1. **A corpus with defects in it.** Every scored milestone so far has been unable to measure defect
   detection: M17's corpus was empty of bundles, M19's is empty of defects. Build a candidate set
   from commits that a *later real commit fixes* — M18's history contains such pairs — and require
   STRONG ground truth for at least half the tasks. That is the experiment that would settle this.
2. **Fix the measurement, not the tool.** Generate each bundle against the commit's own tree rather
   than HEAD. This is experiment-harness work, changes no production code, and removes the drift that
   compromised K1 and touches 6 of 46 commits.
3. **Not an extraction rule.** M18 measured the coverage gap and M19 has not shown that closing it
   would help; the case for widening a form is no stronger than it was two milestones ago.
4. **Carry forward:** hand-written keys have now missed a real defect twice out of two. Whatever M20
   is, it should have a second pair of eyes on its key before scoring.

Stopping at M19 as instructed.
