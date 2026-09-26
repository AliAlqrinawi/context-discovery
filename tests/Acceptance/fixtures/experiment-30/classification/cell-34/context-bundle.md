# Context bundle

bundle_version 2 · budget 8000 / used 4722 tokens

## flagged · named_reference

**Subject:** App\Http\Controllers\Auth\AuthController::success
**Reason:** the region calls App\Http\Controllers\Auth\AuthController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Auth/AuthController.php` :: `App\Http\Controllers\Auth\AuthController::success` (lines 18-35)
**Tokens:** 61

```text
ASSUMPTION: success() is not declared in AuthController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\BranchController::created
**Reason:** the region calls App\Http\Controllers\BranchController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/BranchController.php` :: `App\Http\Controllers\BranchController::created` (lines 17-43)
**Tokens:** 62

```text
ASSUMPTION: created() is not declared in BranchController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\BranchController::deleted
**Reason:** the region calls App\Http\Controllers\BranchController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/BranchController.php` :: `App\Http\Controllers\BranchController::deleted` (lines 17-43)
**Tokens:** 62

```text
ASSUMPTION: deleted() is not declared in BranchController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\BranchController::success
**Reason:** the region calls App\Http\Controllers\BranchController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/BranchController.php` :: `App\Http\Controllers\BranchController::success` (lines 17-43)
**Tokens:** 62

```text
ASSUMPTION: success() is not declared in BranchController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Branch\BranchResource::collection
**Reason:** the region depends on App\Http\Resources\Branch\BranchResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/BranchController.php` :: `App\Http\Resources\Branch\BranchResource::collection` (lines 17-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\CategoryController::created
**Reason:** the region calls App\Http\Controllers\CategoryController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/CategoryController.php` :: `App\Http\Controllers\CategoryController::created` (lines 15-41)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in CategoryController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\CategoryController::deleted
**Reason:** the region calls App\Http\Controllers\CategoryController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/CategoryController.php` :: `App\Http\Controllers\CategoryController::deleted` (lines 15-41)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in CategoryController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\CategoryController::success
**Reason:** the region calls App\Http\Controllers\CategoryController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/CategoryController.php` :: `App\Http\Controllers\CategoryController::success` (lines 15-41)
**Tokens:** 62

```text
ASSUMPTION: success() is not declared in CategoryController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Category\CategoryResource::collection
**Reason:** the region depends on App\Http\Resources\Category\CategoryResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/CategoryController.php` :: `App\Http\Resources\Category\CategoryResource::collection` (lines 15-41)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\CateringPackageController::created
**Reason:** the region calls App\Http\Controllers\Catering\CateringPackageController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/CateringPackageController.php` :: `App\Http\Controllers\Catering\CateringPackageController::created` (lines 18-44)
**Tokens:** 64

```text
ASSUMPTION: created() is not declared in CateringPackageController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\CateringPackageController::deleted
**Reason:** the region calls App\Http\Controllers\Catering\CateringPackageController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/CateringPackageController.php` :: `App\Http\Controllers\Catering\CateringPackageController::deleted` (lines 18-44)
**Tokens:** 64

```text
ASSUMPTION: deleted() is not declared in CateringPackageController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\CateringPackageController::success
**Reason:** the region calls App\Http\Controllers\Catering\CateringPackageController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/CateringPackageController.php` :: `App\Http\Controllers\Catering\CateringPackageController::success` (lines 18-44)
**Tokens:** 64

```text
ASSUMPTION: success() is not declared in CateringPackageController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Catering\PackageResource::collection
**Reason:** the region depends on App\Http\Resources\Catering\PackageResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Catering/CateringPackageController.php` :: `App\Http\Resources\Catering\PackageResource::collection` (lines 18-44)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\QuoteRequestController::created
**Reason:** the region calls App\Http\Controllers\Catering\QuoteRequestController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/QuoteRequestController.php` :: `App\Http\Controllers\Catering\QuoteRequestController::created` (lines 20-26)
**Tokens:** 64

```text
ASSUMPTION: created() is not declared in QuoteRequestController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\QuoteRequestController::paginated
**Reason:** the region calls App\Http\Controllers\Catering\QuoteRequestController::paginated, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/QuoteRequestController.php` :: `App\Http\Controllers\Catering\QuoteRequestController::paginated` (lines 33-50)
**Tokens:** 64

```text
ASSUMPTION: paginated() is not declared in QuoteRequestController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:38, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\QuoteRequestController::success
**Reason:** the region calls App\Http\Controllers\Catering\QuoteRequestController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/QuoteRequestController.php` :: `App\Http\Controllers\Catering\QuoteRequestController::success` (lines 33-50)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in QuoteRequestController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Catering\QuoteRequestResource::collection
**Reason:** the region depends on App\Http\Resources\Catering\QuoteRequestResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Catering/QuoteRequestController.php` :: `App\Http\Resources\Catering\QuoteRequestResource::collection` (lines 33-50)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\SampleMenuController::created
**Reason:** the region calls App\Http\Controllers\Catering\SampleMenuController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/SampleMenuController.php` :: `App\Http\Controllers\Catering\SampleMenuController::created` (lines 16-42)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in SampleMenuController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\SampleMenuController::deleted
**Reason:** the region calls App\Http\Controllers\Catering\SampleMenuController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/SampleMenuController.php` :: `App\Http\Controllers\Catering\SampleMenuController::deleted` (lines 16-42)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in SampleMenuController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\SampleMenuController::success
**Reason:** the region calls App\Http\Controllers\Catering\SampleMenuController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/SampleMenuController.php` :: `App\Http\Controllers\Catering\SampleMenuController::success` (lines 16-42)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in SampleMenuController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Catering\SampleMenuResource::collection
**Reason:** the region depends on App\Http\Resources\Catering\SampleMenuResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Catering/SampleMenuController.php` :: `App\Http\Resources\Catering\SampleMenuResource::collection` (lines 16-42)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\DeliveryAppController::created
**Reason:** the region calls App\Http\Controllers\DeliveryAppController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/DeliveryAppController.php` :: `App\Http\Controllers\DeliveryAppController::created` (lines 17-43)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in DeliveryAppController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\DeliveryAppController::deleted
**Reason:** the region calls App\Http\Controllers\DeliveryAppController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/DeliveryAppController.php` :: `App\Http\Controllers\DeliveryAppController::deleted` (lines 17-43)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in DeliveryAppController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\DeliveryAppController::success
**Reason:** the region calls App\Http\Controllers\DeliveryAppController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/DeliveryAppController.php` :: `App\Http\Controllers\DeliveryAppController::success` (lines 17-43)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in DeliveryAppController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\DeliveryApp\DeliveryAppResource::collection
**Reason:** the region depends on App\Http\Resources\DeliveryApp\DeliveryAppResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/DeliveryAppController.php` :: `App\Http\Resources\DeliveryApp\DeliveryAppResource::collection` (lines 17-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\DishController::created
**Reason:** the region calls App\Http\Controllers\DishController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/DishController.php` :: `App\Http\Controllers\DishController::created` (lines 29-60)
**Tokens:** 62

```text
ASSUMPTION: created() is not declared in DishController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\DishController::deleted
**Reason:** the region calls App\Http\Controllers\DishController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/DishController.php` :: `App\Http\Controllers\DishController::deleted` (lines 29-60)
**Tokens:** 62

```text
ASSUMPTION: deleted() is not declared in DishController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\DishController::success
**Reason:** the region calls App\Http\Controllers\DishController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/DishController.php` :: `App\Http\Controllers\DishController::success` (lines 29-60)
**Tokens:** 61

```text
ASSUMPTION: success() is not declared in DishController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\MediaItemController::created
**Reason:** the region calls App\Http\Controllers\MediaItemController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/MediaItemController.php` :: `App\Http\Controllers\MediaItemController::created` (lines 25-56)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in MediaItemController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\MediaItemController::deleted
**Reason:** the region calls App\Http\Controllers\MediaItemController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/MediaItemController.php` :: `App\Http\Controllers\MediaItemController::deleted` (lines 25-56)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in MediaItemController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\MediaItemController::success
**Reason:** the region calls App\Http\Controllers\MediaItemController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/MediaItemController.php` :: `App\Http\Controllers\MediaItemController::success` (lines 25-56)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in MediaItemController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\PageContentController::success
**Reason:** the region calls App\Http\Controllers\PageContentController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/PageContentController.php` :: `App\Http\Controllers\PageContentController::success` (lines 27-51)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in PageContentController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\SettingController::success
**Reason:** the region calls App\Http\Controllers\SettingController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/SettingController.php` :: `App\Http\Controllers\SettingController::success` (lines 18-30)
**Tokens:** 62

```text
ASSUMPTION: success() is not declared in SettingController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\TestimonialController::created
**Reason:** the region calls App\Http\Controllers\TestimonialController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/TestimonialController.php` :: `App\Http\Controllers\TestimonialController::created` (lines 17-43)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in TestimonialController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\TestimonialController::deleted
**Reason:** the region calls App\Http\Controllers\TestimonialController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/TestimonialController.php` :: `App\Http\Controllers\TestimonialController::deleted` (lines 17-43)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in TestimonialController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\TestimonialController::success
**Reason:** the region calls App\Http\Controllers\TestimonialController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/TestimonialController.php` :: `App\Http\Controllers\TestimonialController::success` (lines 17-43)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in TestimonialController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Testimonial\TestimonialResource::collection
**Reason:** the region depends on App\Http\Resources\Testimonial\TestimonialResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/TestimonialController.php` :: `App\Http\Resources\Testimonial\TestimonialResource::collection` (lines 17-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\TimelineController::created
**Reason:** the region calls App\Http\Controllers\TimelineController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/TimelineController.php` :: `App\Http\Controllers\TimelineController::created` (lines 15-41)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in TimelineController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\TimelineController::deleted
**Reason:** the region calls App\Http\Controllers\TimelineController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/TimelineController.php` :: `App\Http\Controllers\TimelineController::deleted` (lines 15-41)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in TimelineController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\TimelineController::success
**Reason:** the region calls App\Http\Controllers\TimelineController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/TimelineController.php` :: `App\Http\Controllers\TimelineController::success` (lines 15-41)
**Tokens:** 62

```text
ASSUMPTION: success() is not declared in TimelineController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Timeline\TimelineResource::collection
**Reason:** the region depends on App\Http\Resources\Timeline\TimelineResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/TimelineController.php` :: `App\Http\Resources\Timeline\TimelineResource::collection` (lines 15-41)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\UserController::created
**Reason:** the region calls App\Http\Controllers\UserController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/UserController.php` :: `App\Http\Controllers\UserController::created` (lines 20-51)
**Tokens:** 62

```text
ASSUMPTION: created() is not declared in UserController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\UserController::deleted
**Reason:** the region calls App\Http\Controllers\UserController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/UserController.php` :: `App\Http\Controllers\UserController::deleted` (lines 20-51)
**Tokens:** 62

```text
ASSUMPTION: deleted() is not declared in UserController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\UserController::paginated
**Reason:** the region calls App\Http\Controllers\UserController::paginated, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/UserController.php` :: `App\Http\Controllers\UserController::paginated` (lines 20-51)
**Tokens:** 62

```text
ASSUMPTION: paginated() is not declared in UserController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:38, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\UserController::success
**Reason:** the region calls App\Http\Controllers\UserController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/UserController.php` :: `App\Http\Controllers\UserController::success` (lines 20-51)
**Tokens:** 61

```text
ASSUMPTION: success() is not declared in UserController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\User\UserResource::collection
**Reason:** the region depends on App\Http\Resources\User\UserResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/UserController.php` :: `App\Http\Resources\User\UserResource::collection` (lines 20-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Subject:** App\Http\Resources\Auth\AuthResource
**Reason:** the region depends on App\Http\Resources\Auth\AuthResource, whose contract is defined in another file
**Source:** `app/Http/Resources/Auth/AuthResource.php` :: `toArray` (lines 10-21)
**Tokens:** 102

```php
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->resource['token'],
            'user' => [
                'id' => $this->resource['user']->id,
                'name' => $this->resource['user']->name,
                'email' => $this->resource['user']->email,
                'role' => $this->resource['user']->role,
            ],
        ];
    }
```

## fetched · named_reference

**Subject:** App\Http\Resources\Branch\BranchResource
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

**Subject:** App\Http\Resources\Category\CategoryResource
**Reason:** the region depends on App\Http\Resources\Category\CategoryResource, whose contract is defined in another file
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

**Subject:** App\Http\Resources\Catering\PackageResource
**Reason:** the region depends on App\Http\Resources\Catering\PackageResource, whose contract is defined in another file
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

**Subject:** App\Http\Resources\Catering\QuoteRequestResource
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

**Subject:** App\Http\Resources\Catering\SampleMenuResource
**Reason:** the region depends on App\Http\Resources\Catering\SampleMenuResource, whose contract is defined in another file
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

**Subject:** App\Http\Resources\DeliveryApp\DeliveryAppResource
**Reason:** the region depends on App\Http\Resources\DeliveryApp\DeliveryAppResource, whose contract is defined in another file
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

**Subject:** App\Http\Resources\Dish\DishCollection
**Reason:** the region depends on App\Http\Resources\Dish\DishCollection, whose contract is defined in another file
**Source:** `app/Http/Resources/Dish/DishCollection.php` :: `collects` (lines 10-10)
**Tokens:** 11

```php
    public $collects = DishResource::class;
```

## fetched · named_reference

**Subject:** App\Http\Resources\Dish\DishCollection
**Reason:** the region depends on App\Http\Resources\Dish\DishCollection, whose contract is defined in another file
**Source:** `app/Http/Resources/Dish/DishCollection.php` :: `toArray` (lines 12-23)
**Tokens:** 93

```php
    public function toArray(Request $request): array
    {
        return [
            'items' => $this->collection,
            'meta' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
            ],
        ];
    }
```

## fetched · named_reference

**Subject:** App\Http\Resources\Dish\DishResource
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

**Subject:** App\Http\Resources\MediaItem\MediaItemResource
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

**Subject:** App\Http\Resources\PageContent\PageContentResource
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

**Subject:** App\Http\Resources\Setting\SettingResource
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

**Subject:** App\Http\Resources\Testimonial\TestimonialResource
**Reason:** the region depends on App\Http\Resources\Testimonial\TestimonialResource, whose contract is defined in another file
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

**Subject:** App\Http\Resources\Timeline\TimelineResource
**Reason:** the region depends on App\Http\Resources\Timeline\TimelineResource, whose contract is defined in another file
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

**Subject:** App\Http\Resources\User\UserResource
**Reason:** the region depends on App\Http\Resources\User\UserResource, whose contract is defined in another file
**Source:** `app/Http/Resources/User/UserResource.php` :: `toArray` (lines 10-19)
**Tokens:** 75

```php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
```

## fetched · named_reference

**Subject:** App\Repositories\CategoryRepository::getAll
**Reason:** the region depends on App\Repositories\CategoryRepository::getAll, whose contract is defined in another file
**Source:** `app/Repositories/CategoryRepository.php` :: `getAll` (lines 10-13)
**Tokens:** 26

```php
    public function getAll(): Collection
    {
        return Category::orderBy('order')->get();
    }
```

## Dropped

Nothing was dropped.
