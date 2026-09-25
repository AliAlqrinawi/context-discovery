# Context bundle

bundle_version 2 · budget 8000 / used 423 tokens

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\ProfileController::error
**Reason:** the region calls App\Http\Controllers\Admin\ProfileController::error, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/ProfileController.php` :: `App\Http\Controllers\Admin\ProfileController::error` (lines 1-55)
**Tokens:** 62

```text
ASSUMPTION: error() is not declared in ProfileController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:58, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\ProfileController::success
**Reason:** the region calls App\Http\Controllers\Admin\ProfileController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/ProfileController.php` :: `App\Http\Controllers\Admin\ProfileController::success` (lines 1-55)
**Tokens:** 62

```text
ASSUMPTION: success() is not declared in ProfileController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
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

**Subject:** App\Models\User::where
**Reason:** the region depends on App\Models\User::where, whose contract is defined in another file
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

**Subject:** App\Models\User::where
**Reason:** the region depends on App\Models\User::where, whose contract is defined in another file
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

**Subject:** App\Models\User::where
**Reason:** the region depends on App\Models\User::where, whose contract is defined in another file
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

## flagged · named_reference

**Subject:** App\Models\User::factory
**Reason:** the region depends on App\Models\User::factory, whose contract is defined in another file
**Source:** `tests/Feature/ProfileTest.php` :: `App\Models\User::factory` (lines 1-130)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Models\User::where
**Reason:** the region depends on App\Models\User::where, whose contract is defined in another file
**Source:** `tests/Feature/ProfileTest.php` :: `App\Models\User::where` (lines 1-130)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · unverifiable_premise

**Subject:** surrounding-transaction
**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Http/Controllers/Admin/ProfileController.php` (lines 1-55)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## Dropped

Nothing was dropped.
