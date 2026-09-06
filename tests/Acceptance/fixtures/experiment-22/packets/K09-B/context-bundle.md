# Context bundle

bundle_version 1 · budget 8000 / used 2115 tokens

## fetched · named_reference

**Reason:** the region depends on App\DTOs\Catering\CreatePackageDTO, whose contract is defined in another file
**Source:** `app/DTOs/Catering/CreatePackageDTO.php` :: `__construct` (lines 10-21)
**Tokens:** 120

```php
    public function __construct(
        public readonly string $name_ar,
        public readonly string $name_en,
        public readonly string $description_ar,
        public readonly string $description_en,
        public readonly ?string $tag_en,
        public readonly ?float $price_starting_from,
        public readonly bool $is_featured,
        public readonly ?UploadedFile $image,
        public readonly int $order,
        public readonly array $features,
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\DTOs\Catering\CreatePackageDTO, whose contract is defined in another file
**Source:** `app/DTOs/Catering/CreatePackageDTO.php` :: `fromRequest` (lines 23-37)
**Tokens:** 206

```php
    public static function fromRequest(Request $request): self
    {
        return new self(
            name_ar: $request->string('name_ar')->toString(),
            name_en: $request->string('name_en')->toString(),
            description_ar: $request->string('description_ar')->toString(),
            description_en: $request->string('description_en')->toString(),
            tag_en: $request->filled('tag_en') ? $request->string('tag_en')->toString() : null,
            price_starting_from: $request->filled('price_starting_from') ? (float) $request->input('price_starting_from') : null,
            is_featured: $request->boolean('is_featured'),
            image: $request->file('image'),
            order: (int) $request->input('order', 0),
            features: $request->input('features', []),
        );
    }
```

## fetched · named_reference

**Reason:** the region depends on App\DTOs\Dish\UpdateDishDTO, whose contract is defined in another file
**Source:** `app/DTOs/Dish/UpdateDishDTO.php` :: `__construct` (lines 10-21)
**Tokens:** 120

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
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\DTOs\Dish\UpdateDishDTO, whose contract is defined in another file
**Source:** `app/DTOs/Dish/UpdateDishDTO.php` :: `fromRequest` (lines 23-37)
**Tokens:** 267

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
        );
    }
```

## fetched · named_reference

**Reason:** the region depends on App\DTOs\MediaItem\UploadMediaDTO, whose contract is defined in another file
**Source:** `app/DTOs/MediaItem/UploadMediaDTO.php` :: `__construct` (lines 10-17)
**Tokens:** 71

```php
    public function __construct(
        public readonly string $page,
        public readonly string $section,
        public readonly string $key,
        public readonly UploadedFile $image,
        public readonly string $alt_ar,
        public readonly string $alt_en,
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\DTOs\MediaItem\UploadMediaDTO, whose contract is defined in another file
**Source:** `app/DTOs/MediaItem/UploadMediaDTO.php` :: `fromRequest` (lines 19-29)
**Tokens:** 112

```php
    public static function fromRequest(Request $request): self
    {
        return new self(
            page: $request->string('page')->toString(),
            section: $request->string('section')->toString(),
            key: $request->string('key')->toString(),
            image: $request->file('image'),
            alt_ar: $request->string('alt_ar')->toString(),
            alt_en: $request->string('alt_en')->toString(),
        );
    }
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

## fetched · named_reference

**Reason:** the region depends on App\Repositories\BranchRepository::create, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `create` (lines 26-29)
**Tokens:** 25

```php
    public function create(array $data): Branch
    {
        return Branch::create($data);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\BranchRepository::update, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `update` (lines 31-37)
**Tokens:** 42

```php
    public function update(int $id, array $data): Branch
    {
        $branch = Branch::findOrFail($id);
        $branch->update($data);

        return $branch;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\CateringPackageRepository::create, whose contract is defined in another file
**Source:** `app/Repositories/CateringPackageRepository.php` :: `create` (lines 24-32)
**Tokens:** 83

```php
    public function create(array $data, array $features = []): CateringPackage
    {
        return DB::transaction(function () use ($data, $features) {
            $package = CateringPackage::create($data);
            $package->features()->createMany($features);

            return $package->fresh('features');
        });
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\CateringPackageRepository::update, whose contract is defined in another file
**Source:** `app/Repositories/CateringPackageRepository.php` :: `update` (lines 34-45)
**Tokens:** 108

```php
    public function update(int $id, array $data, array $features = []): CateringPackage
    {
        return DB::transaction(function () use ($id, $data, $features) {
            $package = CateringPackage::findOrFail($id);
            $package->update($data);

            $package->features()->delete();
            $package->features()->createMany($features);

            return $package->fresh('features');
        });
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\DeliveryAppRepository::create, whose contract is defined in another file
**Source:** `app/Repositories/DeliveryAppRepository.php` :: `create` (lines 26-29)
**Tokens:** 27

```php
    public function create(array $data): DeliveryApp
    {
        return DeliveryApp::create($data);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\DeliveryAppRepository::update, whose contract is defined in another file
**Source:** `app/Repositories/DeliveryAppRepository.php` :: `update` (lines 31-37)
**Tokens:** 49

```php
    public function update(int $id, array $data): DeliveryApp
    {
        $deliveryApp = DeliveryApp::findOrFail($id);
        $deliveryApp->update($data);

        return $deliveryApp;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\DishRepository::create, whose contract is defined in another file
**Source:** `app/Repositories/DishRepository.php` :: `create` (lines 34-37)
**Tokens:** 24

```php
    public function create(array $data): Dish
    {
        return Dish::create($data);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\DishRepository::update, whose contract is defined in another file
**Source:** `app/Repositories/DishRepository.php` :: `update` (lines 39-45)
**Tokens:** 40

```php
    public function update(int $id, array $data): Dish
    {
        $dish = Dish::findOrFail($id);
        $dish->update($data);

        return $dish;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\MediaItemRepository::create, whose contract is defined in another file
**Source:** `app/Repositories/MediaItemRepository.php` :: `create` (lines 31-34)
**Tokens:** 26

```php
    public function create(array $data): MediaItem
    {
        return MediaItem::create($data);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\MediaItemRepository::update, whose contract is defined in another file
**Source:** `app/Repositories/MediaItemRepository.php` :: `update` (lines 36-42)
**Tokens:** 46

```php
    public function update(int $id, array $data): MediaItem
    {
        $mediaItem = MediaItem::findOrFail($id);
        $mediaItem->update($data);

        return $mediaItem;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\PersonalityRepository::create, whose contract is defined in another file
**Source:** `app/Repositories/PersonalityRepository.php` :: `create` (lines 26-29)
**Tokens:** 27

```php
    public function create(array $data): Personality
    {
        return Personality::create($data);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\PersonalityRepository::update, whose contract is defined in another file
**Source:** `app/Repositories/PersonalityRepository.php` :: `update` (lines 31-37)
**Tokens:** 49

```php
    public function update(int $id, array $data): Personality
    {
        $personality = Personality::findOrFail($id);
        $personality->update($data);

        return $personality;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\SampleMenuRepository::create, whose contract is defined in another file
**Source:** `app/Repositories/SampleMenuRepository.php` :: `create` (lines 24-32)
**Tokens:** 76

```php
    public function create(array $data, array $dishes = []): SampleMenu
    {
        return DB::transaction(function () use ($data, $dishes) {
            $menu = SampleMenu::create($data);
            $menu->dishes()->createMany($dishes);

            return $menu->fresh('dishes');
        });
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\SampleMenuRepository::update, whose contract is defined in another file
**Source:** `app/Repositories/SampleMenuRepository.php` :: `update` (lines 34-45)
**Tokens:** 98

```php
    public function update(int $id, array $data, array $dishes = []): SampleMenu
    {
        return DB::transaction(function () use ($id, $data, $dishes) {
            $menu = SampleMenu::findOrFail($id);
            $menu->update($data);

            $menu->dishes()->delete();
            $menu->dishes()->createMany($dishes);

            return $menu->fresh('dishes');
        });
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Services\ImageService::delete, whose contract is defined in another file
**Source:** `app/Services/ImageService.php` :: `delete` (lines 53-58)
**Tokens:** 44

```php
    public function delete(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\UploadedFile::fake, whose contract is defined in another file
**Source:** `tests/Feature/Actions/FileUploadRollbackTest.php` :: `Illuminate\Http\UploadedFile::fake` (lines 1-126)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Storage::disk, whose contract is defined in another file
**Source:** `tests/Feature/Actions/FileUploadRollbackTest.php` :: `Illuminate\Support\Facades\Storage::disk` (lines 1-126)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Storage::fake, whose contract is defined in another file
**Source:** `tests/Feature/Actions/FileUploadRollbackTest.php` :: `Illuminate\Support\Facades\Storage::fake` (lines 1-126)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on RuntimeException, whose contract is defined in another file
**Source:** `tests/Feature/Actions/FileUploadRollbackTest.php` :: `RuntimeException` (lines 1-126)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Actions/Branch/CreateBranchAction.php` (lines 37-51)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Actions/Branch/UpdateBranchAction.php` (lines 44-58)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Actions/Catering/CreatePackageAction.php` (lines 34-48)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Actions/Catering/CreateSampleMenuAction.php` (lines 31-45)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Actions/Catering/UpdatePackageAction.php` (lines 41-55)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Actions/Catering/UpdateSampleMenuAction.php` (lines 37-51)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Actions/DeliveryApp/CreateDeliveryAppAction.php` (lines 31-45)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Actions/DeliveryApp/UpdateDeliveryAppAction.php` (lines 37-51)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Actions/Dish/CreateDishAction.php` (lines 35-49)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Actions/Dish/UpdateDishAction.php` (lines 42-56)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Actions/MediaItem/UploadMediaAction.php` (lines 34-48)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Actions/Personality/CreatePersonalityAction.php` (lines 30-44)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Actions/Personality/UpdatePersonalityAction.php` (lines 37-51)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## Dropped

Nothing was dropped.
