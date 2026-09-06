# Context bundle

bundle_version 1 · budget 8000 / used 888 tokens

## fetched · same_file_symbol_absence

**Reason:** the region uses array_values, which the file's use block does not import
**Source:** `app/DTOs/Dish/UpdateDishDTO.php` (lines 5-6)
**Tokens:** 16

```php
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
```

## fetched · same_file_symbol_absence

**Reason:** the region uses array_values, which the file's use block does not import
**Source:** `app/DTOs/Dish/UpdateDishDTO.php` :: `fromRequest` (lines 28-46)
**Tokens:** 307

```php
    public static function fromRequest(Request $request): self
    {
        return new self(
            name_ar: $request->filled('name_ar') ? $request->string('name_ar')->toString() : null,
            name_en: $request->filled('name_en') ? $request->string('name_en')->toString() : null,
            description_ar: $request->filled('description_ar') ? $request->string('description_ar')->toString() : null,
            description_en: $request->filled('description_en') ? $request->string('description_en')->toString() : null,
            price: $request->filled('price') ? (float) $request->input('price') : null,
            category_id: $request->filled('category_id') ? (int) $request->input('category_id') : null,
            is_featured: $request->has('is_featured') ? $request->boolean('is_featured') : null,
            is_signature: $request->has('is_signature') ? $request->boolean('is_signature') : null,
            image: $request->file('image'),
            order: $request->filled('order') ? (int) $request->input('order') : null,
            providedKeys: array_values(array_filter(
                self::CLEARABLE,
                fn (string $key) => $request->has($key),
            )),
        );
    }
```

## fetched · named_reference

**Reason:** the region depends on App\DTOs\Dish\UpdateDishDTO::fromRequest, whose contract is defined in another file
**Source:** `app/DTOs/Dish/UpdateDishDTO.php` :: `fromRequest` (lines 28-46)
**Tokens:** 307

```php
    public static function fromRequest(Request $request): self
    {
        return new self(
            name_ar: $request->filled('name_ar') ? $request->string('name_ar')->toString() : null,
            name_en: $request->filled('name_en') ? $request->string('name_en')->toString() : null,
            description_ar: $request->filled('description_ar') ? $request->string('description_ar')->toString() : null,
            description_en: $request->filled('description_en') ? $request->string('description_en')->toString() : null,
            price: $request->filled('price') ? (float) $request->input('price') : null,
            category_id: $request->filled('category_id') ? (int) $request->input('category_id') : null,
            is_featured: $request->has('is_featured') ? $request->boolean('is_featured') : null,
            is_signature: $request->has('is_signature') ? $request->boolean('is_signature') : null,
            image: $request->file('image'),
            order: $request->filled('order') ? (int) $request->input('order') : null,
            providedKeys: array_values(array_filter(
                self::CLEARABLE,
                fn (string $key) => $request->has($key),
            )),
        );
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category::create, whose contract is defined in another file
**Source:** `app/Models/Category.php` :: `dishes` (lines 17-20)
**Tokens:** 24

```php
    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category::create, whose contract is defined in another file
**Source:** `app/Models/Category.php` :: `fillable` (lines 10-15)
**Tokens:** 27

```php
    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'order',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Dish, whose contract is defined in another file
**Source:** `app/Models/Dish.php` :: `casts` (lines 24-28)
**Tokens:** 34

```php
    protected $casts = [
        'price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_signature' => 'boolean',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Dish, whose contract is defined in another file
**Source:** `app/Models/Dish.php` :: `category` (lines 30-33)
**Tokens:** 26

```php
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Dish, whose contract is defined in another file
**Source:** `app/Models/Dish.php` :: `fillable` (lines 10-22)
**Tokens:** 68

```php
    protected $fillable = [
        'category_id',
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'price',
        'is_featured',
        'is_signature',
        'image_path',
        'image_url',
        'order',
    ];
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Category::create, whose contract is defined in another file
**Source:** `tests/Feature/Actions/UpdateDishActionTest.php` :: `App\Models\Category::create` (lines 1-80)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Dish::create, whose contract is defined in another file
**Source:** `tests/Feature/Actions/UpdateDishActionTest.php` :: `App\Models\Dish::create` (lines 1-80)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\Request::create, whose contract is defined in another file
**Source:** `tests/Feature/Actions/UpdateDishActionTest.php` :: `Illuminate\Http\Request::create` (lines 1-80)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `tests/Feature/Actions/UpdateDishActionTest.php` (lines 1-80)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## Dropped

Nothing was dropped.
