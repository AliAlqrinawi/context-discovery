# Reviewer-facing redaction

Applied to `reviewer/` only. `researcher/all-packets/` keeps the originals unchanged.

| Rule | Reason |
|---|---|
| `index <blob>..<blob>` → `index 0000000..0000000` | git blob hashes are directly lookupable and would let the reviewer identify the commit |
| `abouelsid` → `examplecorp` | the string occurs only inside email addresses and one social handle — data, never logic. Substitution is uniform and changes no behaviour under review |

Nothing else was altered. No line of code, no path, no hunk header.
