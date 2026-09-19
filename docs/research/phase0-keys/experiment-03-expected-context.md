# Experiment 03 · Plaid Item lifecycle merge

Transcribed from `docs/01-phase0/experiment-03.md` step 4 and `06-acceptance.md` §1.

Role in the set: the **config- and schema-dependent recall test** — the commit that broke the
assumption that relevant context is application code only. Its two strongest findings are flag-type:
env and data-state facts no file settles.

## Operator inputs

    repo: <operator-set>
    budget: <operator-set>

## Expectations

X3 defers a dedicated config/migration resolver — Experiment 3 is n=1 — so the config and schema
questions are handled through the generic flag path and **only** the flag path. `fetch-if-named`
transcribes the contract's own conditional wording ("only if named in the diff"); such a row is
reported, never required.

| mark | assertion_kind | expect | from the research |
|---|---|---|---|
| flag-satisfied | unverifiable_premise | atomic-lock-store | "`config/cache.php` … settles the lock-backend question" — the *deployed* store is a runtime fact, so the residual is flagged |
| flag-satisfied | unverifiable_premise | schema-index-support | "The `plaid_items` migration(s) — settles … the index question" |
| flag-satisfied | unverifiable_premise | data-state-after-behaviour-change | "…and the SoftDeletes / `deleted_at` data hazard"; whether production holds trashed rows is a data-state fact |
| fetch-if-named | named_reference | rules | "`ExchangePublicTokenRequest` — one FormRequest", fetch-type only if the diff names it |
| fetch-if-named | named_reference | api | "`routes/api.php` (the Plaid group only)", fetch-type only if the diff names it |

## Checks

- The three flag texts are present and attributed to their origin file.
- **No dedicated config resolver is invoked**: nothing is fetched from `config/` or from a
  migrations directory. X3 is enforced by the absence of the module, and this is its observable form.
