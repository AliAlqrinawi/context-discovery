# Context bundle

bundle_version 1 · budget 8000 / used 606 tokens

## flagged · named_reference

**Reason:** the region depends on App\Models\Setting::updateOrCreate, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/MenuPdfController.php` :: `App\Models\Setting::updateOrCreate` (lines 1-85)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Setting::where, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/MenuPdfController.php` :: `App\Models\Setting::where` (lines 1-85)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Reason:** the region depends on App\Models\MediaItem::where, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\MediaItem::where, whose contract is defined in another file
**Source:** `app/Models/MediaItem.php` :: `scopeForPage` (lines 24-35)
**Tokens:** 79

```php
    public function scopeForPage(Builder $query, ?string $page = null, ?string $section = null): Builder
    {
        if ($page !== null) {
            $query->where('page', $page);
        }

        if ($section !== null) {
            $query->where('section', $section);
        }

        return $query;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Setting::updateOrCreate, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\Setting::updateOrCreate, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\User::create, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\User::create, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\User::create, whose contract is defined in another file
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

**Reason:** the region depends on App\Models\MediaItem::where, whose contract is defined in another file
**Source:** `app/Services/MenuQrService.php` :: `App\Models\MediaItem::where` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Setting::updateOrCreate, whose contract is defined in another file
**Source:** `database/seeders/SettingSeeder.php` :: `App\Models\Setting::updateOrCreate` (lines 29-39)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Setting::where, whose contract is defined in another file
**Source:** `routes/web.php` :: `App\Models\Setting::where` (lines 1-45)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\MediaItem::create, whose contract is defined in another file
**Source:** `tests/Feature/MenuPdfTest.php` :: `App\Models\MediaItem::create` (lines 1-154)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Setting::create, whose contract is defined in another file
**Source:** `tests/Feature/MenuPdfTest.php` :: `App\Models\Setting::create` (lines 1-154)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Setting::where, whose contract is defined in another file
**Source:** `tests/Feature/MenuPdfTest.php` :: `App\Models\Setting::where` (lines 1-154)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\User::create, whose contract is defined in another file
**Source:** `tests/Feature/MenuPdfTest.php` :: `App\Models\User::create` (lines 1-154)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Http/Controllers/Admin/MenuPdfController.php` (lines 1-85)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `tests/Feature/MenuPdfTest.php` (lines 1-154)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## Dropped

Nothing was dropped.
