# Context bundle

bundle_version 2 · budget 8000 / used 1393 tokens

## fetched · same_file_symbol_absence

**Subject:** PersonalitySeeder
**Reason:** the region uses PersonalitySeeder, which the file's use block does not import
**Source:** `database/seeders/DatabaseSeeder.php` (lines 5-6)
**Tokens:** 23

```php
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
```

## fetched · same_file_symbol_absence

**Subject:** PersonalitySeeder
**Reason:** the region uses PersonalitySeeder, which the file's use block does not import
**Source:** `database/seeders/DatabaseSeeder.php` :: `run` (lines 12-32)
**Tokens:** 150

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
        ]);
    }
```

## fetched · same_file_reference

**Subject:** apiKey
**Reason:** the region calls the sibling member apiKey, whose contract the diff does not show
**Source:** `tests/Feature/PublicApiTest.php` :: `apiKey` (lines 20-23)
**Tokens:** 25

```php
    private function apiKey(): string
    {
        return config('services.website_api_key');
    }
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\PersonalityController::created
**Reason:** the region calls App\Http\Controllers\Admin\PersonalityController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/PersonalityController.php` :: `App\Http\Controllers\Admin\PersonalityController::created` (lines 1-44)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in PersonalityController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\PersonalityController::deleted
**Reason:** the region calls App\Http\Controllers\Admin\PersonalityController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/PersonalityController.php` :: `App\Http\Controllers\Admin\PersonalityController::deleted` (lines 1-44)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in PersonalityController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\PersonalityController::success
**Reason:** the region calls App\Http\Controllers\Admin\PersonalityController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/PersonalityController.php` :: `App\Http\Controllers\Admin\PersonalityController::success` (lines 1-44)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in PersonalityController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Personality\PersonalityResource::collection
**Reason:** the region depends on App\Http\Resources\Personality\PersonalityResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/PersonalityController.php` :: `App\Http\Resources\Personality\PersonalityResource::collection` (lines 1-44)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Public\PersonalityController::success
**Reason:** the region calls App\Http\Controllers\Public\PersonalityController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Public/PersonalityController.php` :: `App\Http\Controllers\Public\PersonalityController::success` (lines 1-18)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in PersonalityController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Personality\PersonalityResource::collection
**Reason:** the region depends on App\Http\Resources\Personality\PersonalityResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/PersonalityController.php` :: `App\Http\Resources\Personality\PersonalityResource::collection` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Personality\PersonalityResource::resolveLocale
**Reason:** the region calls App\Http\Resources\Personality\PersonalityResource::resolveLocale, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Resources/Personality/PersonalityResource.php` :: `App\Http\Resources\Personality\PersonalityResource::resolveLocale` (lines 1-26)
**Tokens:** 63

```text
ASSUMPTION: resolveLocale() is not declared in PersonalityResource; it is declared in trait App\Http\Resources\Concerns\ResolvesLocale at app/Http/Resources/Concerns/ResolvesLocale.php:7, used by the class itself; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Models\Personality::create
**Reason:** the region depends on App\Models\Personality::create, whose contract is defined in another file
**Source:** `app/Repositories/PersonalityRepository.php` :: `App\Models\Personality::create` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Models\Personality::findOrFail
**Reason:** the region depends on App\Models\Personality::findOrFail, whose contract is defined in another file
**Source:** `app/Repositories/PersonalityRepository.php` :: `App\Models\Personality::findOrFail` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Models\Personality::orderBy
**Reason:** the region depends on App\Models\Personality::orderBy, whose contract is defined in another file
**Source:** `app/Repositories/PersonalityRepository.php` :: `App\Models\Personality::orderBy` (lines 1-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Subject:** App\Services\ImageService
**Reason:** the region depends on App\Services\ImageService, whose contract is defined in another file
**Source:** `app/Services/ImageService.php` :: `ALLOWED_MIMES` (lines 18-18)
**Tokens:** 23

```php
    private const ALLOWED_MIMES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
```

## fetched · named_reference

**Subject:** App\Services\ImageService
**Reason:** the region depends on App\Services\ImageService, whose contract is defined in another file
**Source:** `app/Services/ImageService.php` :: `MAX_SIZE_BYTES` (lines 14-14)
**Tokens:** 13

```php
    private const MAX_SIZE_BYTES = 5 * 1024 * 1024;
```

## fetched · named_reference

**Subject:** App\Services\ImageService
**Reason:** the region depends on App\Services\ImageService, whose contract is defined in another file
**Source:** `app/Services/ImageService.php` :: `MAX_WIDTH` (lines 16-16)
**Tokens:** 9

```php
    private const MAX_WIDTH = 1920;
```

## fetched · named_reference

**Subject:** App\Services\ImageService
**Reason:** the region depends on App\Services\ImageService, whose contract is defined in another file
**Source:** `app/Services/ImageService.php` :: `delete` (lines 53-58)
**Tokens:** 44

```php
    public function delete(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
```

## fetched · named_reference

**Subject:** App\Services\ImageService::delete
**Reason:** the region depends on App\Services\ImageService::delete, whose contract is defined in another file
**Source:** `app/Services/ImageService.php` :: `delete` (lines 53-58)
**Tokens:** 44

```php
    public function delete(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
```

## fetched · named_reference

**Subject:** App\Services\ImageService::store
**Reason:** the region depends on App\Services\ImageService::store, whose contract is defined in another file
**Source:** `app/Services/ImageService.php` :: `store` (lines 20-51)
**Tokens:** 285

```php
    public function store(UploadedFile $file, string $folder): array
    {
        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            throw new InvalidArgumentException('Image exceeds the maximum allowed size of 5MB.');
        }

        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw new InvalidArgumentException('Unsupported image type. Allowed: jpg, jpeg, png, webp.');
        }

        $image = Image::decodeSplFileInfo($file);

        if ($image->width() > self::MAX_WIDTH) {
            $image->scale(width: self::MAX_WIDTH);
        }

        $encoded = $image->encode(new WebpEncoder(quality: 85));

        $filename = Str::uuid()->toString().'.webp';
        $path = trim($folder, '/').'/'.$filename;

        Storage::disk('public')->put($path, (string) $encoded);

        return [
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'width' => $image->width(),
            'height' => $image->height(),
            'size_bytes' => Storage::disk('public')->size($path),
            'mime_type' => 'image/webp',
        ];
    }
```

## fetched · named_reference

**Subject:** App\Services\ImageService
**Reason:** the region depends on App\Services\ImageService, whose contract is defined in another file
**Source:** `app/Services/ImageService.php` :: `store` (lines 20-51)
**Tokens:** 285

```php
    public function store(UploadedFile $file, string $folder): array
    {
        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            throw new InvalidArgumentException('Image exceeds the maximum allowed size of 5MB.');
        }

        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw new InvalidArgumentException('Unsupported image type. Allowed: jpg, jpeg, png, webp.');
        }

        $image = Image::decodeSplFileInfo($file);

        if ($image->width() > self::MAX_WIDTH) {
            $image->scale(width: self::MAX_WIDTH);
        }

        $encoded = $image->encode(new WebpEncoder(quality: 85));

        $filename = Str::uuid()->toString().'.webp';
        $path = trim($folder, '/').'/'.$filename;

        Storage::disk('public')->put($path, (string) $encoded);

        return [
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'width' => $image->width(),
            'height' => $image->height(),
            'size_bytes' => Storage::disk('public')->size($path),
            'mime_type' => 'image/webp',
        ];
    }
```

## flagged · named_reference

**Subject:** App\Models\Personality::updateOrCreate
**Reason:** the region depends on App\Models\Personality::updateOrCreate, whose contract is defined in another file
**Source:** `database/seeders/PersonalitySeeder.php` :: `App\Models\Personality::updateOrCreate` (lines 1-27)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · unverifiable_premise

**Subject:** surrounding-transaction
**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Actions/Personality/DeletePersonalityAction.php` (lines 1-28)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Subject:** surrounding-transaction
**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Actions/Personality/UpdatePersonalityAction.php` (lines 1-46)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Subject:** surrounding-transaction
**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Repositories/PersonalityRepository.php` (lines 1-43)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## Dropped

Nothing was dropped.
