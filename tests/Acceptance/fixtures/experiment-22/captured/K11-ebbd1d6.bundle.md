# Context bundle

bundle_version 1 · budget 8000 / used 1468 tokens

## fetched · same_file_symbol_absence

**Reason:** the region uses SeoSettingSeeder, which the file's use block does not import
**Source:** `database/seeders/DatabaseSeeder.php` (lines 5-6)
**Tokens:** 23

```php
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
```

## fetched · same_file_symbol_absence

**Reason:** the region uses TrackingSettingSeeder, which the file's use block does not import
**Source:** `database/seeders/DatabaseSeeder.php` (lines 5-6)
**Tokens:** 23

```php
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
```

## fetched · same_file_symbol_absence

**Reason:** the region uses SeoSettingSeeder, which the file's use block does not import
**Source:** `database/seeders/DatabaseSeeder.php` :: `run` (lines 12-34)
**Tokens:** 170

```php
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            DishSeeder::class,
            BranchSeeder::class,
            CateringPackageSeeder::class,
            SampleMenuSeeder::class,
            TestimonialSeeder::class,
            TimelineSeeder::class,
            PersonalitySeeder::class,
            DeliveryAppSeeder::class,
            PageContentSeeder::class,
            SettingSeeder::class,
            MediaItemSeeder::class,
            SeoSettingSeeder::class,
            TrackingSettingSeeder::class,
        ]);
    }
```

## fetched · same_file_symbol_absence

**Reason:** the region uses TrackingSettingSeeder, which the file's use block does not import
**Source:** `database/seeders/DatabaseSeeder.php` :: `run` (lines 12-34)
**Tokens:** 170

```php
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            DishSeeder::class,
            BranchSeeder::class,
            CateringPackageSeeder::class,
            SampleMenuSeeder::class,
            TestimonialSeeder::class,
            TimelineSeeder::class,
            PersonalitySeeder::class,
            DeliveryAppSeeder::class,
            PageContentSeeder::class,
            SettingSeeder::class,
            MediaItemSeeder::class,
            SeoSettingSeeder::class,
            TrackingSettingSeeder::class,
        ]);
    }
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Cache::tags, whose contract is defined in another file
**Source:** `app/Actions/Seo/BulkUpdateSeoSettingsAction.php` :: `Illuminate\Support\Facades\Cache::tags` (lines 1-21)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Cache::tags, whose contract is defined in another file
**Source:** `app/Actions/Seo/GetEffectiveSeoAction.php` :: `Illuminate\Support\Facades\Cache::tags` (lines 1-70)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Arr::whereNotNull, whose contract is defined in another file
**Source:** `app/Actions/Seo/UpdateSeoSettingAction.php` :: `Illuminate\Support\Arr::whereNotNull` (lines 1-41)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Cache::tags, whose contract is defined in another file
**Source:** `app/Actions/Seo/UpdateSeoSettingAction.php` :: `Illuminate\Support\Facades\Cache::tags` (lines 1-41)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Cache::tags, whose contract is defined in another file
**Source:** `app/Actions/Seo/UpdateTrackingSettingsAction.php` :: `Illuminate\Support\Facades\Cache::tags` (lines 1-30)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\Request, whose contract is defined in another file
**Source:** `app/DTOs/Seo/BulkUpdateSeoSettingsDTO.php` :: `Illuminate\Http\Request` (lines 1-19)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\Request, whose contract is defined in another file
**Source:** `app/DTOs/Seo/UpdateSeoSettingDTO.php` :: `Illuminate\Http\Request` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\Request, whose contract is defined in another file
**Source:** `app/DTOs/Seo/UpdateTrackingSettingsDTO.php` :: `Illuminate\Http\Request` (lines 1-30)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Http\Resources\Seo\SeoSettingResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/SeoSettingController.php` :: `App\Http\Resources\Seo\SeoSettingResource::collection` (lines 1-36)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/SeoSettingController.php` :: `Illuminate\Http\JsonResponse` (lines 1-36)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/TrackingSettingController.php` :: `Illuminate\Http\JsonResponse` (lines 1-26)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\JsonResponse, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/SeoController.php` :: `Illuminate\Http\JsonResponse` (lines 1-46)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\Request, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/SeoController.php` :: `Illuminate\Http\Request` (lines 1-46)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\Request, whose contract is defined in another file
**Source:** `app/Http/Resources/Seo/SeoSettingResource.php` :: `Illuminate\Http\Request` (lines 1-30)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\Request, whose contract is defined in another file
**Source:** `app/Http/Resources/Seo/TrackingSettingResource.php` :: `Illuminate\Http\Request` (lines 1-21)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
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

**Reason:** the region depends on App\Models\SeoSetting::findOrFail, whose contract is defined in another file
**Source:** `app/Repositories/SeoSettingRepository.php` :: `App\Models\SeoSetting::findOrFail` (lines 1-58)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\SeoSetting::orderByRaw, whose contract is defined in another file
**Source:** `app/Repositories/SeoSettingRepository.php` :: `App\Models\SeoSetting::orderByRaw` (lines 1-58)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\SeoSetting::where, whose contract is defined in another file
**Source:** `app/Repositories/SeoSettingRepository.php` :: `App\Models\SeoSetting::where` (lines 1-58)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\SeoSetting::whereNull, whose contract is defined in another file
**Source:** `app/Repositories/SeoSettingRepository.php` :: `App\Models\SeoSetting::whereNull` (lines 1-58)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Eloquent\Collection, whose contract is defined in another file
**Source:** `app/Repositories/SeoSettingRepository.php` :: `Illuminate\Database\Eloquent\Collection` (lines 1-58)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Arr::only, whose contract is defined in another file
**Source:** `app/Repositories/SeoSettingRepository.php` :: `Illuminate\Support\Arr::only` (lines 1-58)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\DB::transaction, whose contract is defined in another file
**Source:** `app/Repositories/SeoSettingRepository.php` :: `Illuminate\Support\Facades\DB::transaction` (lines 1-58)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\TrackingSetting::create, whose contract is defined in another file
**Source:** `app/Repositories/TrackingSettingRepository.php` :: `App\Models\TrackingSetting::create` (lines 1-21)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\TrackingSetting::first, whose contract is defined in another file
**Source:** `app/Repositories/TrackingSettingRepository.php` :: `App\Models\TrackingSetting::first` (lines 1-21)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Schema\Blueprint, whose contract is defined in another file
**Source:** `database/migrations/2026_07_26_144327_create_seo_settings_table.php` :: `Illuminate\Database\Schema\Blueprint` (lines 1-44)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Schema::create, whose contract is defined in another file
**Source:** `database/migrations/2026_07_26_144327_create_seo_settings_table.php` :: `Illuminate\Support\Facades\Schema::create` (lines 1-44)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Schema::dropIfExists, whose contract is defined in another file
**Source:** `database/migrations/2026_07_26_144327_create_seo_settings_table.php` :: `Illuminate\Support\Facades\Schema::dropIfExists` (lines 1-44)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Schema\Blueprint, whose contract is defined in another file
**Source:** `database/migrations/2026_07_26_144327_create_tracking_settings_table.php` :: `Illuminate\Database\Schema\Blueprint` (lines 1-35)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Schema::create, whose contract is defined in another file
**Source:** `database/migrations/2026_07_26_144327_create_tracking_settings_table.php` :: `Illuminate\Support\Facades\Schema::create` (lines 1-35)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Schema::dropIfExists, whose contract is defined in another file
**Source:** `database/migrations/2026_07_26_144327_create_tracking_settings_table.php` :: `Illuminate\Support\Facades\Schema::dropIfExists` (lines 1-35)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\SeoSetting::updateOrCreate, whose contract is defined in another file
**Source:** `database/seeders/SeoSettingSeeder.php` :: `App\Models\SeoSetting::updateOrCreate` (lines 1-34)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\TrackingSetting::count, whose contract is defined in another file
**Source:** `database/seeders/TrackingSettingSeeder.php` :: `App\Models\TrackingSetting::count` (lines 1-16)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\TrackingSetting::create, whose contract is defined in another file
**Source:** `database/seeders/TrackingSettingSeeder.php` :: `App\Models\TrackingSetting::create` (lines 1-16)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::get, whose contract is defined in another file
**Source:** `routes/admin.php` :: `Illuminate\Support\Facades\Route::get` (lines 164-181)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::post, whose contract is defined in another file
**Source:** `routes/admin.php` :: `Illuminate\Support\Facades\Route::post` (lines 164-181)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::prefix, whose contract is defined in another file
**Source:** `routes/admin.php` :: `Illuminate\Support\Facades\Route::prefix` (lines 164-181)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::put, whose contract is defined in another file
**Source:** `routes/admin.php` :: `Illuminate\Support\Facades\Route::put` (lines 164-181)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::get, whose contract is defined in another file
**Source:** `routes/api.php` :: `Illuminate\Support\Facades\Route::get` (lines 91-99)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::prefix, whose contract is defined in another file
**Source:** `routes/api.php` :: `Illuminate\Support\Facades\Route::prefix` (lines 91-99)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\SeoSetting::where, whose contract is defined in another file
**Source:** `tests/Feature/SeoSettingsTest.php` :: `App\Models\SeoSetting::where` (lines 1-135)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\SeoSetting::whereNull, whose contract is defined in another file
**Source:** `tests/Feature/SeoSettingsTest.php` :: `App\Models\SeoSetting::whereNull` (lines 1-135)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\TrackingSetting::count, whose contract is defined in another file
**Source:** `tests/Feature/SeoSettingsTest.php` :: `App\Models\TrackingSetting::count` (lines 1-135)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\User::where, whose contract is defined in another file
**Source:** `tests/Feature/SeoSettingsTest.php` :: `App\Models\User::where` (lines 1-135)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Repositories/SeoSettingRepository.php` (lines 1-58)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Repositories/TrackingSettingRepository.php` (lines 1-21)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `database/seeders/SeoSettingSeeder.php` (lines 1-34)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## Dropped

Nothing was dropped.
