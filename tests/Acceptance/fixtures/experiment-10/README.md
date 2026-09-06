# Experiment 10 · does the diagnostic name the state that happened?

Three states produce "cannot settle this reference", and `pathFor()` collapses two of them into one
`null`:

| | State | Message before | Message after |
|---|---|---|---|
| **A** | no PSR-4 prefix covers the name | `missing PSR-4 entry` ✓ | unchanged |
| **B** | a prefix covers it, no file is there | `missing PSR-4 entry` ✗ **false** | `class file not found` |
| **C** | file present, member absent | `unresolved named_reference` ✓ | unchanged |

The fixture declares two project prefixes (`App\`, `Acme\Lib\`) and one framework prefix, so each
state can be produced deliberately. `diff.patch` adds one method to an existing file.

`observed-diagnostics-before.txt` and `-after.txt` are the captured runs; `observed-bundle.json` is
byte-identical between them — only stderr moved.

Three of the four `missing PSR-4 entry` lines were false. See
[`docs/research/M10-diagnostic-truthfulness.md`](../../../../docs/research/M10-diagnostic-truthfulness.md)
and [ADR-A017](../../../../../context-discovery-architecture/architecture/decisions/ADR-A017-diagnostics-name-the-state-they-observed.md).
