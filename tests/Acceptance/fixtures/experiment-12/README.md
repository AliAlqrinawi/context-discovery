# Experiment 12 · when is the diff enough to replace repository context?

One changed file (`app/Consumer.php`) references six others, each in a different evidence state:

| referenced file | state | in the diff? | declaration visible? | expected |
|---|---|---|---|---|
| `NewService.php` | **C** | created | yes — every line | do not fetch |
| `VisibleService.php` | **A** | modified, hunk 11-16 | yes — `run()` at line 12 | do not fetch |
| `HiddenService.php` | **B** | modified, hunk 26-31 | **no** — `run()` at line 7 | **FETCH** |
| `Noisy.php` | **C** | created | yes | do not fetch; and no items from its noise |
| `Untouched.php` | **E** | not in the diff | — | **FETCH** |

`HiddenService` is the row that matters. A rule keyed on *"the path appears in the diff"* would
suppress it and delete context the reviewer never saw — the false negative P10 rates worst.

## Result

| | before | after |
|---|---:|---:|
| items | 7 | **2** |
| fetched | 7 | **2** |
| tokens | 194 | **43** |

The two survivors are exactly `HiddenService::run` (off-hunk) and `Untouched::go` (not in the diff).
Four suppressions are reported on stderr.

See [`docs/research/M12-cross-file-duplication.md`](../../../../docs/research/M12-cross-file-duplication.md)
and [ADR-A019](../../../../../context-discovery-architecture/architecture/decisions/ADR-A019-context-the-diff-already-shows-is-not-fetched.md).
