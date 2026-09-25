# Context bundle

bundle_version 2 · budget 8000 / used 624 tokens

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Actions/Branch/GetBranchesAction.php` (lines 25-25)
**Tokens:** 16

```php
            fn () => $this->repository->getAll($showInFooter)
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Actions/Catering/GetPackagesAction.php` (lines 24-24)
**Tokens:** 12

```php
            fn () => $this->repository->getAll()
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Actions/Catering/GetSampleMenusAction.php` (lines 24-24)
**Tokens:** 12

```php
            fn () => $this->repository->getAll()
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Actions/DeliveryApp/GetDeliveryAppsAction.php` (lines 24-24)
**Tokens:** 15

```php
            fn () => $this->repository->getAll($activeOnly)
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Actions/Dish/GetDishesAction.php` (lines 30-30)
**Tokens:** 14

```php
            fn () => $this->repository->getAll($filters)
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Actions/Personality/GetPersonalitiesAction.php` (lines 24-24)
**Tokens:** 15

```php
            fn () => $this->repository->getAll($activeOnly)
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Actions/Setting/GetSettingsAction.php` (lines 23-23)
**Tokens:** 14

```php
            fn () => $this->repository->getAll($group)
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Actions/Testimonial/GetTestimonialsAction.php` (lines 24-24)
**Tokens:** 15

```php
            fn () => $this->repository->getAll($activeOnly)
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Actions/Timeline/GetTimelineAction.php` (lines 24-24)
**Tokens:** 12

```php
            fn () => $this->repository->getAll()
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Actions/User/GetUsersAction.php` (lines 16-16)
**Tokens:** 11

```php
        return $this->repository->getAll();
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Admin/CategoryController.php` (lines 19-19)
**Tokens:** 29

```php
        return $this->success(CategoryResource::collection($this->repository->getAll()), __('messages.fetched'));
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Admin/Catering/QuoteRequestController.php` (lines 24-24)
**Tokens:** 12

```php
        $result = $repository->getAll($filters);
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Admin/SeoSettingController.php` (lines 20-20)
**Tokens:** 28

```php
        return $this->success(SeoSettingResource::collection($repository->getAll()), __('messages.fetched'));
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Public/CategoryController.php` (lines 14-14)
**Tokens:** 11

```php
        $categories = $repository->getAll();
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Repositories/BranchRepository.php` (lines 10-10)
**Tokens:** 17

```php
    public function getAll(?bool $showInFooter = null): Collection
```

## flagged · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Repositories/BranchRepository.php` :: `getAll` (lines 15-21)
**Tokens:** 21

```text
ASSUMPTION: additional call sites exist beyond the search bound; not all verified
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Repositories/CategoryRepository.php` (lines 10-10)
**Tokens:** 10

```php
    public function getAll(): Collection
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Repositories/CateringPackageRepository.php` (lines 11-11)
**Tokens:** 10

```php
    public function getAll(): Collection
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Repositories/DeliveryAppRepository.php` (lines 10-10)
**Tokens:** 16

```php
    public function getAll(bool $activeOnly = true): Collection
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Repositories/DishRepository.php` (lines 10-10)
**Tokens:** 18

```php
    public function getAll(array $filters = []): LengthAwarePaginator
```

## fetched · changed_return_contract

**Subject:** getAll
**Reason:** the body of getAll now returns a single value or null where it returned a collection; its declared signature is unchanged, so its call sites are not shown by the diff
**Source:** `app/Repositories/PersonalityRepository.php` (lines 10-10)
**Tokens:** 16

```php
    public function getAll(bool $activeOnly = true): Collection
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\BranchController::success
**Reason:** the region calls App\Http\Controllers\Admin\BranchController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/BranchController.php` :: `App\Http\Controllers\Admin\BranchController::success` (lines 19-26)
**Tokens:** 62

```text
ASSUMPTION: success() is not declared in BranchController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## fetched · named_reference

**Subject:** App\Http\Resources\Branch\BranchResource
**Reason:** the region depends on App\Http\Resources\Branch\BranchResource, whose contract is defined in another file
**Source:** `app/Http/Resources/Branch/BranchResource.php` :: `toArray` (lines 13-34)
**Tokens:** 238

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
            'whatsapp_number' => $this->whatsapp_number ?? $this->phone,
            'opening_time' => $this->opening_time,
            'closing_time' => $this->closing_time,
            'google_maps_url' => $this->google_maps_url,
            'image_url' => $this->image_url,
            'order' => $this->order,
            'show_in_footer' => $this->show_in_footer,
            'locale' => app()->getLocale(),
        ];
    }
```

## Dropped

Nothing was dropped.
