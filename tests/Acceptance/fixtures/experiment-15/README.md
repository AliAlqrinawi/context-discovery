# Experiment 15 · does the origin of a reference change its worth?

M14 recorded an observation and refused to act on it: `User::create(...)` in a test file caused a
project-class surface that M7's answer key never asked for, and *"test files are scaffolding"* went
into `evidence-gaps.md` as an **unproven** discriminator with a key-first trigger. This is that key.

> **Does the file in which a named reference originates provide enough evidence to justify a
> different resolution or bundling outcome?**

## Origin categories — the repository's own, not invented

The tool already parses `composer.json` for PSR-4 and for `config.vendor-dir`. Both autoload
sections are read today and merged into one flat map, *"because a changed file may reference
either"*. The distinction the map discards is exactly the one under test, so this fixture declares
what the real corpus declares:

| Origin | Declared by | Category |
|---|---|---|
| `app/**` | `autoload.psr-4` `App\` | production |
| `database/seeders/**` | `autoload.psr-4` `Database\Seeders\` | **production — Composer says so** |
| `tests/**` | `autoload-dev.psr-4` `Tests\` | dev |
| `routes/**` | neither section | **unmapped** |

Testing the *declared* form matters: it is the strongest version of the discriminator. A rule that
cannot be justified from the project's own declaration cannot be justified from a hand-written list
of directory names either.

## Rows

| Row | Reference | Origin | The question it isolates |
|---|---|---|---|
| P1 | `OrderCalculator::total` | production | a resolving member, the settled case |
| T1a | `OrderCalculator::total` | dev | the **same subject** from a second origin |
| T1b | `OrderCalculator::subtotal` | dev | a resolving member reached **only** from a test |
| P2 | `Order::create` | production | ADR-A020's settled case |
| T2 | `Order::create` | dev | the same class, also used from production |
| **T3** | `AuditLog::create` | dev | **the deciding row** — the M7 `User` case, keyed at last |
| H1 | `Order::updateOrCreate` | dev (test *support*) | can "test case" and "test helper" be separated? |
| S1 | `Order::firstOrCreate` | production (a seeder) | GUARD — scaffolding-shaped, declared production |
| R1 | `Order::whereActive` | unmapped | GUARD — a binary rule has no answer here |
| V1 | `Coupon::create` | both | GUARD — ADR-A019 already withholds it |
| N1 | docblock / comment / string / `::class` | both | zero items, from both categories |

## Answer

**No.** Not one row's correct outcome differs because of the origin file, and the four candidate
boundaries all lose keyed context without removing a single keyed false positive.

| Boundary | flags | fetched | tokens | false negatives |
|---|---:|---:|---:|---:|
| **B0 · current** | 8 | 8 | 314 | **0** |
| B1 · drop dev references | 4 | 1 | 105 | 10 — including **P2, a production row** |
| B2 · no surface for dev origin | 8 | 6 | 288 | 2 |
| B3 · no flags or surfaces for dev | 4 | 6 | 208 | 6 |
| B4 · three-way by declaration | 8 | 6 | 288 | 2 — identical to B2; the third state buys nothing |

B1's failure is the sharpest result: `Order`'s surface reaches the bundle through a `: Order` return
type in a **test-support** file, so dropping dev-origin references destroys context a **production**
row demands. Origin-based suppression turns out to depend on which file happens to carry the
reference shape that resolves — which is not a property anyone would choose to depend on.

See [`docs/research/M15-origin-value.md`](../../../../docs/research/M15-origin-value.md), locked by
`tests/Acceptance/OriginDoesNotChangeOutcomeTest.php`. Re-run the simulation with:

```bash
php tests/Acceptance/fixtures/experiment-15/boundary-simulation.php \
  tests/Acceptance/fixtures/experiment-15/repo \
  tests/Acceptance/fixtures/experiment-15/diff.patch .
```
