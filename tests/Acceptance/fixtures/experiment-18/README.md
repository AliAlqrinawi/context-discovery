# Experiment 18 · bundle coverage across a whole repository

M17's scored run was inconclusive because 4 of its 5 commits produced an empty bundle. That was five
commits. This measures **every** commit in the same repository.

> Across a representative set of real commits, how often does the tool produce a non-empty bundle,
> how large are those bundles, and what decisions account for the empty ones?

## Selection — the whole population, so there is no selection

The corpus repository has **47 commits and no merges**. The rule is: *every commit reachable from
HEAD that has a parent* — all **46**, the complete population minus the root commit, which has no
diff to review. Nothing was sampled, filtered or chosen, so nothing could be chosen favourably.

Measured at CLI `b7623af` (M17) against corpus `450d91f`, both working trees clean.

## Files

| | |
|---|---|
| `empty-bundle-taxonomy.md` | **pre-registered** E0–E6 classification, written before the run |
| `run-corpus.php` | the runner; regenerates everything from the two repositories |
| `analyse.php` | aggregates `per-commit.json` into `aggregate.json` |
| `outputs/` | per commit: the diff, JSON, Markdown and stderr, exactly as produced |
| `analysis/per-commit.json` | the raw row for every commit |
| `analysis/aggregate.json` | distributions, empty-bundle reasons, shape table |

## Headline

| | |
|---|---|
| commits measured | **46** |
| **non-empty bundles** | **22 · 47.8%** |
| empty bundles | 24 · 52.2% |
| items — mean / median / max | 18.04 / **0** / 163 |
| tokens — mean / median / max | 789.8 / **0** / 7894 |
| non-empty only — median items / tokens | **18.5 / 881** |
| vendor items, whole corpus | **0** |

The distribution is **bimodal, not average**: 24 commits produce nothing and 16 produce 11+ items.
Reporting the mean alone (18 items) would describe no commit that exists.

## Why the empty ones are empty

| Primary | Commits | Meaning |
|---|---:|---|
| **E0** | **18** | no assertion produced — the tool never had a question |
| E3 | 5 | created-file suppression (ADR-A018) |
| E2 | 1 | dependency ownership (ADR-A012) |
| E1, E4, E5 | 0 | never the primary reason |

**E0 splits in two**, a distinction the corpus forced and the taxonomy permits:

- **8 commits contain no PHP file at all** — CI pipelines, Kubernetes manifests, Postman
  collections, deploy scripts. A PHP context tool cannot speak about them and should not.
- **10 commits are PHP that names no class** on any added line — settings seeds, a middleware
  tweak, a query-param cast, config-string moves.

So of 46 commits: 22 produce context, 6 are silent by a decision with an ADR behind it, 8 are
outside the tool's subject matter, and **10 are the real coverage gap**.

See [`docs/research/M18-coverage-distribution.md`](../../../../docs/research/M18-coverage-distribution.md).
