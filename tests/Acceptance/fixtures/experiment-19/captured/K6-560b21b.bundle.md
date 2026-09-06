# Context bundle

bundle_version 1 · budget 8000 / used 848 tokens

## fetched · named_reference

**Reason:** the region depends on App\DTOs\Dish\UpdateDishDTO, whose contract is defined in another file
**Source:** `app/DTOs/Dish/UpdateDishDTO.php` :: `CLEARABLE` (lines 25-26)
**Tokens:** 36

```php
    /** Nullable fields listed here can be cleared by sending them empty. */
    public const CLEARABLE = ['description_ar', 'description_en'];
```

## fetched · named_reference

**Reason:** the region depends on App\DTOs\Dish\UpdateDishDTO, whose contract is defined in another file
**Source:** `app/DTOs/Dish/UpdateDishDTO.php` :: `__construct` (lines 10-23)
**Tokens:** 157

```php
    public function __construct(
        public readonly ?string $name_ar,
        public readonly ?string $name_en,
        public readonly ?string $description_ar,
        public readonly ?string $description_en,
        public readonly ?float $price,
        public readonly ?int $category_id,
        public readonly ?bool $is_featured,
        public readonly ?bool $is_signature,
        public readonly ?UploadedFile $image,
        public readonly ?int $order,
        /** @var list<string> Nullable keys the request actually sent, so they can be cleared. */
        public readonly array $providedKeys = [],
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\DTOs\Dish\UpdateDishDTO, whose contract is defined in another file
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

**Reason:** the region depends on App\DTOs\Dish\UpdateDishDTO, whose contract is defined in another file
**Source:** `app/DTOs/Dish/UpdateDishDTO.php` :: `wasProvided` (lines 48-51)
**Tokens:** 30

```php
    public function wasProvided(string $key): bool
    {
        return in_array($key, $this->providedKeys, true);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\DTOs\Setting\UpdateSettingDTO, whose contract is defined in another file
**Source:** `app/DTOs/Setting/UpdateSettingDTO.php` :: `__construct` (lines 9-11)
**Tokens:** 21

```php
    public function __construct(
        public readonly array $settings,
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\DTOs\Setting\UpdateSettingDTO, whose contract is defined in another file
**Source:** `app/DTOs/Setting/UpdateSettingDTO.php` :: `fromRequest` (lines 13-18)
**Tokens:** 38

```php
    public static function fromRequest(Request $request): self
    {
        return new self(
            settings: $request->all(),
        );
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category::where, whose contract is defined in another file
**Source:** `app/Models/Category.php` :: `dishes` (lines 17-20)
**Tokens:** 24

```php
    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category::where, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\Dish::first, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\Dish::first, whose contract is defined in another file
**Source:** `app/Models/Dish.php` :: `category` (lines 30-33)
**Tokens:** 26

```php
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Dish::first, whose contract is defined in another file
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

**Reason:** the region depends on Closure, whose contract is defined in another file
**Source:** `app/Services/CacheGroup.php` :: `Closure` (lines 1-67)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on DateTimeInterface, whose contract is defined in another file
**Source:** `app/Services/CacheGroup.php` :: `DateTimeInterface` (lines 1-67)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Category::where, whose contract is defined in another file
**Source:** `tests/Feature/Actions/CacheGroupTest.php` :: `App\Models\Category::where` (lines 1-102)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Dish::first, whose contract is defined in another file
**Source:** `tests/Feature/Actions/CacheGroupTest.php` :: `App\Models\Dish::first` (lines 1-102)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## Dropped

Nothing was dropped.
