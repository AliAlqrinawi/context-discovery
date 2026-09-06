# Context bundle

bundle_version 1 · budget 8000 / used 494 tokens

## fetched · same_file_symbol_absence

**Reason:** the region uses Order, which the file's use block does not import
**Source:** `app/Models/Coupon.php` (lines 5-8)
**Tokens:** 43

```php
use App\Http\Traits\IsActive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
```

## fetched · same_file_symbol_absence

**Reason:** the region uses Order, which the file's use block does not import
**Source:** `app/Models/Coupon.php` :: `fillable` (lines 13-15)
**Tokens:** 34

```php
    protected $fillable = [
        'code', 'discount', 'type', 'use_limit', 'min_order', 'max_discount', 'expire_at','is_active'
    ];
```

## fetched · same_file_symbol_absence

**Reason:** the region uses CouponController, which the file's use block does not import
**Source:** `routes/api.php` (lines 4-5)
**Tokens:** 17

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\Request, whose contract is defined in another file
**Source:** `app/Http/Resources/CouponResource.php` :: `Illuminate\Http\Request` (lines 1-24)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Validation\ValidationException::withMessages, whose contract is defined in another file
**Source:** `app/Services/CouponService.php` :: `Illuminate\Validation\ValidationException::withMessages` (lines 14-26)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Reason:** the region depends on App\Services\CouponService, whose contract is defined in another file
**Source:** `app/Services/CouponService.php` :: `__construct` (lines 12-15)
**Tokens:** 24

```php
    public function __construct(CouponRepository $repo)
    {
        $this->repo = $repo;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Services\CouponService, whose contract is defined in another file
**Source:** `app/Services/CouponService.php` :: `create` (lines 24-27)
**Tokens:** 24

```php
    public function create($request)
    {
        return $this->repo->create($request);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Services\CouponService, whose contract is defined in another file
**Source:** `app/Services/CouponService.php` :: `delete` (lines 34-37)
**Tokens:** 23

```php
    public function delete($model)
    {
        return $this->repo->delete($model);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Services\CouponService, whose contract is defined in another file
**Source:** `app/Services/CouponService.php` :: `repo` (lines 10-10)
**Tokens:** 5

```php
    protected $repo;
```

## fetched · named_reference

**Reason:** the region depends on App\Services\CouponService, whose contract is defined in another file
**Source:** `app/Services/CouponService.php` :: `update` (lines 29-32)
**Tokens:** 28

```php
    public function update($model ,$request)
    {
        return $this->repo->update($model , $request);
    }
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Schema\Blueprint, whose contract is defined in another file
**Source:** `database/migrations/2024_03_13_120508_create_delivery_types_table.php` :: `Illuminate\Database\Schema\Blueprint` (lines 1-30)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Schema::create, whose contract is defined in another file
**Source:** `database/migrations/2024_03_13_120508_create_delivery_types_table.php` :: `Illuminate\Support\Facades\Schema::create` (lines 1-30)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Schema::dropIfExists, whose contract is defined in another file
**Source:** `database/migrations/2024_03_13_120508_create_delivery_types_table.php` :: `Illuminate\Support\Facades\Schema::dropIfExists` (lines 1-30)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Schema\Blueprint, whose contract is defined in another file
**Source:** `database/migrations/2024_03_13_123628_create_payments_table.php` :: `Illuminate\Database\Schema\Blueprint` (lines 1-30)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Schema::create, whose contract is defined in another file
**Source:** `database/migrations/2024_03_13_123628_create_payments_table.php` :: `Illuminate\Support\Facades\Schema::create` (lines 1-30)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Schema::dropIfExists, whose contract is defined in another file
**Source:** `database/migrations/2024_03_13_123628_create_payments_table.php` :: `Illuminate\Support\Facades\Schema::dropIfExists` (lines 1-30)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::post, whose contract is defined in another file
**Source:** `routes/api.php` :: `Illuminate\Support\Facades\Route::post` (lines 31-37)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · unverifiable_premise

**Reason:** the change removes a trait from a class body; how pre-existing rows behave after it depends on production data state
**Source:** `app/Models/Address.php` (lines 4-14)
**Tokens:** 29

```text
ASSUMPTION: behaviour depends on whether pre-existing rows are affected in production; no backfill migration present
```

## flagged · unverifiable_premise

**Reason:** the change removes a trait from a class body; how pre-existing rows behave after it depends on production data state
**Source:** `app/Models/Coupon.php` (lines 5-27)
**Tokens:** 29

```text
ASSUMPTION: behaviour depends on whether pre-existing rows are affected in production; no backfill migration present
```

## flagged · unverifiable_premise

**Reason:** the change removes a trait from a class body; how pre-existing rows behave after it depends on production data state
**Source:** `app/Models/Driver.php` (lines 5-15)
**Tokens:** 29

```text
ASSUMPTION: behaviour depends on whether pre-existing rows are affected in production; no backfill migration present
```

## flagged · unverifiable_premise

**Reason:** the change removes a trait from a class body; how pre-existing rows behave after it depends on production data state
**Source:** `app/Models/User.php` (lines 8-18)
**Tokens:** 29

```text
ASSUMPTION: behaviour depends on whether pre-existing rows are affected in production; no backfill migration present
```

## Dropped

Nothing was dropped.
