# Experiment 11 · a created file is input, not context

One diff, three file shapes, so new-file handling can be compared against unchanged modified-file
handling in the same run:

| file | shape | what it tests |
|---|---|---|
| `app/Services/NewService.php` | **created** | G3 — its own content must not be fetched; D1 — its `<?php`; lexical noise in a new file |
| `app/Support/Existing.php` | **modified** | the own-file move must survive untouched |
| `config/thing.yml` | **created, not PHP** | no PHP symbol extraction |

## Result

| | before | after |
|---|---:|---:|
| items | 8 | **5** |
| fetched | 6 | **3** |
| tokens | 186 | **97** |
| items whose provenance is the created file | 5 (129 tok, 69 %) | **0** |
| items reasoned *"the region uses php"* | 2 | **0** |
| assertions from `config/thing.yml` | 2 diagnostics | **0** |

What survived, correctly: the `App\Models\Package` surface (external, off-diff), the
`Existing::helper` sibling (modified file), both Eloquent flags, and `Log::info` settled as
framework-known.

`observed-*-before.txt/json` and `-after.*` are the captured runs. See
[`docs/research/M11-new-file-duplication.md`](../../../../docs/research/M11-new-file-duplication.md)
and [ADR-A018](../../../../../context-discovery-architecture/architecture/decisions/ADR-A018-a-created-file-is-input-not-context.md).
