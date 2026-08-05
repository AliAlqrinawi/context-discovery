# context-discovery

One deterministic command — **diff in, context bundle out** — that performs only the discovery
moves Phase 0 proved, attaches a *reason* and a *lever* to every item, and enforces a token budget
with a visible drop list.

**It does not review, judge, score, or call a model.** The reviewer is a separate, downstream,
human step. Assembling the context is the whole job.

## Status

**Milestone M0 complete** — repository skeleton and toolchain only. No discovery logic exists yet;
`bin/context-discover` prints its contract and exits `1`. See the engineering plan's milestone list
for what lands next (M1 domain types and ports, M2 diff parsing, …).

## Usage (the contract, once M5 lands)

```
context-discover --diff <path|-> --repo <path> --budget <int>
                 [--format json|markdown]
                 [--caller-scope <prefix>] [--max-call-sites <int>]
```

`--budget` is required and has no default: the research fixes no absolute number, and inventing one
would hide an untested assumption in the code (ADR-A008).

The bundle goes to stdout; diagnostics go to stderr. Exit codes: `0` bundle produced (including an
empty bundle) · `1` usage or input error · `2` repository unreadable.

## Install

```
composer install
```

There are **zero runtime dependencies**. Composer is used for autoloading and for a dev-only test
runner (PHPUnit). Requires PHP 8.2 or later.

## Tests

```
composer test
```

## The three repositories

| Repository | Role |
|---|---|
| [context-discovery-research](https://github.com/AliAlqrinawi/context-discovery-research) | Source of truth. The Phase 0 validation, the discovery moves, the Phase 1 spec |
| [context-discovery-architecture](https://github.com/AliAlqrinawi/context-discovery-architecture) | The frozen implementation contract |
| this repository | The implementation |

A copy of the frozen contract lives in [`docs/architecture/`](docs/architecture/) for offline
reading. **The origin repository is authoritative** — if the copy and the origin disagree, the copy
is stale.

## What this tool will never do

No forward import-following (disproven). No traversal beyond depth one. No maintained index or call
graph — reverse-caller is a grep. No dedicated config/migration resolver — those go through the flag
path. No severity, score, or ranking. No LLM call. No network, no subprocess, no state between runs.

Each of those is an exclusion the research earned, not a simplification. See
[`docs/architecture/05-traceability.md`](docs/architecture/05-traceability.md) §5.
