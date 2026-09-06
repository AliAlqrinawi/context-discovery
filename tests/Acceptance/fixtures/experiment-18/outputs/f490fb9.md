# Context bundle

bundle_version 1 · budget 8000 / used 25 tokens

## fetched · same_file_reference

**Reason:** the region calls the sibling member apiKey, whose contract the diff does not show
**Source:** `tests/Feature/PublicApiTest.php` :: `apiKey` (lines 20-23)
**Tokens:** 25

```php
    private function apiKey(): string
    {
        return config('services.website_api_key');
    }
```

## Dropped

Nothing was dropped.
