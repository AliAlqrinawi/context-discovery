# Golden bundles

One recorded output per contract version, **with its input beside it**, so a shape change shows up
as a reviewable diff rather than as a surprise in someone's integration — and so the reproduction
can be re-run from this directory alone.

| File | What it is |
|---|---|
| `m26.diff` | The input: the M26 reproduction diff — `BranchRepository::getAll` changing `get()` to `first()`, plus the two files changed alongside it in the same pull request. Three hunks. Taken from a real Laravel 12 repository (`abouelsid-backend`). |
| `m26-bundle.v2.md` | The same run's output under `--format markdown`, generated from the same inputs in the same session as the JSON (2026-09-26). The reader's rendering of the JSON; it carries the same 23 items in the same order and is what a reviewer packet contains. The backend's `MarkdownConformance` (ADR-B003) is tested against this pair: the two files must say the same thing. |
| `m26-bundle.v2.json` | The output, as `bundle_version` 2 emits it under `policy_version` 3: 3 assertions, 23 items, 1 diagnostic, 624 tokens. Under `policy_version` 2 (v0.2.0) it was 2 assertions, 22 items, 562 tokens; the third assertion is the diff's `$this->success(` — §3.3's fourth form (ADR-A029) — and its item is the S1 statement citing `app/Traits/ApiResponse.php:9` (ADR-A028), 62 tokens. |

`BundleSchemaConformanceTest` validates the bundle against `schema/bundle-v2.schema.json` on every
run and asserts the recorded counts.

**Re-running it** needs the repository the diff was cut from, at its post-image state
(`03-interfaces.md` §1) — it is not in this repository. When it is available:

    ./bin/context-discover --diff tests/Acceptance/fixtures/golden/m26.diff \
                           --repo <abouelsid-backend, post-image> --budget 8000 --format json

must reproduce `m26-bundle.v2.json` byte for byte. Regenerating the fixture is a deliberate act: say
in the commit message which contract change made it necessary.

**v1 bundles are not kept here.** The ~90 recorded under `../experiment-*/` are frozen research
artefacts and are never regenerated — see ADR-A024 on why comparing across the version boundary
means re-running, never translating.
