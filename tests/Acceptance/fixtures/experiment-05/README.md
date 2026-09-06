# Experiment 05 · the first end-to-end run of the finished tool

The commit Phase 0 never ran. `requirements.md` names it as the one thing to feed the finished
tool first, *"because if the move-set is not actually bounded, that is where it breaks"*.

Unlike Experiments 1–4 this is not a polished, hand-picked commit: it is a whole feature PR, taken
as it was pushed, including its routes, its seeder change and its tests.

| | |
|---|---|
| Repository | `abouelsid-backend` — **private, operator-supplied**, Laravel 12.64.0, `vendor/` installed |
| Commit | `ec924032f3b3167fd97c51e8b15af73a59c011f6` — *"feat: menu PDF upload with permanent URL and QR code with logo"* |
| Shape | 9 files, 519 insertions; 7 of them PHP |
| Budget | 8000 |

## Files

| File | What it is |
|---|---|
| `diff.patch` | the commit, exported verbatim |
| `diff-with-noise.patch` | the same plus one unrelated real commit (`fdef4a9`) and `noise-lexical.diff` |
| `noise-lexical.diff` | crafted noise: a docblock, `@see Log::warning()`, comments naming `Branch::create()`, and string literals containing `Setting::updateOrCreate`, `Illuminate\Support\Facades\Storage::disk`, `Endroid\QrCode\Builder\Builder` |
| `answer-key.json` | **written before the tool was run** (ADR-001), 14 rows: 3 FETCH, 1 FLAG, 10 OMIT |
| `observed-bundle.json` · `.md` · `observed-diagnostics.txt` | what the binary actually produced |

## Reproducing

```bash
./bin/context-discover --repo <abouelsid-backend> \
  --diff tests/Acceptance/fixtures/experiment-05/diff.patch \
  --budget 8000 --format json
```

The repository is not shipped, for the same reason the four Phase 0 fixtures are not: it is private.
The captured outputs are committed so the analysis in
[`docs/research/M7-experiment-05.md`](../../../../docs/research/M7-experiment-05.md) can be checked
without it.

## Result in one line

**ACCEPTABLE WITH KNOWN GAPS.** Nothing from `vendor/` reached the bundle, lexical noise produced
nothing, the budget behaved exactly as specified, and output was byte-identical across runs — but
precision was **1 item in 26** and recall **1 finding in 4**, and the single most-earned move in the
whole corpus (Experiment 1's model surface) never fired.
