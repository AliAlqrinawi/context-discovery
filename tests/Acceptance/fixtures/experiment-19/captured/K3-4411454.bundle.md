# Context bundle

bundle_version 1 · budget 8000 / used 299 tokens

## fetched · named_reference

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

**Reason:** the region depends on App\Models\User::factory, whose contract is defined in another file
**Source:** `tests/Feature/ProfileTest.php` :: `App\Models\User::factory` (lines 1-130)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\User::where, whose contract is defined in another file
**Source:** `tests/Feature/ProfileTest.php` :: `App\Models\User::where` (lines 1-130)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Http/Controllers/Admin/ProfileController.php` (lines 1-55)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## Dropped

Nothing was dropped.
