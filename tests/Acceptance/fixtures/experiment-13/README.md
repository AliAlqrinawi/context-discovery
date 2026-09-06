# Experiment 13 · does `Model::method()` earn the model surface?

M7's **G1**: on a real Laravel pull request the model surface never fired, because Laravel code writes
`Setting::updateOrCreate(...)` and never `new Setting` or `: Setting`.

One changed file references six others, arranged so that *"reached by a static call"* can be
separated from *"is a model"*, *"is project code"* and *"the member exists"*:

| reference | case | why it is here |
|---|---|---|
| `Package::where/create/query`, `Setting::updateOrCreate` | A, B, C | models reached **only** by static calls |
| `new Widget()`, `Widget $w`, `?Widget` | D | a model in the forms the extractor already accepts |
| `Registry::create(...)` | **E** | a non-model project class declaring Eloquent's most recognisable member name |
| `MissingGateway::resolve(...)` | **F** | an unknown class |
| `Log::info(...)` | G | a framework facade |
| docblock, `@see`, comment, strings, `Package::class` | H | lexical noise |

E and F are the trap rows: extracting a surface for every `Class::method()` breaks both.

## Files

`boundary-simulation.php` reads the real components and reports what each candidate boundary *would*
produce, changing none of them. It is an experiment artifact — nothing here is wired into the CLI.

```
php tests/Acceptance/fixtures/experiment-13/boundary-simulation.php <repo> <diff> <cli-root>
```

## Result

**No production code was changed.** The baseline reproduces G1 exactly: `Widget`'s surface is fetched,
`Package`'s and `Setting`'s are not. Of four candidate boundaries only one satisfies the key —
*placeable **and** project source **and** the referenced member does not resolve in the class's own
file* — and it is a **resolution-time** rule, not an extraction move.

See [`docs/research/M13-model-surface-extraction.md`](../../../../docs/research/M13-model-surface-extraction.md).
