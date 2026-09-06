# Experiment 14 · does the unresolved-member flag survive the surface?

M13 established boundary **B4**: when a `Fqcn::member` reference cannot be resolved, fetch the
class's surface — if the class is placeable, the path is project source, and nothing accounts for
the member. It left one question open, and this fixture settles it.

> When B4 fires, does the existing unresolved-member flag **remain** alongside the fetched surface,
> or is it **replaced** by it?

## Files

| File | What it is |
|---|---|
| `answer-key.json` | **written before the tool was run** (ADR-001), rows A–J plus X |
| `diff.patch` | one changed file, `app/Consumer.php` |
| `repo/` | a controlled repository: three models, an ordinary service, an absent contract, two hand-written framework stubs |

`repo/vendor/` holds two stub files only — a `Facade` base class and a `Log` facade carrying one
`@method static` tag. They exist so the *ownership* condition and M3's facade rule can be exercised
without installing Laravel; nothing reads more of them than the tag.

## The row that decides it

**H · `Package::activatte`** — a typo. It satisfies all three of B4's conditions *identically* to
`Package::create`, and [ADR-A016](../../../../../context-discovery-architecture/architecture/decisions/ADR-A016-oq6-the-available-facts-cannot-classify.md)
established that no fact available to the tool separates them. Under *replace* it would silently
become "here is the model surface" — M0's risk R1, and the silence P10 forbids.

**Answer: RETAIN.** See [ADR-A020](../../../../../context-discovery-architecture/architecture/decisions/ADR-A020-the-project-class-surface-fallback.md).

## Row I, the duplicate guard

`Invoice` is referenced twice and two different ways — as a `?Invoice` return type and through
`Invoice::create`. Both ask for the same surface; the bundle must contain it once. Nothing before
M14 could exercise this.

## Result

16 items · 298 tokens · 0 dropped · 8 diagnostics · **9 of 9 keyed rows**. Byte-identical across
five runs. Asserted by `tests/Acceptance/ProjectSurfaceFallbackAcceptanceTest.php`; analysed in
[`docs/research/M14-model-surface-fallback.md`](../../../../docs/research/M14-model-surface-fallback.md).
