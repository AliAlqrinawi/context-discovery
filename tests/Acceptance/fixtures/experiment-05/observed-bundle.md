# Context bundle

bundle_version 1 · budget 8000 / used 1231 tokens

## fetched · same_file_symbol_absence

**Reason:** the region uses php, which the file's use block does not import
**Source:** `app/Http/Controllers/Admin/MenuPdfController.php` (lines 5-9)
**Tokens:** 41

```php
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
```

## fetched · same_file_symbol_absence

**Reason:** the region uses php, which the file's use block does not import
**Source:** `app/Http/Controllers/Admin/MenuPdfController.php` :: `SETTING_KEY` (lines 13-13)
**Tokens:** 12

```php
    private const SETTING_KEY = 'menu_pdf_path';
```

## fetched · same_file_symbol_absence

**Reason:** the region uses php, which the file's use block does not import
**Source:** `app/Services/MenuQrService.php` (lines 5-10)
**Tokens:** 53

```php
use App\Models\MediaItem;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
```

## fetched · same_file_symbol_absence

**Reason:** the region uses php, which the file's use block does not import
**Source:** `app/Services/MenuQrService.php` :: `LOGO_RATIO` (lines 14-15)
**Tokens:** 32

```php
    /** Share of the QR width the logo may cover; beyond ~0.3 the code stops scanning. */
    private const LOGO_RATIO = 0.25;
```

## fetched · same_file_symbol_absence

**Reason:** the region uses php, which the file's use block does not import
**Source:** `tests/Feature/MenuPdfTest.php` (lines 5-12)
**Tokens:** 61

```php
use App\Models\MediaItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
```

## fetched · same_file_symbol_absence

**Reason:** the region uses php, which the file's use block does not import
**Source:** `tests/Feature/MenuPdfTest.php` :: `setUp` (lines 18-28)
**Tokens:** 84

```php
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        Setting::create([
            'key' => 'menu_pdf_path', 'value' => '', 'type' => 'text', 'group' => 'general',
            'label_ar' => 'ملف قائمة الطعام PDF', 'label_en' => 'Menu PDF File',
        ]);
    }
```

## fetched · same_file_reference

**Reason:** the region calls the sibling member payload, whose contract the diff does not show
**Source:** `app/Http/Controllers/Admin/MenuPdfController.php` :: `payload` (lines 74-84)
**Tokens:** 92

```php
    private function payload(): array
    {
        $path = $this->storedPath();

        return [
            'has_pdf' => $path !== null && Storage::disk('public')->exists($path),
            'permanent_url' => config('app.frontend_url').'/menu/pdf',
            'qr_url' => route('menu.qr'),
            'qr_download' => route('menu.qr.download'),
        ];
    }
```

## fetched · same_file_reference

**Reason:** the region calls the sibling member storedPath, whose contract the diff does not show
**Source:** `app/Http/Controllers/Admin/MenuPdfController.php` :: `storedPath` (lines 67-72)
**Tokens:** 44

```php
    private function storedPath(): ?string
    {
        $path = Setting::where('key', self::SETTING_KEY)->value('value');

        return $path !== '' ? $path : null;
    }
```

## fetched · same_file_reference

**Reason:** the region calls the sibling member logoPath, whose contract the diff does not show
**Source:** `app/Services/MenuQrService.php` :: `logoPath` (lines 38-50)
**Tokens:** 96

```php
    private function logoPath(): ?string
    {
        $logo = MediaItem::where('page', 'global')
            ->where('section', 'brand')
            ->where('key', 'logo_dark')
            ->first();

        if (! $logo?->path || ! Storage::disk('public')->exists($logo->path)) {
            return null;
        }

        return Storage::disk('public')->path($logo->path);
    }
```

## fetched · same_file_reference

**Reason:** the region calls the sibling member actingAsAdmin, whose contract the diff does not show
**Source:** `tests/Feature/MenuPdfTest.php` :: `actingAsAdmin` (lines 30-36)
**Tokens:** 58

```php
    private function actingAsAdmin(): void
    {
        Sanctum::actingAs(User::create([
            'name' => 'Admin', 'email' => 'admin@example.com',
            'password' => 'secret', 'role' => 'super_admin',
        ]));
    }
```

## fetched · same_file_reference

**Reason:** the region calls the sibling member createLogoFixture, whose contract the diff does not show
**Source:** `tests/Feature/MenuPdfTest.php` :: `createLogoFixture` (lines 143-153)
**Tokens:** 83

```php
    private function createLogoFixture(): string
    {
        $image = imagecreatetruecolor(225, 225);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 40, 40));

        $path = tempnam(sys_get_temp_dir(), 'logo').'.png';
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }
```

## fetched · same_file_reference

**Reason:** the region calls the sibling member pdf, whose contract the diff does not show
**Source:** `tests/Feature/MenuPdfTest.php` :: `pdf` (lines 38-41)
**Tokens:** 39

```php
    private function pdf(string $name = 'menu.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 120, 'application/pdf');
    }
```

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

## flagged · named_reference

**Reason:** the region depends on App\Models\MediaItem::where, whose contract is defined in another file
**Source:** `app/Services/MenuQrService.php` :: `App\Models\MediaItem::where` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Reason:** the region depends on App\Services\MenuQrService, whose contract is defined in another file
**Source:** `app/Services/MenuQrService.php` :: `LOGO_RATIO` (lines 14-15)
**Tokens:** 32

```php
    /** Share of the QR width the logo may cover; beyond ~0.3 the code stops scanning. */
    private const LOGO_RATIO = 0.25;
```

## fetched · named_reference

**Reason:** the region depends on App\Services\MenuQrService, whose contract is defined in another file
**Source:** `app/Services/MenuQrService.php` :: `logoPath` (lines 38-50)
**Tokens:** 96

```php
    private function logoPath(): ?string
    {
        $logo = MediaItem::where('page', 'global')
            ->where('section', 'brand')
            ->where('key', 'logo_dark')
            ->first();

        if (! $logo?->path || ! Storage::disk('public')->exists($logo->path)) {
            return null;
        }

        return Storage::disk('public')->path($logo->path);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Services\MenuQrService, whose contract is defined in another file
**Source:** `app/Services/MenuQrService.php` :: `png` (lines 17-36)
**Tokens:** 190

```php
    public function png(int $size = 500): string
    {
        $arguments = [
            'writer' => new PngWriter(),
            'data' => rtrim(config('app.frontend_url'), '/').'/menu/pdf',
            'errorCorrectionLevel' => ErrorCorrectionLevel::High,
            'size' => $size,
            'margin' => 10,
            'foregroundColor' => new Color(26, 23, 0),
            'backgroundColor' => new Color(246, 239, 223),
        ];

        if ($logoPath = $this->logoPath()) {
            $arguments['logoPath'] = $logoPath;
            $arguments['logoResizeToWidth'] = (int) round($size * self::LOGO_RATIO);
            $arguments['logoPunchoutBackground'] = true;
        }

        return (new Builder(...$arguments))->build()->getString();
    }
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
