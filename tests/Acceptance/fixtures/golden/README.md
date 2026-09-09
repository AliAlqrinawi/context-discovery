# Golden bundles

One recorded output per contract version, so a shape change shows up as a reviewable diff rather
than as a surprise in someone's integration.

| File | What it records |
|---|---|
| `m26-bundle.v2.json` | The M26 reproduction — `BranchRepository::getAll` changing `get()` to `first()` — as `bundle_version` 2 emits it: 2 assertions, 22 items, 1 diagnostic, 562 tokens. |

`BundleSchemaConformanceTest` validates this file against `schema/bundle-v2.schema.json` on every
run, and asserts the recorded counts. Regenerating it is a deliberate act: run the reproduction,
overwrite the file, and say in the commit message which contract change made it necessary.

**v1 bundles are not kept here.** The ~90 recorded under `../experiment-*/` are frozen research
artefacts and are never regenerated — see ADR-A024 on why comparing across the version boundary
means re-running, never translating.
