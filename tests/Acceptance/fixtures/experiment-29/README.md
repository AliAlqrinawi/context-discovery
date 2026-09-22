# Experiment 29 · the symlinked `vendor/` was never read

**The M20 harness never gave the tool a `vendor/` directory.** `bundle-at-commit.sh` symlinked the
corpus checkout's `vendor/` into the detached worktree; `LocalSourceRepository` resolves every read
through `realpath()` and refuses one that lands outside `--repo`, so the link was refused whole and
the tool ran as though no dependency were installed. It said so nowhere: an unplaceable class is a
`missing PSR-4 entry` diagnostic and a `named_reference / flagged` item, which is exactly what a
genuinely missing dependency produces.

Recorded as **ADR-A026** in the architecture repository. The write-up, with the M19–M23 impact
classification, is [`docs/research/M29-vendor-symlink-correction.md`](../../../../docs/research/M29-vendor-symlink-correction.md).

## The measurement

Engine `e7c919d` (`v0.2.0`, bundle v2), tree clean. Corpus `abouelsid-backend` at `450d91f` with
`composer install` run in it (a real, untracked `vendor/`). Every one of M20's seven commits, plus
ADR-A022's proof commit `0a6e7d1`, generated three ways:

| Arm | Harness | `vendor/` in the worktree |
|---|---|---|
| **A** | `experiment-20/harness/bundle-at-commit.sh` **as committed at the time** (`ln -s`) | a symlink to the main checkout's |
| **B** | `harness-variants/harness-B-real-vendor.sh` — one line changed, `ln -s` → `cp -R` | a real directory |
| **C** | `harness-variants/harness-C-no-vendor.sh` — the vendor line removed | none |

Each run left its four files: `.diff`, `.bundle.json`, `.bundle.md`, `.stderr`. The proof commit was
run in A and B only. 23 runs, 92 output files.

## Result

| Commit | A vs C | A vs B | A (items · tokens) | B (items · tokens) | items only in A | items only in B |
|---|---|---|---:|---:|---:|---:|
| D1 `ec92403` | **byte-identical** | different | 37 · 986 | **18 · 606** | 19 | 0 |
| D2 `fc573d2` | byte-identical | different | 2 · 40 | **0 · 0** | 2 | 0 |
| D3 `e5e48ce` | byte-identical | different | 85 · 4694 | 57 · 4134 | 28 | 0 |
| D4 `ec76dc4` | byte-identical | different | 66 · 3386 | 65 · 3366 | 1 | 0 |
| D5 `44726d0` | byte-identical | different | 97 · 2700 | 78 · 2320 | 19 | 0 |
| C1 `4411454` | byte-identical | different | 16 · 479 | **7 · 299** | 9 | 0 |
| C2 `e770086` | byte-identical | different | 30 · 504 | 27 · 444 | 3 | 0 |
| proof `0a6e7d1` | — | **identical** | 4 · 449 | 4 · 449 | 0 | 0 |

"Byte-identical" covers `bundle.json` **and** `stderr`. Arm A reproduces the recorded
`experiment-20/captured/` item, token and flag counts exactly, so the captures — and the packets,
which are byte-identical to the captures — were made in arm A's condition.

**Every item present in A and absent in B is the same kind of item:** `named_reference`, lever
`flagged`, payload `ASSUMPTION: named reference could not be resolved on disk; contract
unverified`, subject a dependency class (`Illuminate\…`, `Endroid\QrCode\…`, `Laravel\Sanctum\…`).
No item is present in B and absent in A: B is a strict subset of A in all seven. Items are compared
on every reviewer-visible field, ADR-A021's identity.

`analysis/D1-at-head.json` is D1's diff run against the main checkout at HEAD — the M18/M19
condition, real `vendor/`, drifted tree — and is 18 · 606 with 9 unresolved flags, the same as B
and the same as M18 recorded. The "18 / 606 → 37 / 986" growth ADR-A022 and M20 attribute to the
tree correction is therefore the vendor disappearing, not the tree being right.

## Files

- `A-symlink/`, `B-realdir/`, `C-novendor/` — the runs, exactly as produced.
- `harness-variants/` — the two scratch harnesses. Arm A used the committed script unchanged; its
  text at the time is `git show ba14403^:tests/Acceptance/fixtures/experiment-20/harness/bundle-at-commit.sh`.
- `analysis/itemdiff.php` — `php itemdiff.php <A.bundle.json> <B.bundle.json> [-v]`: items in one
  bundle and not the other, keyed on ADR-A021's identity.
- `analysis/classify.php` — the namespace heuristic used to *estimate* the spurious flags in the
  M22 and M23 captures, which were not re-run. On the seven commits above, where the truth is
  measured, it is exact on six and over-counts D4 by one.

## What changed because of this

- `experiment-20/harness/bundle-at-commit.sh` copies `vendor/` (`ba14403`).
- `tests/Acceptance/HarnessPlacesAReadableVendorTest.php` runs the script itself against a
  synthetic repository whose `vendor/` holds a PSR-4 class, and fails if the copy becomes a link
  again. `HarnessResolvesAtCommitTreeTest` never ran the script — it pinned the idea, not the
  file — which is how the link survived four milestones.
- Nothing recorded was rewritten. The captures, packets, observations and write-ups of M20–M23 are
  as they were; each affected record carries an appended, dated erratum.
