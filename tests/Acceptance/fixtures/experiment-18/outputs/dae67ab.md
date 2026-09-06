# Context bundle

bundle_version 1 · budget 8000 / used 126 tokens

## fetched · named_reference

**Reason:** the region depends on App\Models\PageContent::updateOrCreate, whose contract is defined in another file
**Source:** `app/Models/PageContent.php` :: `fillable` (lines 10-18)
**Tokens:** 40

```php
    protected $fillable = [
        'page',
        'section',
        'key',
        'value_ar',
        'value_en',
        'type',
        'order',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\PageContent::updateOrCreate, whose contract is defined in another file
**Source:** `app/Models/PageContent.php` :: `scopeForPage` (lines 20-29)
**Tokens:** 66

```php
    public function scopeForPage(Builder $query, string $page, ?string $section = null): Builder
    {
        $query->where('page', $page);

        if ($section !== null) {
            $query->where('section', $section);
        }

        return $query;
    }
```

## flagged · named_reference

**Reason:** the region depends on App\Models\PageContent::updateOrCreate, whose contract is defined in another file
**Source:** `database/seeders/PageContentSeeder.php` :: `App\Models\PageContent::updateOrCreate` (lines 117-126)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## Dropped

Nothing was dropped.
