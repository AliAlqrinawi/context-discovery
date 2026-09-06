# Context bundle

bundle_version 1 · budget 8000 / used 221 tokens

## fetched · same_file_symbol_absence

**Reason:** the region uses var_export, which the file's use block does not import
**Source:** `app/Actions/Dish/GetDishesAction.php` (lines 5-7)
**Tokens:** 29

```php
use App\Repositories\DishRepository;
use App\Services\CacheGroup;
use Illuminate\Pagination\LengthAwarePaginator;
```

## fetched · same_file_symbol_absence

**Reason:** the region uses var_export, which the file's use block does not import
**Source:** `app/Actions/Dish/GetDishesAction.php` :: `execute` (lines 15-32)
**Tokens:** 192

```php
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $locale = app()->getLocale();
        $categoryId = $filters['category_id'] ?? 'all';
        $featured = array_key_exists('featured', $filters) ? var_export($filters['featured'], true) : 'all';
        $signature = array_key_exists('signature', $filters) ? var_export($filters['signature'], true) : 'all';
        $perPage = $filters['per_page'] ?? 'default';
        $page = request()->integer('page', 1);

        $key = "dishes_{$locale}_{$categoryId}_{$featured}_{$signature}_{$perPage}_{$page}";

        return CacheGroup::remember(
            'dishes',
            $key,
            now()->addHour(),
            fn () => $this->repository->getAll($filters)
        );
    }
```

## Dropped

Nothing was dropped.
