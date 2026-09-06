# Context bundle

bundle_version 1 · budget 8000 / used 4694 tokens

## fetched · named_reference

**Reason:** the region depends on App\Actions\Branch\GetBranchesAction, whose contract is defined in another file
**Source:** `app/Actions/Branch/GetBranchesAction.php` :: `__construct` (lines 11-13)
**Tokens:** 24

```php
    public function __construct(
        private readonly BranchRepository $repository,
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Branch\GetBranchesAction, whose contract is defined in another file
**Source:** `app/Actions/Branch/GetBranchesAction.php` :: `execute` (lines 15-25)
**Tokens:** 73

```php
    public function execute(): Collection
    {
        $locale = app()->getLocale();
        $key = "branches_{$locale}";

        return Cache::tags(['branches'])->remember(
            $key,
            now()->addHour(),
            fn () => $this->repository->getAll()
        );
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Catering\GetPackagesAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/GetPackagesAction.php` :: `__construct` (lines 11-13)
**Tokens:** 27

```php
    public function __construct(
        private readonly CateringPackageRepository $repository,
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Catering\GetPackagesAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/GetPackagesAction.php` :: `execute` (lines 15-25)
**Tokens:** 77

```php
    public function execute(): Collection
    {
        $locale = app()->getLocale();
        $key = "catering_packages_{$locale}";

        return Cache::tags(['catering_packages'])->remember(
            $key,
            now()->addHour(),
            fn () => $this->repository->getAll()
        );
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Catering\GetSampleMenusAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/GetSampleMenusAction.php` :: `__construct` (lines 11-13)
**Tokens:** 25

```php
    public function __construct(
        private readonly SampleMenuRepository $repository,
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Catering\GetSampleMenusAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/GetSampleMenusAction.php` :: `execute` (lines 15-25)
**Tokens:** 75

```php
    public function execute(): Collection
    {
        $locale = app()->getLocale();
        $key = "sample_menus_{$locale}";

        return Cache::tags(['sample_menus'])->remember(
            $key,
            now()->addHour(),
            fn () => $this->repository->getAll()
        );
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Catering\SubmitQuoteRequestAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/SubmitQuoteRequestAction.php` :: `__construct` (lines 11-13)
**Tokens:** 26

```php
    public function __construct(
        private readonly QuoteRequestRepository $repository,
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Catering\SubmitQuoteRequestAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/SubmitQuoteRequestAction.php` :: `execute` (lines 15-29)
**Tokens:** 136

```php
    public function execute(SubmitQuoteRequestDTO $dto): QuoteRequest
    {
        return $this->repository->create([
            'name' => $dto->name,
            'phone' => $dto->phone,
            'email' => $dto->email,
            'event_date' => $dto->event_date,
            'guests_count' => $dto->guests_count,
            'branch' => $dto->branch,
            'event_type' => $dto->event_type,
            'budget_range' => $dto->budget_range,
            'notes' => $dto->notes,
            'status' => 'pending',
        ]);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\DeliveryApp\GetDeliveryAppsAction, whose contract is defined in another file
**Source:** `app/Actions/DeliveryApp/GetDeliveryAppsAction.php` :: `__construct` (lines 11-13)
**Tokens:** 26

```php
    public function __construct(
        private readonly DeliveryAppRepository $repository,
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\DeliveryApp\GetDeliveryAppsAction, whose contract is defined in another file
**Source:** `app/Actions/DeliveryApp/GetDeliveryAppsAction.php` :: `execute` (lines 15-25)
**Tokens:** 92

```php
    public function execute(bool $activeOnly = true): Collection
    {
        $locale = app()->getLocale();
        $key = "delivery_apps_{$locale}_".($activeOnly ? 'active' : 'all');

        return Cache::tags(['delivery_apps'])->remember(
            $key,
            now()->addHour(),
            fn () => $this->repository->getAll($activeOnly)
        );
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Dish\GetDishAction, whose contract is defined in another file
**Source:** `app/Actions/Dish/GetDishAction.php` :: `__construct` (lines 10-12)
**Tokens:** 24

```php
    public function __construct(
        private readonly DishRepository $repository,
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Dish\GetDishAction, whose contract is defined in another file
**Source:** `app/Actions/Dish/GetDishAction.php` :: `execute` (lines 14-17)
**Tokens:** 26

```php
    public function execute(int $id): Dish
    {
        return $this->repository->getById($id);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Dish\GetDishesAction, whose contract is defined in another file
**Source:** `app/Actions/Dish/GetDishesAction.php` :: `__construct` (lines 11-13)
**Tokens:** 24

```php
    public function __construct(
        private readonly DishRepository $repository,
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Dish\GetDishesAction, whose contract is defined in another file
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

## fetched · named_reference

**Reason:** the region depends on App\Actions\MediaItem\GetMediaItemsAction, whose contract is defined in another file
**Source:** `app/Actions/MediaItem/GetMediaItemsAction.php` :: `__construct` (lines 11-13)
**Tokens:** 25

```php
    public function __construct(
        private readonly MediaItemRepository $repository,
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\MediaItem\GetMediaItemsAction, whose contract is defined in another file
**Source:** `app/Actions/MediaItem/GetMediaItemsAction.php` :: `execute` (lines 15-24)
**Tokens:** 80

```php
    public function execute(string $page, ?string $section = null): Collection
    {
        $key = "media_items_{$page}_{$section}";

        return Cache::tags(['media_items'])->remember(
            $key,
            now()->addDay(),
            fn () => $this->repository->getByPage($page, $section)
        );
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\PageContent\GetPageContentAction, whose contract is defined in another file
**Source:** `app/Actions/PageContent/GetPageContentAction.php` :: `__construct` (lines 11-13)
**Tokens:** 26

```php
    public function __construct(
        private readonly PageContentRepository $repository,
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\PageContent\GetPageContentAction, whose contract is defined in another file
**Source:** `app/Actions/PageContent/GetPageContentAction.php` :: `execute` (lines 15-25)
**Tokens:** 93

```php
    public function execute(string $page, ?string $section = null): Collection
    {
        $locale = app()->getLocale();
        $key = "page_contents_{$page}_{$section}_{$locale}";

        return Cache::tags(['page_contents'])->remember(
            $key,
            now()->addDay(),
            fn () => $this->repository->getByPage($page, $section)
        );
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Setting\GetSettingsAction, whose contract is defined in another file
**Source:** `app/Actions/Setting/GetSettingsAction.php` :: `__construct` (lines 11-13)
**Tokens:** 25

```php
    public function __construct(
        private readonly SettingRepository $repository,
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Setting\GetSettingsAction, whose contract is defined in another file
**Source:** `app/Actions/Setting/GetSettingsAction.php` :: `execute` (lines 15-24)
**Tokens:** 69

```php
    public function execute(?string $group = null): Collection
    {
        $key = "settings_{$group}";

        return Cache::tags(['settings'])->remember(
            $key,
            now()->addDay(),
            fn () => $this->repository->getAll($group)
        );
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Testimonial\GetTestimonialsAction, whose contract is defined in another file
**Source:** `app/Actions/Testimonial/GetTestimonialsAction.php` :: `__construct` (lines 11-13)
**Tokens:** 26

```php
    public function __construct(
        private readonly TestimonialRepository $repository,
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Testimonial\GetTestimonialsAction, whose contract is defined in another file
**Source:** `app/Actions/Testimonial/GetTestimonialsAction.php` :: `execute` (lines 15-25)
**Tokens:** 92

```php
    public function execute(bool $activeOnly = true): Collection
    {
        $locale = app()->getLocale();
        $key = "testimonials_{$locale}_".($activeOnly ? 'active' : 'all');

        return Cache::tags(['testimonials'])->remember(
            $key,
            now()->addHour(),
            fn () => $this->repository->getAll($activeOnly)
        );
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Timeline\GetTimelineAction, whose contract is defined in another file
**Source:** `app/Actions/Timeline/GetTimelineAction.php` :: `__construct` (lines 11-13)
**Tokens:** 25

```php
    public function __construct(
        private readonly TimelineRepository $repository,
    ) {}
```

## fetched · named_reference

**Reason:** the region depends on App\Actions\Timeline\GetTimelineAction, whose contract is defined in another file
**Source:** `app/Actions/Timeline/GetTimelineAction.php` :: `execute` (lines 15-25)
**Tokens:** 72

```php
    public function execute(): Collection
    {
        $locale = app()->getLocale();
        $key = "timeline_{$locale}";

        return Cache::tags(['timeline'])->remember(
            $key,
            now()->addDay(),
            fn () => $this->repository->getAll()
        );
    }
```

## fetched · named_reference

**Reason:** the region depends on App\DTOs\Catering\SubmitQuoteRequestDTO::fromRequest, whose contract is defined in another file
**Source:** `app/DTOs/Catering/SubmitQuoteRequestDTO.php` :: `fromRequest` (lines 21-34)
**Tokens:** 197

```php
    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            phone: $request->string('phone')->toString(),
            email: $request->filled('email') ? $request->string('email')->toString() : null,
            event_date: $request->string('event_date')->toString(),
            guests_count: (int) $request->input('guests_count'),
            branch: $request->string('branch')->toString(),
            event_type: $request->string('event_type')->toString(),
            budget_range: $request->filled('budget_range') ? $request->string('budget_range')->toString() : null,
            notes: $request->filled('notes') ? $request->string('notes')->toString() : null,
        );
    }
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/BranchController.php` :: `Illuminate\Http\JsonResponse` (lines 21-31)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Http\Resources\Branch\BranchResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/BranchController.php` :: `App\Http\Resources\Branch\BranchResource::collection` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/BranchController.php` :: `Illuminate\Http\JsonResponse` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Http\Resources\Category\CategoryResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/CategoryController.php` :: `App\Http\Resources\Category\CategoryResource::collection` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/CategoryController.php` :: `Illuminate\Http\JsonResponse` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Http\Resources\Catering\PackageResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/Catering/CateringPackageController.php` :: `App\Http\Resources\Catering\PackageResource::collection` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/Catering/CateringPackageController.php` :: `Illuminate\Http\JsonResponse` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/Catering/QuoteRequestController.php` :: `Illuminate\Http\JsonResponse` (lines 1-21)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Http\Resources\Catering\SampleMenuResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/Catering/SampleMenuController.php` :: `App\Http\Resources\Catering\SampleMenuResource::collection` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/Catering/SampleMenuController.php` :: `Illuminate\Http\JsonResponse` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Http\Resources\DeliveryApp\DeliveryAppResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/DeliveryAppController.php` :: `App\Http\Resources\DeliveryApp\DeliveryAppResource::collection` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/DeliveryAppController.php` :: `Illuminate\Http\JsonResponse` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Http\Resources\Dish\DishResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/DishController.php` :: `App\Http\Resources\Dish\DishResource::collection` (lines 1-32)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/DishController.php` :: `Illuminate\Http\JsonResponse` (lines 1-32)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\Request, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/DishController.php` :: `Illuminate\Http\Request` (lines 1-32)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/MediaItemController.php` :: `Illuminate\Http\JsonResponse` (lines 1-25)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\Request, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/MediaItemController.php` :: `Illuminate\Http\Request` (lines 1-25)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/PageContentController.php` :: `Illuminate\Http\JsonResponse` (lines 1-25)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\Request, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/PageContentController.php` :: `Illuminate\Http\Request` (lines 1-25)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/SettingController.php` :: `Illuminate\Http\JsonResponse` (lines 1-21)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\Request, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/SettingController.php` :: `Illuminate\Http\Request` (lines 1-21)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Http\Resources\Testimonial\TestimonialResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/TestimonialController.php` :: `App\Http\Resources\Testimonial\TestimonialResource::collection` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/TestimonialController.php` :: `Illuminate\Http\JsonResponse` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Http\Resources\Timeline\TimelineResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/TimelineController.php` :: `App\Http\Resources\Timeline\TimelineResource::collection` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/TimelineController.php` :: `Illuminate\Http\JsonResponse` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Requests\Catering\StoreQuoteRequestRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Catering/StoreQuoteRequestRequest.php` :: `authorize` (lines 13-16)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Requests\Catering\StoreQuoteRequestRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Catering/StoreQuoteRequestRequest.php` :: `rules` (lines 18-31)
**Tokens:** 178

```php
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'event_date' => ['required', 'date', 'after:today'],
            'guests_count' => ['required', 'integer', 'min:1', 'max:10000'],
            'branch' => ['required', 'string', Rule::in(BranchLocationEnum::values())],
            'event_type' => ['required', 'string', Rule::in(EventTypeEnum::values())],
            'budget_range' => ['nullable', 'string', Rule::in(BudgetRangeEnum::values())],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\Branch\BranchResource, whose contract is defined in another file
**Source:** `app/Http/Resources/Branch/BranchResource.php` :: `toArray` (lines 13-32)
**Tokens:** 206

```php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->resolveLocale($this->name_ar, $this->name_en),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'location' => $this->resolveLocale($this->location_ar, $this->location_en),
            'location_ar' => $this->location_ar,
            'location_en' => $this->location_en,
            'city' => $this->city,
            'phone' => $this->phone,
            'opening_time' => $this->opening_time,
            'closing_time' => $this->closing_time,
            'google_maps_url' => $this->google_maps_url,
            'image_url' => $this->image_url,
            'order' => $this->order,
            'locale' => app()->getLocale(),
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\Category\CategoryResource::collection, whose contract is defined in another file
**Source:** `app/Http/Resources/Category/CategoryResource.php` :: `toArray` (lines 13-24)
**Tokens:** 100

```php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->resolveLocale($this->name_ar, $this->name_en),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'order' => $this->order,
            'locale' => app()->getLocale(),
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\Catering\PackageResource::collection, whose contract is defined in another file
**Source:** `app/Http/Resources/Catering/PackageResource.php` :: `toArray` (lines 13-35)
**Tokens:** 261

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
            'tag_en' => $this->tag_en,
            'price_starting_from' => $this->price_starting_from,
            'is_featured' => $this->is_featured,
            'features' => $this->whenLoaded('features', fn () => $this->features->map(fn ($feature) => [
                'feature_ar' => $feature->feature_ar,
                'feature_en' => $feature->feature_en,
                'order' => $feature->order,
            ])),
            'image_url' => $this->image_url,
            'order' => $this->order,
            'locale' => app()->getLocale(),
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\Catering\QuoteRequestResource, whose contract is defined in another file
**Source:** `app/Http/Resources/Catering/QuoteRequestResource.php` :: `toArray` (lines 10-29)
**Tokens:** 186

```php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'event_date' => $this->event_date?->toDateString(),
            'guests_count' => $this->guests_count,
            'branch' => $this->branch,
            'event_type' => $this->event_type->value,
            'budget_range' => $this->budget_range?->value,
            'notes' => $this->notes,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\Catering\SampleMenuResource::collection, whose contract is defined in another file
**Source:** `app/Http/Resources/Catering/SampleMenuResource.php` :: `toArray` (lines 13-31)
**Tokens:** 190

```php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->resolveLocale($this->name_ar, $this->name_en),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'tag_en' => $this->tag_en,
            'is_featured' => $this->is_featured,
            'dishes' => $this->whenLoaded('dishes', fn () => $this->dishes->map(fn ($dish) => [
                'dish_name_ar' => $dish->dish_name_ar,
                'dish_name_en' => $dish->dish_name_en,
                'order' => $dish->order,
            ])),
            'image_url' => $this->image_url,
            'order' => $this->order,
            'locale' => app()->getLocale(),
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\DeliveryApp\DeliveryAppResource::collection, whose contract is defined in another file
**Source:** `app/Http/Resources/DeliveryApp/DeliveryAppResource.php` :: `toArray` (lines 13-26)
**Tokens:** 124

```php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->resolveLocale($this->name_ar, $this->name_en),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'order_url' => $this->order_url,
            'logo_url' => $this->logo_url,
            'is_active' => $this->is_active,
            'order' => $this->order,
            'locale' => app()->getLocale(),
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\Dish\DishResource, whose contract is defined in another file
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

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\MediaItem\MediaItemResource, whose contract is defined in another file
**Source:** `app/Http/Resources/MediaItem/MediaItemResource.php` :: `toArray` (lines 13-26)
**Tokens:** 115

```php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'page' => $this->page,
            'section' => $this->section,
            'key' => $this->key,
            'url' => $this->url,
            'alt' => $this->resolveLocale($this->alt_ar, $this->alt_en),
            'alt_ar' => $this->alt_ar,
            'alt_en' => $this->alt_en,
            'locale' => app()->getLocale(),
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\PageContent\PageContentResource, whose contract is defined in another file
**Source:** `app/Http/Resources/PageContent/PageContentResource.php` :: `toArray` (lines 13-27)
**Tokens:** 129

```php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'page' => $this->page,
            'section' => $this->section,
            'key' => $this->key,
            'value' => $this->resolveLocale($this->value_ar, $this->value_en),
            'value_ar' => $this->value_ar,
            'value_en' => $this->value_en,
            'type' => $this->type,
            'order' => $this->order,
            'locale' => app()->getLocale(),
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\Setting\SettingResource, whose contract is defined in another file
**Source:** `app/Http/Resources/Setting/SettingResource.php` :: `toArray` (lines 13-26)
**Tokens:** 119

```php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->value,
            'type' => $this->type,
            'group' => $this->group,
            'label' => $this->resolveLocale($this->label_ar, $this->label_en),
            'label_ar' => $this->label_ar,
            'label_en' => $this->label_en,
            'locale' => app()->getLocale(),
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\Testimonial\TestimonialResource::collection, whose contract is defined in another file
**Source:** `app/Http/Resources/Testimonial/TestimonialResource.php` :: `toArray` (lines 13-25)
**Tokens:** 113

```php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'quote' => $this->resolveLocale($this->quote_ar, $this->quote_en),
            'quote_ar' => $this->quote_ar,
            'quote_en' => $this->quote_en,
            'is_active' => $this->is_active,
            'order' => $this->order,
            'locale' => app()->getLocale(),
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\Timeline\TimelineResource::collection, whose contract is defined in another file
**Source:** `app/Http/Resources/Timeline/TimelineResource.php` :: `toArray` (lines 13-28)
**Tokens:** 165

```php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'year' => $this->year,
            'location_en' => $this->location_en,
            'title' => $this->resolveLocale($this->title_ar, $this->title_en),
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'description' => $this->resolveLocale($this->description_ar, $this->description_en),
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'order' => $this->order,
            'locale' => app()->getLocale(),
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\BranchRepository, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `create` (lines 20-23)
**Tokens:** 25

```php
    public function create(array $data): Branch
    {
        return Branch::create($data);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\BranchRepository, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `delete` (lines 33-36)
**Tokens:** 24

```php
    public function delete(int $id): void
    {
        Branch::findOrFail($id)->delete();
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\BranchRepository, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `getAll` (lines 10-13)
**Tokens:** 25

```php
    public function getAll(): Collection
    {
        return Branch::orderBy('order')->get();
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\BranchRepository, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `getById` (lines 15-18)
**Tokens:** 24

```php
    public function getById(int $id): Branch
    {
        return Branch::findOrFail($id);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\BranchRepository, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `update` (lines 25-31)
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

**Reason:** the region depends on App\Repositories\CategoryRepository, whose contract is defined in another file
**Source:** `app/Repositories/CategoryRepository.php` :: `create` (lines 20-23)
**Tokens:** 26

```php
    public function create(array $data): Category
    {
        return Category::create($data);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\CategoryRepository, whose contract is defined in another file
**Source:** `app/Repositories/CategoryRepository.php` :: `delete` (lines 33-36)
**Tokens:** 25

```php
    public function delete(int $id): void
    {
        Category::findOrFail($id)->delete();
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\CategoryRepository, whose contract is defined in another file
**Source:** `app/Repositories/CategoryRepository.php` :: `getAll` (lines 10-13)
**Tokens:** 26

```php
    public function getAll(): Collection
    {
        return Category::orderBy('order')->get();
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\CategoryRepository, whose contract is defined in another file
**Source:** `app/Repositories/CategoryRepository.php` :: `getById` (lines 15-18)
**Tokens:** 25

```php
    public function getById(int $id): Category
    {
        return Category::findOrFail($id);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\CategoryRepository, whose contract is defined in another file
**Source:** `app/Repositories/CategoryRepository.php` :: `update` (lines 25-31)
**Tokens:** 45

```php
    public function update(int $id, array $data): Category
    {
        $category = Category::findOrFail($id);
        $category->update($data);

        return $category;
    }
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::middleware, whose contract is defined in another file
**Source:** `bootstrap/app.php` :: `Illuminate\Support\Facades\Route::middleware` (lines 8-25)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::delete, whose contract is defined in another file
**Source:** `routes/admin.php` :: `Illuminate\Support\Facades\Route::delete` (lines 1-132)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::get, whose contract is defined in another file
**Source:** `routes/admin.php` :: `Illuminate\Support\Facades\Route::get` (lines 1-132)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::middleware, whose contract is defined in another file
**Source:** `routes/admin.php` :: `Illuminate\Support\Facades\Route::middleware` (lines 1-132)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::patch, whose contract is defined in another file
**Source:** `routes/admin.php` :: `Illuminate\Support\Facades\Route::patch` (lines 1-132)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::post, whose contract is defined in another file
**Source:** `routes/admin.php` :: `Illuminate\Support\Facades\Route::post` (lines 1-132)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::prefix, whose contract is defined in another file
**Source:** `routes/admin.php` :: `Illuminate\Support\Facades\Route::prefix` (lines 1-132)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::put, whose contract is defined in another file
**Source:** `routes/admin.php` :: `Illuminate\Support\Facades\Route::put` (lines 1-132)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::get, whose contract is defined in another file
**Source:** `routes/api.php` :: `Illuminate\Support\Facades\Route::get` (lines 1-87)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::post, whose contract is defined in another file
**Source:** `routes/api.php` :: `Illuminate\Support\Facades\Route::post` (lines 1-87)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::prefix, whose contract is defined in another file
**Source:** `routes/api.php` :: `Illuminate\Support\Facades\Route::prefix` (lines 1-87)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## Dropped

Nothing was dropped.
