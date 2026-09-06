# Context bundle

bundle_version 1 · budget 8000 / used 40 tokens

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Schema\Blueprint, whose contract is defined in another file
**Source:** `database/migrations/2024_11_18_122542_add_location_to_products.php` :: `Illuminate\Database\Schema\Blueprint` (lines 1-28)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Schema::table, whose contract is defined in another file
**Source:** `database/migrations/2024_11_18_122542_add_location_to_products.php` :: `Illuminate\Support\Facades\Schema::table` (lines 1-28)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## Dropped

Nothing was dropped.
