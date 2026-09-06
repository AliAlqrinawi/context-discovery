# M17 · the first scored review run

> **Classification: C — inconclusive.** Not because the bundle failed, and not because it succeeded,
> but because **the tool produced an empty bundle on 4 of the 5 real commits**, so only one task
> could discriminate between the conditions at all. On that one task the bundle changed nothing.
>
> No production code was modified. The pre-registered protocol named this outcome in advance.

## 1 · The question

> Does supplying the generated context bundle improve a reviewer's ability to correctly understand
> and review a change, compared with the diff alone?

## 2 · Answer

**Not established, and the reason is coverage rather than quality.** Every measured quantity is
identical between the two conditions, and 4 of 5 comparisons were vacuous because there was nothing
in the bundle to compare.

The milestone's most useful output is not the score. It is this, measured before any reviewer ran:

> On five real commits from a real Laravel application, the tool emitted context for **one**.

## 3 · Protocol and key — both pre-registered

`tests/Acceptance/fixtures/experiment-17/scoring-protocol.md` and `answer-key.json`, written and
fixed before any bundle was generated and before any reviewer was run. Five questions per task:
defect yes/no/cannot-tell, the defect in a sentence, the most important thing *not* shown,
confidence, and a verbatim quotation of the evidence relied on.

Two commitments in the protocol matter for reading what follows:

- **No statistical test would be computed**, at any point, because n = 5 admits none. Direction and
  mechanism only.
- **Three predictions said the bundle would not help** — T2 (structurally cannot), T3 and T4
  (controls) — and T5 was a negative control where an apparent improvement would be an artefact.
  Written first, so the report could not be assembled around whichever tasks improved.

## 4 · Corpus — five real commits

| Task | Commit | Why chosen | Key |
|---|---|---|---|
| T1 | `ec92403` | already keyed by M7; exercises four context types at once | defect **YES** |
| T2 | `fc573d2` | the purest "evidence is in another file" case in the history | defect **YES** |
| T3 | `fdef4a9` | mass-assignment with `$fillable` **inside** the diff — a control | defect **NO** |
| T4 | `1fa1f66` | one self-contained file — the second control | defect **NO** |
| T5 | `2c2a7b4` | correctness turns on a **config** value X3 defers — negative control | **CANNOT_TELL** |

**Three of the five take their ground truth from a later real fix commit** in the same repository —
T1 from `04328a5`, T2 from `11c0ced` — rather than from the milestone author's judgement. That is the
strongest key available here.

Five is a small corpus and is not presented as more.

## 5 · The finding that arrived before the reviewers

| Task | items | fetched | flagged | tokens | vendor |
|---|---:|---:|---:|---:|---:|
| T1 | 18 | 7 | 11 | 606 | 0 |
| T2 | **0** | 0 | 0 | **0** | 0 |
| T3 | **0** | 0 | 0 | **0** | 0 |
| T4 | **0** | 0 | 0 | **0** | 0 |
| T5 | **0** | 0 | 0 | **0** | 0 |

**Every empty bundle is correct behaviour**, and each for a documented reason:

- **T2, T3** — the migration is a created file (ADR-A018), `Schema::table` is framework-known
  (ADR-A011), `Blueprint` is a dependency class (ADR-A012). Three decisions, each right, and their
  sum is nothing.
- **T4, T5** — **no assertion was extracted at all**; the tool emitted not even a diagnostic. The
  changed lines contain `$request->has(...)`, `$request->boolean(...)`, `$action->execute(...)`,
  `$this->paginated(...)`, `config(...)`, `route(...)`. None is a `Name::member` static call, a `new`,
  or a type position — the closed three-form list (01-architecture.md §3.3). Nothing to see, by
  design.

So the tool fires when a changed line **names a class**. Commits that change method-call chains,
request handling or configuration name none, and produce nothing. That is a **coverage** property
of the frozen form list, not a defect, and no milestone before this one had measured it on real
input, because M7 examined a single commit that happened to use Eloquent statically.

## 6 · Reviewers

Ten independent language-model agents, one per (task, condition): no conversation history, no prior
exposure to the repository, each pointed at a directory containing only its own material. The diff
is byte-identical between conditions, verified by `cmp`.

**The central limitation, stated plainly:** the hypothesis is about a *reviewer*, and what was
measured is what a *language model* concludes. This is evidence about a proxy and cannot be reported
as evidence about human reviewers. Two further limits: isolation was enforced **by instruction**,
not by sandboxing — the agents had file tools and were told which files to read; and the tasks were
chosen by the author who wrote the key, though from history and before any bundle existed.

## 7 · Results

| Measure | DIFF_ONLY | DIFF_PLUS_BUNDLE | Δ |
|---|---:|---:|---:|
| correct decisions | **2/5** | **2/5** | **0** |
| high-confidence incorrect | **0** | **0** | 0 |
| low-confidence incorrect | 1 | 1 | 0 |
| abstentions | 3 | 3 | 0 |
| defect identified (of 2) | 0/2 | 0/2 | 0 |
| dependency identified | 1/5 | 1/5 | 0 |
| uncertainty rate | 60% | 60% | 0 |
| evidence-supported | 5/5 | 5/5 | 0 |

Every reviewer answered **MEDIUM** confidence. Not one HIGH, in either condition — so the
false-confidence measure, the one the protocol weighted most heavily against the bundle, recorded
zero on both sides and discriminates nothing here.

All ten Q5 quotations were verified mechanically to occur verbatim in that reviewer's material.

Against the pre-registered criteria: (1) met, (2) met, (3) **not met** — on T1 and T2 the bundle
condition identified the keyed defect no more often than the diff alone, which is to say not at all.
**Inconclusive → C.**

## 8 · Failure analysis

**No task flipped.** There is no A-correct/B-incorrect case and no A-incorrect/B-correct case. On
T2–T5 that is trivially explained: the bundle was empty. Only T1 is interesting.

### T1 — the bundle had 18 items and changed nothing

Both conditions answered **YES**, and both named the **same defect** — one the key does not contain:

> `endroid/qr-code` 6.1.3 requires PHP `^8.4`, while `composer.json` declares `"php": "^8.2"`.

**Verified: this is real.** At `ec92403`, `composer.json` line 9 reads `"php": "^8.2"` and the lock
records `endroid/qr-code 6.1.3 -> ^8.4`.

Three things follow, and the third is the important one.

1. **The key missed a genuine defect that both blind reviewers found.** M7's key missed it too. It is
   recorded here and **the key was not retroactively changed** — the brief forbids it, and the miss
   is more informative left standing. It is a fact about hand-written keys, and about a diff whose
   most reviewable line was in a lockfile nobody thought to key.
2. **It cost nothing in the comparison**, because both conditions made the same call for the same
   reason from the same quotation. The bundle neither caused nor prevented it.
3. **The bundle's most relevant item did not lead the reviewer to the keyed defect.** The bundle
   contained, in plain words, *"ASSUMPTION: this code assumes a surrounding transaction; caller not
   checked"* — which names the mechanism of the keyed defect almost exactly. The reviewer read it and
   went to `composer.lock` instead. One observation, not a result, but the honest reading is that
   **a flag naming a risk is not the same as a reviewer acting on it.**

The only visible bundle effect on T1 is Q3: the diff-only reviewer asked for `composer.json`, and the
bundle reviewer asked for `app/Http/Controllers/Controller.php::deleted` — E5.4, the trait member
ADR-A010 cannot reach. Neither matches the key's `required_context`, so neither scores; but the
bundle demonstrably moved the reviewer's attention from packaging to the code, and onto the one gap
the architecture has already declined to close.

### Distraction — the thing the milestone was told to watch for

**None observed, and M14's retained Eloquent flags were specifically at risk.** T1's bundle carries
eleven flags whose statement ADR-A016 records as arguably false — *"named reference could not be
resolved on disk; contract unverified"* said of `Setting::updateOrCreate`, which is perfectly
resolvable — alongside the very model surfaces that resolve them. The reviewer neither repeated that
false claim nor was pushed toward a wrong answer by it.

On T3 and T4 the controls held: the empty bundle manufactured no doubt, and both conditions gave
identical answers. On T5, the negative control, the bundle produced **no** increase in confidence —
both conditions abstained with MEDIUM and both named `config/app.php::frontend_url`. Had confidence
risen there, §9 would have required treating the whole result as suspect.

### T3 — both conditions disagree with the key, identically

Both reviewers called `'whatsapp_number' => $this->whatsapp_number ?? $this->phone` a defect: the
read path cannot express the null the write path supports, so a read-modify-write round trip
persists the phone number. The key says **NO** — the commit message calls the fallback deliberate,
and no later commit amends it.

The key stands unchanged. The disagreement is identical in both conditions, so it does not affect
the comparison at all — but it is worth recording that two independent reviewers found the same
design smell the author considered a feature.

## 9 · Cost

T1: **18 items, 7 fetched, 11 flagged, 606 tokens** against a 632-line diff. Everything else: zero.

Is any observed improvement disproportionate to the context supplied? **The question does not arise,
because no improvement was observed.** What can be said is the converse: on T1 the tool spent 606
tokens and moved no measured outcome. That is one observation on one commit and is not a verdict on
the bundle's worth — but it is the only cost-versus-benefit datum this milestone produced, and it
does not favour the bundle.

## 10 · Determinism and regression

No production code was modified — `git status src/ bin/` is empty.

- Full suite: **1077 tests, 4831 assertions, 4 failures** — the same four pre-existing
  `ExperimentKeyTest` failures (private Phase 0 fixtures absent). Unchanged from M16.
- M1–M16 targeted suites: **272 tests, 1287 assertions, green.**
- M1 baseline: **30 tests, 150 assertions, green** — no entry moved.
- **Zero vendor items** in all five bundles.
- Determinism: 5 runs each, 1 distinct result on both streams — T1 JSON @8000, T1 Markdown @8000,
  T1 JSON @300 (drops forced), T2 Markdown @8000.

## 11 · Classification

**C — inconclusive, because the corpus could not exercise the thing being measured.**

Precisely: 4 of 5 tasks had an empty bundle, so the effective n for the comparison is **1**, and on
that one task the bundle changed no measured outcome. That is not evidence the bundle is worthless;
it is an absence of evidence either way, and the protocol required saying so.

**The smallest experiment that would make this measurable** is not a bigger corpus of arbitrary
commits — it is a corpus of commits **selected for producing a non-empty bundle**, scored the same
way. That selection is legitimate (it asks "when the tool speaks, does it help?" rather than "how
often does it speak?"), provided the two questions are never conflated. §5 already answers the
second: **1 in 5**.

## 12 · Remaining gaps

- **Coverage is the binding constraint, and it is newly measured.** Four of five real commits produce
  nothing. Every earlier milestone improved what the bundle contains *when it fires*; none asked how
  often it fires.
- **A flag naming a risk did not make a reviewer act on it** (T1). One observation, worth a key.
- **Hand-written keys miss real defects** — both blind reviewers found one that two answer keys did
  not. This is a finding about the method the whole programme rests on.
- **The human question is untouched.** The reviewer here is a language model.

## 13 · Recommended M18 — not started

1. **Measure coverage properly.** Run the tool over all ~40 commits of the real repository and report
   the distribution of bundle sizes, and for the empty ones, which decision produced the silence.
   Pure measurement, no key, no production change — and it is the prerequisite for M17's re-run.
2. **Then re-run M17's protocol** on the commits that do produce a bundle, unchanged, so the two
   milestones compose.
3. Not a new extraction rule. §5's four empty bundles were each *correctly* empty under a decision
   that has its own ADR; widening any of them before the coverage distribution is known would be
   guessing at which one matters.

Stopping at M17 as instructed.
