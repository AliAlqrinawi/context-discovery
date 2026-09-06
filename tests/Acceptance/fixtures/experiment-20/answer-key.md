# Experiment 20 · answer key

Written **before** any reviewer ran and before any packet was assembled. The machine-readable form
is `answer-key.json`; this is the same content, readable. Neither was changed after scoring.

## Selection criteria, fixed before selecting

| | |
|---|---|
| **S1** | a later **real** commit fixes a behaviour present in the target |
| **S2** | target ≤ 1000 changed lines and ≤ 35 files — reviewable in one sitting |
| **S3** | for controls: **no** later commit amends the behaviour |
| **S4** | the target produces a non-empty bundle under the corrected harness, else the conditions are identical |

Excluded by **S2**, stated in advance and independent of outcome: `8054003` (2339 lines, 71 files)
and `4c6a83b` (2375 lines, 55 files), each of which *does* have later real fixes.

## Ground-truth standard

| | |
|---|---|
| **STRONG** | a later real commit clearly fixes a specific behaviour in the target, and the message or diff names the mechanism |
| **MODERATE** | the later commit suggests the defect and fix, but interpretation remains |
| **WEAK** | inferred from inspection, with no later fix |

**Census: STRONG 4 · MODERATE 3 · WEAK 0** — 4 of 7 scored tasks, meeting the "at least half STRONG"
standard. 5 defect tasks, 2 controls.

## Tasks

| | Commit | Key | Strength | Fixed by | The defect |
|---|---|---|---|---|---|
| **D1** | `ec92403` | **YES** | STRONG | `04328a5`, `2c2a7b4` | **(a)** the file is written and the old one deleted *before* the DB write, with no transaction or cleanup → orphaned file and the old one already gone. **(b)** `permanent_url` returns `route('menu.pdf')`, the API domain, where the user-facing URL must be the frontend. **Either counts as detected.** |
| **D2** | `fc573d2` | **YES** | STRONG | `11c0ced` | Making descriptions nullable leaves the update path unable to express "clear this field": `UpdateDishAction`'s `Arr::whereNotNull` drops the null, so an emptied description keeps its old text. |
| **D3** | `e5e48ce` | **YES** | STRONG | `1fa1f66` | Public `DishController::index` passes raw `featured`/`signature` query strings through `$request->only([...])`; `"false"` is truthy, so `?featured=false` returns featured dishes. |
| **D4** | `ec76dc4` | **YES** | STRONG | `dae67ab` | `PageContentSeeder::run()` writes each row with `PageContent::create()`, so re-running against a seeded database fails on the `(page, section, key)` unique constraint. Not idempotent. |
| **D5** | `44726d0` | **YES** | MODERATE | `721ea3c` | Three different default page sizes ship together — `?? 12`, `15`, `15` — so page size depends on the endpoint. Keyed MODERATE, not STRONG: the fix names no failure, and an inconsistent default is partly a product decision. |
| **C1** | `4411454` | **NO** | MODERATE | — | Control. `$user->update()` mass-assigns name, email, password; **verified** that `User::$fillable` contains all three, and `updatePassword()` checks the current password with `Hash::check`. No later amendment. |
| **C2** | `e770086` | **NO** | MODERATE | — | Control. The optional `page` parameter is threaded coherently through controller, action, model scope and repository. No later amendment. |

## Predictions recorded in advance

| | |
|---|---|
| **D2** | the bundle **cannot** reach the defect — nothing in the changed regions names the update path (P4/X1). 2 flags, 40 tokens. A miss here is a prediction met. |
| **D1** | the bundle flags the surrounding-transaction premise, naming defect (a)'s mechanism; it cannot reach (b), a configuration fact (X3). |
| **C1** | M19 measured the bundle helping on this exact task. If the corrected harness reverses that, the correction matters more than the effect did. |

Two of seven say the bundle will not help. Written first, so the report cannot be assembled around
whichever tasks improved.
