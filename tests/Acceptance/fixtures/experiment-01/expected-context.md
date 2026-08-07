# Experiment 01 · `syncFromResponse` reconciliation change

Transcribed from `docs/01-phase0/experiment-01.md` step 4 and `06-acceptance.md` §1. Nothing here is
invented: every row names something the research already asked for.

Role in the set: **primary positive signal** — a recall test. Its four headline findings are all
context-dependent, and each needs a different discovery move.

## Operator inputs

The research repository contains no patch text; these commits live in a private Laravel + Plaid
codebase. Both fields must be filled in before the acceptance test can run.

    repo: <operator-set>
    budget: <operator-set>

`budget` has no default here for the same reason `--budget` has none in the tool: the research fixes
no absolute number, only that the bundle must cost dramatically less than the whole repository
(ADR-A008). The operator sets the number this run is measured against.

## Expectations

`fetch-expected` — the tool must fetch it. `flag-satisfied` — the research's minimum-context list
names a source the tool is not permitted to fetch, and the implementation spec counts a flag as a
catch for it.

| mark | assertion_kind | expect | from the research |
|---|---|---|---|
| fetch-expected | same_file_symbol_absence | use-block | "The `use` block … from `PlaidAccountService.php` — resolves the `Log` import" |
| fetch-expected | same_file_symbol_absence | syncFromResponse | "…plus the full `syncFromResponse` body … shows whether a transaction is opened locally" |
| fetch-expected | same_file_reference | upsertFromPlaid | "The `upsertFromPlaid` method from the same repository file — resolves the immutability contract" |
| fetch-expected | named_reference | forItem | "The `PlaidAccount` model — resolves `forItem`, `official_name`, fillable/casts" |
| flag-satisfied | unverifiable_premise | surrounding-transaction | "The single caller of `syncFromResponse` — resolves the transaction question definitively." R3 restricts the caller grep to changed signatures, and `fetch-vs-flag.md` names this the canonical flag case |

## Checks

- Recall of all four findings.
- The missing-import assertion is present.
- The transaction item carries `lever: flagged`.
- **No caller grep was performed** — no `changed_signature` item, no caller-search diagnostic.
