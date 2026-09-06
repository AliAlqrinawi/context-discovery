# Experiment 16 · when are two bundle items the same item?

M15 found, while measuring something else, that one member named from two files arrives in the
bundle **twice** — two items identical in every field, 50 tokens for one fact — and recorded it as
**G4** with a key-first trigger rather than fixing it inside another milestone. This is that key.

> Does one identical slice per `(path, member)` always provide the correct reviewer context, or can
> multiple origins of the same member justify multiple copies?

## The fact the experiment turns on

The two levers put different things in `provenance`:

| Lever | `provenance` names | Two origins give |
|---|---|---|
| **flagged** | the **requesting** site | two different items, two lines to visit |
| **fetched** | the **declaring** site | two identical items |

So a fetched item's origin is **not in the bundle at all** — not in the copy, and not in the first
one either. Keeping the duplicate cannot preserve origin; it prints the same unlabelled bytes twice.

## Rows

| Row | Case | Keyed |
|---|---|---|
| R1 | same path + member, two **production** files | 1 item |
| R2 | same path + member, production and **test** | still 1 — and *not* because a test is worth less (M15 C) |
| R3 | same path + member, twice in **one** file | no additional item |
| R4 | same path, **different members** | 2 — kills any path-only rule |
| R5 | **different paths**, same member name | 2 — kills any name-only rule |
| **R6** | same path, member **and span**, two resolution routes | 2 — **the deciding guard** |
| R7 | byte-identical **text**, different classes | 2 — kills any payload-only rule |
| R8 | same path, **different spans** | 2 |
| R9 | a **framework-known** reference named twice | 0 items, 2 diagnostics, no vendor source |
| R10 | M1/M7 shape — a model named from two files | 2 flags + 1 surface |

**R6 turned out richer than keyed.** `app/Http/ControllerA.php::helper`, lines 18–21, arrives
**three** times with identical path, member, span and text — as `same_file_symbol_absence`, as
`same_file_reference`, and as a `named_reference` from another file. Three questions, three reasons,
and **two different `ItemPriority` bands**. Collapsing them would change what survives a budget.

## Measurements

Budget 8000. Baseline: **15 items, 328 tokens, 2 false positives** (the redundant `Calc::total`
copies), 0 false negatives.

| | Identity | items | fetched | flags | tokens | rows broken |
|---|---|---:|---:|---:|---:|---|
| B0 | none (current) | 15 | 13 | 2 | 328 | — |
| B1 | `(path, member, span)` | 11 | 9 | 2 | 240 | R6 |
| B2 | payload text | 9 | 8 | **1** | 197 | R6, R7, **R10** |
| B3 | slice, provenances merged | 11 | 9 | 2 | 240 | R6 |
| **B4** | **the whole visible item** | **13** | **11** | **2** | **278** | **—** |
| B5 | provenance only | 11 | 9 | 2 | 240 | R6 |

**B2 is the cautionary one:** deduplicating on payload collapses a *flag*, because both
`Order::create` assumption statements are the same sentence. Cheapest boundary, worst outcome.

## Two fixture corrections, recorded

Both were made **after** the key was written and **without** changing any keyed expectation — the
rows described cases the first fixture did not actually produce.

1. **R9** did not place the framework, so `Log::info` flagged as unplaceable instead of settling as
   framework-known. Fixed by adding `vendor/composer/autoload_psr4.php`, as a real installed app has.
2. **R6** put `helper()` inside the diff, so ADR-A019 withheld the named-reference copy and the
   cross-route duplicate never appeared. Fixed by making `helper()` pre-existing. `ControllerB` also
   moved to its own namespace, because a same-namespace name is an *absence*, not a reference.

## Result

**Implemented** as [ADR-A021](../../../../../context-discovery-architecture/architecture/decisions/ADR-A021-item-identity-is-the-whole-visible-item.md):
15 → 13 items, 328 → 278 tokens, 0 duplicates, every guard row intact. Analysed in
[`docs/research/M16-duplicate-slice-identity.md`](../../../../docs/research/M16-duplicate-slice-identity.md).

```bash
php tests/Acceptance/fixtures/experiment-16/boundary-simulation.php <a bundle.json>
```
