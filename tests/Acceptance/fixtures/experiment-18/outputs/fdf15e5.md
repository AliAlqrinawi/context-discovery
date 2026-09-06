# Context bundle

bundle_version 1 · budget 8000 / used 1298 tokens

## fetched · named_reference

**Reason:** the region depends on App\Enums\QuoteRequestStatusEnum::Closed, whose contract is defined in another file
**Source:** `app/Enums/QuoteRequestStatusEnum.php` :: `Closed` (lines 10-10)
**Tokens:** 7

```php
    case Closed = 'closed';
```

## fetched · named_reference

**Reason:** the region depends on App\Enums\QuoteRequestStatusEnum::Contacted, whose contract is defined in another file
**Source:** `app/Enums/QuoteRequestStatusEnum.php` :: `Contacted` (lines 9-9)
**Tokens:** 9

```php
    case Contacted = 'contacted';
```

## fetched · named_reference

**Reason:** the region depends on App\Enums\QuoteRequestStatusEnum::Pending, whose contract is defined in another file
**Source:** `app/Enums/QuoteRequestStatusEnum.php` :: `Pending` (lines 7-7)
**Tokens:** 8

```php
    case Pending = 'pending';
```

## fetched · named_reference

**Reason:** the region depends on App\Enums\QuoteRequestStatusEnum::Reviewed, whose contract is defined in another file
**Source:** `app/Enums/QuoteRequestStatusEnum.php` :: `Reviewed` (lines 8-8)
**Tokens:** 8

```php
    case Reviewed = 'reviewed';
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Branch::count, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DashboardController.php` :: `App\Models\Branch::count` (lines 1-65)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Category::count, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DashboardController.php` :: `App\Models\Category::count` (lines 1-65)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\CateringPackage::count, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DashboardController.php` :: `App\Models\CateringPackage::count` (lines 1-65)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\DeliveryApp::count, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DashboardController.php` :: `App\Models\DeliveryApp::count` (lines 1-65)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\DeliveryApp::where, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DashboardController.php` :: `App\Models\DeliveryApp::where` (lines 1-65)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Dish::count, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DashboardController.php` :: `App\Models\Dish::count` (lines 1-65)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Dish::where, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DashboardController.php` :: `App\Models\Dish::where` (lines 1-65)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\QuoteRequest::count, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DashboardController.php` :: `App\Models\QuoteRequest::count` (lines 1-65)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\QuoteRequest::where, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DashboardController.php` :: `App\Models\QuoteRequest::where` (lines 1-65)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Testimonial::count, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DashboardController.php` :: `App\Models\Testimonial::count` (lines 1-65)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Testimonial::where, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DashboardController.php` :: `App\Models\Testimonial::where` (lines 1-65)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Timeline::count, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DashboardController.php` :: `App\Models\Timeline::count` (lines 1-65)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\User::count, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DashboardController.php` :: `App\Models\User::count` (lines 1-65)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Branch::count, whose contract is defined in another file
**Source:** `app/Models/Branch.php` :: `casts` (lines 26-28)
**Tokens:** 18

```php
    protected $casts = [
        'show_in_footer' => 'boolean',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Branch::count, whose contract is defined in another file
**Source:** `app/Models/Branch.php` :: `fillable` (lines 9-24)
**Tokens:** 85

```php
    protected $fillable = [
        'name_ar',
        'name_en',
        'city',
        'location_ar',
        'location_en',
        'phone',
        'whatsapp_number',
        'opening_time',
        'closing_time',
        'google_maps_url',
        'image_path',
        'image_url',
        'order',
        'show_in_footer',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category::count, whose contract is defined in another file
**Source:** `app/Models/Category.php` :: `dishes` (lines 17-20)
**Tokens:** 24

```php
    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Category::count, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\CateringPackage::count, whose contract is defined in another file
**Source:** `app/Models/CateringPackage.php` :: `casts` (lines 23-26)
**Tokens:** 29

```php
    protected $casts = [
        'is_featured' => 'boolean',
        'price_starting_from' => 'decimal:2',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\CateringPackage::count, whose contract is defined in another file
**Source:** `app/Models/CateringPackage.php` :: `features` (lines 28-31)
**Tokens:** 32

```php
    public function features(): HasMany
    {
        return $this->hasMany(CateringPackageFeature::class, 'package_id');
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\CateringPackage::count, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\DeliveryApp::count, whose contract is defined in another file
**Source:** `app/Models/DeliveryApp.php` :: `casts` (lines 19-21)
**Tokens:** 17

```php
    protected $casts = [
        'is_active' => 'boolean',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\DeliveryApp::count, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\Dish::count, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\Dish::count, whose contract is defined in another file
**Source:** `app/Models/Dish.php` :: `category` (lines 30-33)
**Tokens:** 26

```php
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Dish::count, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\QuoteRequest::count, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\QuoteRequest::count, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\Testimonial::count, whose contract is defined in another file
**Source:** `app/Models/Testimonial.php` :: `casts` (lines 17-19)
**Tokens:** 17

```php
    protected $casts = [
        'is_active' => 'boolean',
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Testimonial::count, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\Timeline::count, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\User::count, whose contract is defined in another file
**Source:** `app/Models/User.php` :: `casts` (lines 40-51)
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

**Reason:** the region depends on App\Models\User::count, whose contract is defined in another file
**Source:** `app/Models/User.php` :: `fillable` (lines 18-28)
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

**Reason:** the region depends on App\Models\User::count, whose contract is defined in another file
**Source:** `app/Models/User.php` :: `hidden` (lines 30-38)
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

## fetched · named_reference

**Reason:** the region depends on App\Repositories\QuoteRequestRepository::create, whose contract is defined in another file
**Source:** `app/Repositories/QuoteRequestRepository.php` :: `create` (lines 34-37)
**Tokens:** 28

```php
    public function create(array $data): QuoteRequest
    {
        return QuoteRequest::create($data);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\QuoteRequestRepository::updateStatus, whose contract is defined in another file
**Source:** `app/Repositories/QuoteRequestRepository.php` :: `updateStatus` (lines 39-45)
**Tokens:** 56

```php
    public function updateStatus(int $id, string $status): QuoteRequest
    {
        $quoteRequest = QuoteRequest::findOrFail($id);
        $quoteRequest->update(['status' => $status]);

        return $quoteRequest;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\UserRepository::create, whose contract is defined in another file
**Source:** `app/Repositories/UserRepository.php` :: `create` (lines 21-29)
**Tokens:** 64

```php
    public function create(array $data, string $role): User
    {
        return DB::transaction(function () use ($data, $role) {
            $user = User::create($data);
            $user->assignRole($role);

            return $user;
        });
    }
```

## flagged · named_reference

**Reason:** the region depends on App\Models\User::where, whose contract is defined in another file
**Source:** `tests/Feature/DashboardStatsTest.php` :: `App\Models\User::where` (lines 1-60)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## Dropped

Nothing was dropped.
