# Context bundle

bundle_version 1 · budget 8000 / used 2700 tokens

## fetched · named_reference

**Reason:** the region depends on App\Models\Branch, whose contract is defined in another file
**Source:** `app/Models/Branch.php` :: `fillable` (lines 9-22)
**Tokens:** 72

```php
    protected $fillable = [
        'name_ar',
        'name_en',
        'city',
        'location_ar',
        'location_en',
        'phone',
        'opening_time',
        'closing_time',
        'google_maps_url',
        'image_path',
        'image_url',
        'order',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category, whose contract is defined in another file
**Source:** `app/Models/Category.php` :: `dishes` (lines 17-20)
**Tokens:** 24

```php
    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\CateringPackage, whose contract is defined in another file
**Source:** `app/Models/CateringPackage.php` :: `casts` (lines 23-26)
**Tokens:** 29

```php
    protected $casts = [
        'is_featured' => 'boolean',
        'price_starting_from' => 'decimal:2',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\CateringPackage, whose contract is defined in another file
**Source:** `app/Models/CateringPackage.php` :: `features` (lines 28-31)
**Tokens:** 32

```php
    public function features(): HasMany
    {
        return $this->hasMany(CateringPackageFeature::class, 'package_id');
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\CateringPackage, whose contract is defined in another file
**Source:** `app/Models/CateringPackage.php` :: `fillable` (lines 10-21)
**Tokens:** 64

```php
    protected $fillable = [
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'tag_en',
        'price_starting_from',
        'is_featured',
        'image_path',
        'image_url',
        'order',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\DeliveryApp, whose contract is defined in another file
**Source:** `app/Models/DeliveryApp.php` :: `casts` (lines 19-21)
**Tokens:** 17

```php
    protected $casts = [
        'is_active' => 'boolean',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\DeliveryApp, whose contract is defined in another file
**Source:** `app/Models/DeliveryApp.php` :: `fillable` (lines 9-17)
**Tokens:** 43

```php
    protected $fillable = [
        'name_ar',
        'name_en',
        'order_url',
        'logo_path',
        'logo_url',
        'is_active',
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

## fetched · named_reference

**Reason:** the region depends on App\Models\MediaItem, whose contract is defined in another file
**Source:** `app/Models/MediaItem.php` :: `fillable` (lines 10-22)
**Tokens:** 58

```php
    protected $fillable = [
        'page',
        'section',
        'key',
        'path',
        'url',
        'alt_ar',
        'alt_en',
        'mime_type',
        'size_bytes',
        'width',
        'height',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\MediaItem::forPage, whose contract is defined in another file
**Source:** `app/Models/MediaItem.php` :: `scopeForPage` (lines 24-33)
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

## fetched · named_reference

**Reason:** the region depends on App\Models\MediaItem, whose contract is defined in another file
**Source:** `app/Models/MediaItem.php` :: `scopeForPage` (lines 24-33)
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

## fetched · named_reference

**Reason:** the region depends on App\Models\PageContent, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\PageContent::forPage, whose contract is defined in another file
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

## fetched · named_reference

**Reason:** the region depends on App\Models\PageContent, whose contract is defined in another file
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

## fetched · named_reference

**Reason:** the region depends on App\Models\QuoteRequest, whose contract is defined in another file
**Source:** `app/Models/QuoteRequest.php` :: `casts` (lines 25-30)
**Tokens:** 53

```php
    protected $casts = [
        'status' => QuoteRequestStatusEnum::class,
        'event_type' => EventTypeEnum::class,
        'budget_range' => BudgetRangeEnum::class,
        'event_date' => 'date',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\QuoteRequest, whose contract is defined in another file
**Source:** `app/Models/QuoteRequest.php` :: `fillable` (lines 12-23)
**Tokens:** 58

```php
    protected $fillable = [
        'name',
        'phone',
        'email',
        'event_date',
        'guests_count',
        'branch',
        'event_type',
        'budget_range',
        'notes',
        'status',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\SampleMenu, whose contract is defined in another file
**Source:** `app/Models/SampleMenu.php` :: `casts` (lines 20-22)
**Tokens:** 17

```php
    protected $casts = [
        'is_featured' => 'boolean',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\SampleMenu, whose contract is defined in another file
**Source:** `app/Models/SampleMenu.php` :: `dishes` (lines 24-27)
**Tokens:** 26

```php
    public function dishes(): HasMany
    {
        return $this->hasMany(SampleMenuDish::class);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\SampleMenu, whose contract is defined in another file
**Source:** `app/Models/SampleMenu.php` :: `fillable` (lines 10-18)
**Tokens:** 44

```php
    protected $fillable = [
        'name_ar',
        'name_en',
        'tag_en',
        'is_featured',
        'image_path',
        'image_url',
        'order',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Setting::where, whose contract is defined in another file
**Source:** `app/Models/Setting.php` :: `fillable` (lines 10-17)
**Tokens:** 35

```php
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label_ar',
        'label_en',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Setting::forGroup, whose contract is defined in another file
**Source:** `app/Models/Setting.php` :: `scopeForGroup` (lines 19-26)
**Tokens:** 51

```php
    public function scopeForGroup(Builder $query, ?string $group = null): Builder
    {
        if ($group !== null) {
            $query->where('group', $group);
        }

        return $query;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Setting::where, whose contract is defined in another file
**Source:** `app/Models/Setting.php` :: `scopeForGroup` (lines 19-26)
**Tokens:** 51

```php
    public function scopeForGroup(Builder $query, ?string $group = null): Builder
    {
        if ($group !== null) {
            $query->where('group', $group);
        }

        return $query;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Testimonial, whose contract is defined in another file
**Source:** `app/Models/Testimonial.php` :: `casts` (lines 17-19)
**Tokens:** 17

```php
    protected $casts = [
        'is_active' => 'boolean',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Testimonial, whose contract is defined in another file
**Source:** `app/Models/Testimonial.php` :: `fillable` (lines 9-15)
**Tokens:** 32

```php
    protected $fillable = [
        'name',
        'quote_ar',
        'quote_en',
        'is_active',
        'order',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Timeline, whose contract is defined in another file
**Source:** `app/Models/Timeline.php` :: `fillable` (lines 9-17)
**Tokens:** 46

```php
    protected $fillable = [
        'year',
        'location_en',
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'order',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\User, whose contract is defined in another file
**Source:** `app/Models/User.php` :: `casts` (lines 39-50)
**Tokens:** 67

```php
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\User, whose contract is defined in another file
**Source:** `app/Models/User.php` :: `fillable` (lines 17-27)
**Tokens:** 50

```php
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\User, whose contract is defined in another file
**Source:** `app/Models/User.php` :: `hidden` (lines 29-37)
**Tokens:** 48

```php
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Branch::create, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `App\Models\Branch::create` (lines 1-37)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Branch::findOrFail, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `App\Models\Branch::findOrFail` (lines 1-37)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Branch::orderBy, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `App\Models\Branch::orderBy` (lines 1-37)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Eloquent\Collection, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `Illuminate\Database\Eloquent\Collection` (lines 1-37)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Category::create, whose contract is defined in another file
**Source:** `app/Repositories/CategoryRepository.php` :: `App\Models\Category::create` (lines 1-37)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Category::findOrFail, whose contract is defined in another file
**Source:** `app/Repositories/CategoryRepository.php` :: `App\Models\Category::findOrFail` (lines 1-37)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Category::orderBy, whose contract is defined in another file
**Source:** `app/Repositories/CategoryRepository.php` :: `App\Models\Category::orderBy` (lines 1-37)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Eloquent\Collection, whose contract is defined in another file
**Source:** `app/Repositories/CategoryRepository.php` :: `Illuminate\Database\Eloquent\Collection` (lines 1-37)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\CateringPackage::create, whose contract is defined in another file
**Source:** `app/Repositories/CateringPackageRepository.php` :: `App\Models\CateringPackage::create` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\CateringPackage::findOrFail, whose contract is defined in another file
**Source:** `app/Repositories/CateringPackageRepository.php` :: `App\Models\CateringPackage::findOrFail` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\CateringPackage::with, whose contract is defined in another file
**Source:** `app/Repositories/CateringPackageRepository.php` :: `App\Models\CateringPackage::with` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Eloquent\Collection, whose contract is defined in another file
**Source:** `app/Repositories/CateringPackageRepository.php` :: `Illuminate\Database\Eloquent\Collection` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\DB::transaction, whose contract is defined in another file
**Source:** `app/Repositories/CateringPackageRepository.php` :: `Illuminate\Support\Facades\DB::transaction` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\DeliveryApp::create, whose contract is defined in another file
**Source:** `app/Repositories/DeliveryAppRepository.php` :: `App\Models\DeliveryApp::create` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\DeliveryApp::findOrFail, whose contract is defined in another file
**Source:** `app/Repositories/DeliveryAppRepository.php` :: `App\Models\DeliveryApp::findOrFail` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\DeliveryApp::orderBy, whose contract is defined in another file
**Source:** `app/Repositories/DeliveryAppRepository.php` :: `App\Models\DeliveryApp::orderBy` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Eloquent\Collection, whose contract is defined in another file
**Source:** `app/Repositories/DeliveryAppRepository.php` :: `Illuminate\Database\Eloquent\Collection` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Dish::create, whose contract is defined in another file
**Source:** `app/Repositories/DishRepository.php` :: `App\Models\Dish::create` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Dish::findOrFail, whose contract is defined in another file
**Source:** `app/Repositories/DishRepository.php` :: `App\Models\Dish::findOrFail` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Dish::with, whose contract is defined in another file
**Source:** `app/Repositories/DishRepository.php` :: `App\Models\Dish::with` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Pagination\LengthAwarePaginator, whose contract is defined in another file
**Source:** `app/Repositories/DishRepository.php` :: `Illuminate\Pagination\LengthAwarePaginator` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\MediaItem::create, whose contract is defined in another file
**Source:** `app/Repositories/MediaItemRepository.php` :: `App\Models\MediaItem::create` (lines 1-40)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\MediaItem::findOrFail, whose contract is defined in another file
**Source:** `app/Repositories/MediaItemRepository.php` :: `App\Models\MediaItem::findOrFail` (lines 1-40)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Collection, whose contract is defined in another file
**Source:** `app/Repositories/MediaItemRepository.php` :: `Illuminate\Support\Collection` (lines 1-40)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\PageContent::findOrFail, whose contract is defined in another file
**Source:** `app/Repositories/PageContentRepository.php` :: `App\Models\PageContent::findOrFail` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Arr::only, whose contract is defined in another file
**Source:** `app/Repositories/PageContentRepository.php` :: `Illuminate\Support\Arr::only` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Collection, whose contract is defined in another file
**Source:** `app/Repositories/PageContentRepository.php` :: `Illuminate\Support\Collection` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\DB::transaction, whose contract is defined in another file
**Source:** `app/Repositories/PageContentRepository.php` :: `Illuminate\Support\Facades\DB::transaction` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\QuoteRequest::create, whose contract is defined in another file
**Source:** `app/Repositories/QuoteRequestRepository.php` :: `App\Models\QuoteRequest::create` (lines 1-46)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\QuoteRequest::findOrFail, whose contract is defined in another file
**Source:** `app/Repositories/QuoteRequestRepository.php` :: `App\Models\QuoteRequest::findOrFail` (lines 1-46)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\QuoteRequest::orderByDesc, whose contract is defined in another file
**Source:** `app/Repositories/QuoteRequestRepository.php` :: `App\Models\QuoteRequest::orderByDesc` (lines 1-46)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Pagination\LengthAwarePaginator, whose contract is defined in another file
**Source:** `app/Repositories/QuoteRequestRepository.php` :: `Illuminate\Pagination\LengthAwarePaginator` (lines 1-46)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\SampleMenu::create, whose contract is defined in another file
**Source:** `app/Repositories/SampleMenuRepository.php` :: `App\Models\SampleMenu::create` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\SampleMenu::findOrFail, whose contract is defined in another file
**Source:** `app/Repositories/SampleMenuRepository.php` :: `App\Models\SampleMenu::findOrFail` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\SampleMenu::with, whose contract is defined in another file
**Source:** `app/Repositories/SampleMenuRepository.php` :: `App\Models\SampleMenu::with` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Eloquent\Collection, whose contract is defined in another file
**Source:** `app/Repositories/SampleMenuRepository.php` :: `Illuminate\Database\Eloquent\Collection` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\DB::transaction, whose contract is defined in another file
**Source:** `app/Repositories/SampleMenuRepository.php` :: `Illuminate\Support\Facades\DB::transaction` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Setting::where, whose contract is defined in another file
**Source:** `app/Repositories/SettingRepository.php` :: `App\Models\Setting::where` (lines 1-24)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Collection, whose contract is defined in another file
**Source:** `app/Repositories/SettingRepository.php` :: `Illuminate\Support\Collection` (lines 1-24)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\DB::transaction, whose contract is defined in another file
**Source:** `app/Repositories/SettingRepository.php` :: `Illuminate\Support\Facades\DB::transaction` (lines 1-24)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Testimonial::create, whose contract is defined in another file
**Source:** `app/Repositories/TestimonialRepository.php` :: `App\Models\Testimonial::create` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Testimonial::findOrFail, whose contract is defined in another file
**Source:** `app/Repositories/TestimonialRepository.php` :: `App\Models\Testimonial::findOrFail` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Testimonial::orderBy, whose contract is defined in another file
**Source:** `app/Repositories/TestimonialRepository.php` :: `App\Models\Testimonial::orderBy` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Eloquent\Collection, whose contract is defined in another file
**Source:** `app/Repositories/TestimonialRepository.php` :: `Illuminate\Database\Eloquent\Collection` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Timeline::create, whose contract is defined in another file
**Source:** `app/Repositories/TimelineRepository.php` :: `App\Models\Timeline::create` (lines 1-37)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Timeline::findOrFail, whose contract is defined in another file
**Source:** `app/Repositories/TimelineRepository.php` :: `App\Models\Timeline::findOrFail` (lines 1-37)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Timeline::orderBy, whose contract is defined in another file
**Source:** `app/Repositories/TimelineRepository.php` :: `App\Models\Timeline::orderBy` (lines 1-37)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Eloquent\Collection, whose contract is defined in another file
**Source:** `app/Repositories/TimelineRepository.php` :: `Illuminate\Database\Eloquent\Collection` (lines 1-37)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\User::create, whose contract is defined in another file
**Source:** `app/Repositories/UserRepository.php` :: `App\Models\User::create` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\User::findOrFail, whose contract is defined in another file
**Source:** `app/Repositories/UserRepository.php` :: `App\Models\User::findOrFail` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\User::with, whose contract is defined in another file
**Source:** `app/Repositories/UserRepository.php` :: `App\Models\User::with` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Pagination\LengthAwarePaginator, whose contract is defined in another file
**Source:** `app/Repositories/UserRepository.php` :: `Illuminate\Pagination\LengthAwarePaginator` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\DB::transaction, whose contract is defined in another file
**Source:** `app/Repositories/UserRepository.php` :: `Illuminate\Support\Facades\DB::transaction` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Repositories/BranchRepository.php` (lines 1-37)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Repositories/CategoryRepository.php` (lines 1-37)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Repositories/CateringPackageRepository.php` (lines 1-51)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Repositories/DeliveryAppRepository.php` (lines 1-43)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Repositories/DishRepository.php` (lines 1-51)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Repositories/MediaItemRepository.php` (lines 1-40)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Repositories/PageContentRepository.php` (lines 1-43)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Repositories/QuoteRequestRepository.php` (lines 1-46)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Repositories/SampleMenuRepository.php` (lines 1-51)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Repositories/TestimonialRepository.php` (lines 1-43)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Repositories/TimelineRepository.php` (lines 1-37)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Repositories/UserRepository.php` (lines 1-43)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `tests/Feature/RepositoriesTest.php` (lines 1-387)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## Dropped

Nothing was dropped.
