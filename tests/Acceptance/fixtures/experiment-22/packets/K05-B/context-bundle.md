# Context bundle

bundle_version 1 · budget 8000 / used 449 tokens

## fetched · same_file_symbol_absence

**Reason:** the region uses var_export, which the file's use block does not import
**Source:** `app/Actions/Dish/GetDishesAction.php` (lines 5-7)
**Tokens:** 31

```php
use App\Repositories\DishRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
```

## fetched · same_file_symbol_absence

**Reason:** the region uses var_export, which the file's use block does not import
**Source:** `app/Actions/Dish/GetDishesAction.php` :: `execute` (lines 15-31)
**Tokens:** 190

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

        return Cache::tags(['dishes'])->remember(
            $key,
            now()->addHour(),
            fn () => $this->repository->getAll($filters)
        );
    }
```

## flagged · named_reference

**Reason:** the region depends on App\Http\Resources\Dish\DishResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/DishController.php` :: `App\Http\Resources\Dish\DishResource::collection` (lines 28-34)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\Dish\DishResource::collection, whose contract is defined in another file
**Source:** `app/Http/Resources/Dish/DishResource.php` :: `toArray` (lines 14-32)
**Tokens:** 208

```php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->resolveLocale($this->name_ar, $this->name_en),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'description' => $this->resolveLocale($this->description_ar, $this->description_en),
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'price' => $this->price,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'is_featured' => $this->is_featured,
            'is_signature' => $this->is_signature,
            'image_url' => $this->image_url,
            'order' => $this->order,
            'locale' => app()->getLocale(),
        ];
    }
```

## Dropped

Nothing was dropped.
