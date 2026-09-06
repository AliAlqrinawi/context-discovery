# Experiment 08 · where is a member declared, and how far away

M8's minimal experiment. One question per class, and **no framework and no `vendor/` directory** —
the PHP-inheritance question is deliberately separated from the Laravel dynamic-dispatch question, so
neither can stand in for the other. It runs on a clean checkout.

| Class | Mechanism | Files from the changed one |
|---|---|---|
| `Direct` | declares `run()` and `slug()` | 1 |
| `OneLevel extends Direct` | inherited once | 2 |
| `TwoLevel extends OneLevel` | inherited twice | 3 |
| `WithTrait use Greeter` | trait on the class itself | 2 |
| `ViaParent extends Controllerish use Greeter` | **trait on the parent — the E5.4 shape** | 3 |
| `Dynamic extends BaseModel` | `__callStatic` — declared **nowhere** | n/a |

`Controllerish` is `abouelsid-backend`'s `App\Http\Controllers\Controller` reproduced in structure:
an abstract parent that declares nothing and applies a trait.

`diff.patch` adds one method to an existing file, so the new-file artefacts D1 and D2 stay out of the
measurement.

## Result

7 of 10 key rows correct. The three that are not:

| row | expected | actual | |
|---|---|---|---|
| X8.3 `OneLevel::run` | FETCH | FLAG | inherited once — one hop |
| X8.5 `WithTrait::greet` | FETCH | FLAG | trait on the class — one hop |
| X8.10 `$this->instanceGreet()` | FLAG | **absent** | silent omission; no assertion is formed |

And the rows that matter most for the decision, all **correct**: X8.6 (the E5.4 shape) flags at three
hops, X8.7 (dynamic dispatch) flags because no file declares it, and X8.8 / X8.9 keep flagging the
unknown member and the typo.

See [`docs/research/M8-depth-boundary-revisit.md`](../../../../docs/research/M8-depth-boundary-revisit.md)
and [ADR-A015](../../../../../context-discovery-architecture/architecture/decisions/ADR-A015-depth-boundary-trigger-evaluated-and-not-met.md).
