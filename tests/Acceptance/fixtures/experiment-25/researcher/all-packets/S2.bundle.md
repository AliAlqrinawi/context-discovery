# Context bundle

bundle_version 1 · budget 8000 / used 40 tokens

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Schema\Blueprint, whose contract is defined in another file
**Source:** `database/migrations/2026_08_11_091844_make_dish_descriptions_nullable.php` :: `Illuminate\Database\Schema\Blueprint` (lines 1-30)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Schema::table, whose contract is defined in another file
**Source:** `database/migrations/2026_08_11_091844_make_dish_descriptions_nullable.php` :: `Illuminate\Support\Facades\Schema::table` (lines 1-30)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## Dropped

Nothing was dropped.
