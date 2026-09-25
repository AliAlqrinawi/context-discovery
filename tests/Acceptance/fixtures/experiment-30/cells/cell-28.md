<!-- cell-28 -->
# Code review · one commit

You are reviewing one commit from a Laravel application (PHP 8.x, Laravel 12). Below, after these instructions, are exactly three files, each between a `===== BEGIN <name> =====`
line and a `===== END <name> =====` line. They are the only material you may use:

| File | What it is |
|---|---|
| `change.diff` | the commit's unified diff — the change under review |
| `context-bundle.md` | excerpts from the repository, prepared automatically for this review: pieces of code the change refers to, and one-line notes where something could not be looked up |
| `context-diagnostics.txt` | the preparation's own log of what it searched for and what it settled without including anything |

You have no repository, no file access outside these three files, no ability to run code, no
web, and no prior knowledge of this project. Answer from the three files alone. If they are not
enough to be sure, say so — that is a legitimate answer.

## Answer these five questions, in this order, in exactly this format

```
Q1: YES | NO | CANNOT_TELL
Q2: <one sentence stating the defect, or ->
Q3: <path/to/File.php::member> | NONE
Q4: HIGH | MEDIUM | LOW
Q5: "<exact quotation>" | NONE_VISIBLE
```

| | Question | How to answer |
|---|---|---|
| **Q1** | Does this change contain a **correctness defect** — code that will behave wrongly at runtime, silently lose or corrupt data, expose the wrong thing, or break an existing behaviour it did not mean to break? | `YES`, `NO`, or `CANNOT_TELL` if the material genuinely does not let you decide |
| **Q2** | If YES, state the defect in **one sentence** naming the mechanism — what goes wrong, under what input or state, and where. | Free text, or `-` |
| **Q3** | What is the **single most important piece of code not shown** in these files that you would need in order to be sure of Q1? | One `path/to/File.php::member` (or a path, or a config key), or `NONE` if the material is sufficient |
| **Q4** | Your confidence in Q1. | `HIGH`, `MEDIUM` or `LOW` |
| **Q5** | Quote the **exact text** from any of the three files that you based Q1 on. | A verbatim quotation — it will be checked mechanically against the files — or `NONE_VISIBLE` |

## Rules

1. Read all of `change.diff` before the other two files, then read those in full.
2. Q2 names a mechanism, not a category. "Possible null handling issue" is not an answer;
   "`update()` passes `$request->input('description')` which is `null` when the field is
   omitted, so an omitted field clears the column" is.
3. Q3 is **one** thing, the most important. If you would want several, pick the one whose
   absence most limits your Q1.
4. Q5 must be a substring of one of the three files below, character for character. Do not paraphrase.
5. Do not propose fixes, do not comment on style, do not summarise the change. Five lines.
6. Answer once. You will not be asked to revise.

---

===== BEGIN change.diff =====
diff --git a/app/Http/Controllers/Admin/MenuPdfController.php b/app/Http/Controllers/Admin/MenuPdfController.php
new file mode 100644
index 0000000..d3080a2
--- /dev/null
+++ b/app/Http/Controllers/Admin/MenuPdfController.php
@@ -0,0 +1,85 @@
+<?php
+
+namespace App\Http\Controllers\Admin;
+
+use App\Http\Controllers\Controller;
+use App\Models\Setting;
+use Illuminate\Http\JsonResponse;
+use Illuminate\Http\Request;
+use Illuminate\Support\Facades\Storage;
+
+class MenuPdfController extends Controller
+{
+    private const SETTING_KEY = 'menu_pdf_path';
+
+    private const STORAGE_PATH = 'menu/menu.pdf';
+
+    public function show(): JsonResponse
+    {
+        return $this->success($this->payload(), __('messages.fetched'));
+    }
+
+    public function upload(Request $request): JsonResponse
+    {
+        $request->validate([
+            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
+        ]);
+
+        $oldPath = $this->storedPath();
+
+        if ($oldPath && $oldPath !== self::STORAGE_PATH && Storage::disk('public')->exists($oldPath)) {
+            Storage::disk('public')->delete($oldPath);
+        }
+
+        // Fixed filename so re-uploads overwrite in place and the public URL never moves.
+        $path = $request->file('pdf')->storeAs('menu', 'menu.pdf', 'public');
+
+        Setting::updateOrCreate(
+            ['key' => self::SETTING_KEY],
+            [
+                'value' => $path,
+                'type' => 'text',
+                'group' => 'general',
+                'label_ar' => 'ملف قائمة الطعام PDF',
+                'label_en' => 'Menu PDF File',
+            ],
+        );
+
+        return $this->success($this->payload(), __('messages.uploaded'));
+    }
+
+    public function destroy(): JsonResponse
+    {
+        $path = $this->storedPath();
+
+        if ($path && Storage::disk('public')->exists($path)) {
+            Storage::disk('public')->delete($path);
+        }
+
+        Setting::updateOrCreate(
+            ['key' => self::SETTING_KEY],
+            ['value' => '', 'type' => 'text', 'group' => 'general'],
+        );
+
+        return $this->deleted(__('messages.deleted'));
+    }
+
+    private function storedPath(): ?string
+    {
+        $path = Setting::where('key', self::SETTING_KEY)->value('value');
+
+        return $path !== '' ? $path : null;
+    }
+
+    private function payload(): array
+    {
+        $path = $this->storedPath();
+
+        return [
+            'has_pdf' => $path !== null && Storage::disk('public')->exists($path),
+            'permanent_url' => route('menu.pdf'),
+            'qr_url' => route('menu.qr'),
+            'qr_download' => route('menu.qr.download'),
+        ];
+    }
+}
diff --git a/app/Services/MenuQrService.php b/app/Services/MenuQrService.php
new file mode 100644
index 0000000..9e8964e
--- /dev/null
+++ b/app/Services/MenuQrService.php
@@ -0,0 +1,51 @@
+<?php
+
+namespace App\Services;
+
+use App\Models\MediaItem;
+use Endroid\QrCode\Builder\Builder;
+use Endroid\QrCode\Color\Color;
+use Endroid\QrCode\ErrorCorrectionLevel;
+use Endroid\QrCode\Writer\PngWriter;
+use Illuminate\Support\Facades\Storage;
+
+class MenuQrService
+{
+    /** Share of the QR width the logo may cover; beyond ~0.3 the code stops scanning. */
+    private const LOGO_RATIO = 0.25;
+
+    public function png(int $size = 500): string
+    {
+        $arguments = [
+            'writer' => new PngWriter(),
+            'data' => route('menu.pdf'),
+            'errorCorrectionLevel' => ErrorCorrectionLevel::High,
+            'size' => $size,
+            'margin' => 10,
+            'foregroundColor' => new Color(26, 23, 0),
+            'backgroundColor' => new Color(246, 239, 223),
+        ];
+
+        if ($logoPath = $this->logoPath()) {
+            $arguments['logoPath'] = $logoPath;
+            $arguments['logoResizeToWidth'] = (int) round($size * self::LOGO_RATIO);
+            $arguments['logoPunchoutBackground'] = true;
+        }
+
+        return (new Builder(...$arguments))->build()->getString();
+    }
+
+    private function logoPath(): ?string
+    {
+        $logo = MediaItem::where('page', 'global')
+            ->where('section', 'brand')
+            ->where('key', 'logo_dark')
+            ->first();
+
+        if (! $logo?->path || ! Storage::disk('public')->exists($logo->path)) {
+            return null;
+        }
+
+        return Storage::disk('public')->path($logo->path);
+    }
+}
diff --git a/composer.json b/composer.json
index 22f512c..e918d6c 100644
--- a/composer.json
+++ b/composer.json
@@ -8,6 +8,7 @@
     "require": {
         "php": "^8.2",
         "dedoc/scramble": "^0.13.35",
+        "endroid/qr-code": "^6.1",
         "intervention/image-laravel": "^4.0",
         "laravel/framework": "^12.0",
         "laravel/sanctum": "^4.3",
diff --git a/composer.lock b/composer.lock
index 0c7e622..3e15ab9 100644
--- a/composer.lock
+++ b/composer.lock
@@ -4,8 +4,63 @@
         "Read more about it at https://getcomposer.org/doc/01-basic-usage.md#installing-dependencies",
         "This file is @generated automatically"
     ],
-    "content-hash": "dff99565e3ff109e79a99c0b7f2355c8",
+    "content-hash": "1bfb9fd34baa28b29ca3a908ea004b57",
     "packages": [
+        {
+            "name": "bacon/bacon-qr-code",
+            "version": "v3.1.1",
+            "source": {
+                "type": "git",
+                "url": "https://github.com/Bacon/BaconQrCode.git",
+                "reference": "4da2233e72eeecd9be3b62e0dc2cc9ed8e2e31c2"
+            },
+            "dist": {
+                "type": "zip",
+                "url": "https://api.github.com/repos/Bacon/BaconQrCode/zipball/4da2233e72eeecd9be3b62e0dc2cc9ed8e2e31c2",
+                "reference": "4da2233e72eeecd9be3b62e0dc2cc9ed8e2e31c2",
+                "shasum": ""
+            },
+            "require": {
+                "dasprid/enum": "^1.0.3",
+                "ext-iconv": "*",
+                "php": "^8.1"
+            },
+            "require-dev": {
+                "phly/keep-a-changelog": "^2.12",
+                "phpunit/phpunit": "^10.5.11 || ^11.0.4",
+                "spatie/phpunit-snapshot-assertions": "^5.1.5",
+                "spatie/pixelmatch-php": "^1.2.0",
+                "squizlabs/php_codesniffer": "^3.9"
+            },
+            "suggest": {
+                "ext-imagick": "to generate QR code images"
+            },
+            "type": "library",
+            "autoload": {
+                "psr-4": {
+                    "BaconQrCode\\": "src/"
+                }
+            },
+            "notification-url": "https://packagist.org/downloads/",
+            "license": [
+                "BSD-2-Clause"
+            ],
+            "authors": [
+                {
+                    "name": "Ben Scholzen 'DASPRiD'",
+                    "email": "mail@dasprids.de",
+                    "homepage": "https://dasprids.de/",
+                    "role": "Developer"
+                }
+            ],
+            "description": "BaconQrCode is a QR code generator for PHP.",
+            "homepage": "https://github.com/Bacon/BaconQrCode",
+            "support": {
+                "issues": "https://github.com/Bacon/BaconQrCode/issues",
+                "source": "https://github.com/Bacon/BaconQrCode/tree/v3.1.1"
+            },
+            "time": "2026-04-05T21:06:35+00:00"
+        },
         {
             "name": "brick/math",
             "version": "0.14.8",
@@ -135,6 +190,56 @@
             ],
             "time": "2024-02-09T16:56:22+00:00"
         },
+        {
+            "name": "dasprid/enum",
+            "version": "1.0.7",
+            "source": {
+                "type": "git",
+                "url": "https://github.com/DASPRiD/Enum.git",
+                "reference": "b5874fa9ed0043116c72162ec7f4fb50e02e7cce"
+            },
+            "dist": {
+                "type": "zip",
+                "url": "https://api.github.com/repos/DASPRiD/Enum/zipball/b5874fa9ed0043116c72162ec7f4fb50e02e7cce",
+                "reference": "b5874fa9ed0043116c72162ec7f4fb50e02e7cce",
+                "shasum": ""
+            },
+            "require": {
+                "php": ">=7.1 <9.0"
+            },
+            "require-dev": {
+                "phpunit/phpunit": "^7 || ^8 || ^9 || ^10 || ^11",
+                "squizlabs/php_codesniffer": "*"
+            },
+            "type": "library",
+            "autoload": {
+                "psr-4": {
+                    "DASPRiD\\Enum\\": "src/"
+                }
+            },
+            "notification-url": "https://packagist.org/downloads/",
+            "license": [
+                "BSD-2-Clause"
+            ],
+            "authors": [
+                {
+                    "name": "Ben Scholzen 'DASPRiD'",
+                    "email": "mail@dasprids.de",
+                    "homepage": "https://dasprids.de/",
+                    "role": "Developer"
+                }
+            ],
+            "description": "PHP 7.1 enum implementation",
+            "keywords": [
+                "enum",
+                "map"
+            ],
+            "support": {
+                "issues": "https://github.com/DASPRiD/Enum/issues",
+                "source": "https://github.com/DASPRiD/Enum/tree/1.0.7"
+            },
+            "time": "2025-09-16T12:23:56+00:00"
+        },
         {
             "name": "dedoc/scramble",
             "version": "v0.13.35",
@@ -589,6 +694,78 @@
             ],
             "time": "2025-03-06T22:45:56+00:00"
         },
+        {
+            "name": "endroid/qr-code",
+            "version": "6.1.3",
+            "source": {
+                "type": "git",
+                "url": "https://github.com/endroid/qr-code.git",
+                "reference": "5fa534856ed95649d67c0eab0cabc03ab1d8e0e2"
+            },
+            "dist": {
+                "type": "zip",
+                "url": "https://api.github.com/repos/endroid/qr-code/zipball/5fa534856ed95649d67c0eab0cabc03ab1d8e0e2",
+                "reference": "5fa534856ed95649d67c0eab0cabc03ab1d8e0e2",
+                "shasum": ""
+            },
+            "require": {
+                "bacon/bacon-qr-code": "^3.0",
+                "php": "^8.4"
+            },
+            "require-dev": {
+                "endroid/quality": "dev-main",
+                "ext-gd": "*",
+                "khanamiryan/qrcode-detector-decoder": "^2.0.3",
+                "setasign/fpdf": "^1.8.2"
+            },
+            "suggest": {
+                "ext-gd": "Enables you to write PNG images",
+                "khanamiryan/qrcode-detector-decoder": "Enables you to use the image validator",
+                "roave/security-advisories": "Makes sure package versions with known security issues are not installed",
+                "setasign/fpdf": "Enables you to use the PDF writer"
+            },
+            "type": "library",
+            "extra": {
+                "branch-alias": {
+                    "dev-main": "6.x-dev"
+                }
+            },
+            "autoload": {
+                "psr-4": {
+                    "Endroid\\QrCode\\": "src/"
+                }
+            },
+            "notification-url": "https://packagist.org/downloads/",
+            "license": [
+                "MIT"
+            ],
+            "authors": [
+                {
+                    "name": "Jeroen van den Enden",
+                    "email": "info@endroid.nl"
+                }
+            ],
+            "description": "Endroid QR Code",
+            "homepage": "https://github.com/endroid/qr-code",
+            "keywords": [
+                "code",
+                "endroid",
+                "php",
+                "qr",
+                "qrcode"
+            ],
+            "support": {
+                "issues": "https://github.com/endroid/qr-code/issues",
+                "source": "https://github.com/endroid/qr-code/tree/6.1.3"
+            },
+            "funding": [
+                {
+                    "url": "https://github.com/endroid",
+                    "type": "github"
+                }
+            ],
+            "time": "2026-02-05T07:01:58+00:00"
+        },
         {
             "name": "fruitcake/php-cors",
             "version": "v1.4.0",
diff --git a/database/seeders/SettingSeeder.php b/database/seeders/SettingSeeder.php
index 85d0338..f068d73 100644
--- a/database/seeders/SettingSeeder.php
+++ b/database/seeders/SettingSeeder.php
@@ -29,10 +29,11 @@ public function run(): void
             ['key' => 'footer_copyright', 'value' => '© 2026 أبو السيد. جميع الحقوق محفوظة.', 'type' => 'text', 'group' => 'footer', 'label_ar' => 'نص الحقوق', 'label_en' => 'Copyright Text'],
             ['key' => 'navbar_cta_ar', 'value' => 'اطلب أونلاين', 'type' => 'text', 'group' => 'navbar', 'label_ar' => 'زر الهيدر (عربي)', 'label_en' => 'Navbar CTA (Arabic)'],
             ['key' => 'navbar_cta_en', 'value' => 'Order Online', 'type' => 'text', 'group' => 'navbar', 'label_ar' => 'زر الهيدر (إنجليزي)', 'label_en' => 'Navbar CTA (English)'],
+            ['key' => 'menu_pdf_path', 'value' => '', 'type' => 'text', 'group' => 'general', 'label_ar' => 'ملف قائمة الطعام PDF', 'label_en' => 'Menu PDF File'],
         ];
 
         foreach ($settings as $setting) {
-            Setting::create($setting);
+            Setting::updateOrCreate(['key' => $setting['key']], $setting);
         }
     }
 }
diff --git a/routes/admin.php b/routes/admin.php
index d98221f..20f4557 100644
--- a/routes/admin.php
+++ b/routes/admin.php
@@ -10,6 +10,7 @@
 use App\Http\Controllers\Admin\DeliveryAppController;
 use App\Http\Controllers\Admin\DishController;
 use App\Http\Controllers\Admin\MediaItemController;
+use App\Http\Controllers\Admin\MenuPdfController;
 use App\Http\Controllers\Admin\PageContentController;
 use App\Http\Controllers\Admin\PersonalityController;
 use App\Http\Controllers\Admin\ProfileController;
@@ -177,5 +178,12 @@
             Route::get('/', 'show');
             Route::put('/', 'update');
         });
+
+        // ── Menu PDF ──────────────────────────────────────
+        Route::prefix('menu')->controller(MenuPdfController::class)->group(function () {
+            Route::get('pdf',    'show');
+            Route::post('pdf',   'upload');
+            Route::delete('pdf', 'destroy');
+        });
     });
 });
diff --git a/routes/web.php b/routes/web.php
index 86a06c5..8832b7a 100644
--- a/routes/web.php
+++ b/routes/web.php
@@ -1,7 +1,45 @@
 <?php
 
+use App\Models\Setting;
+use App\Services\MenuQrService;
 use Illuminate\Support\Facades\Route;
+use Illuminate\Support\Facades\Storage;
 
 Route::get('/', function () {
     return view('welcome');
 });
+
+// Permanent menu PDF URL — the target the printed QR code points at, so it must
+// keep working across re-uploads. The stored path is looked up per request.
+Route::get('/menu/pdf', function () {
+    $path = Setting::where('key', 'menu_pdf_path')->value('value');
+
+    if (! $path || ! Storage::disk('public')->exists($path)) {
+        abort(404, 'Menu PDF not available yet.');
+    }
+
+    return response()->file(
+        Storage::disk('public')->path($path),
+        [
+            'Content-Type' => 'application/pdf',
+            'Content-Disposition' => 'inline; filename="abouelsid-menu.pdf"',
+        ],
+    );
+})->name('menu.pdf');
+
+// QR code pointing at the permanent PDF URL, rendered on demand so it always
+// reflects the current brand logo.
+Route::get('/menu/qr', function (MenuQrService $qr) {
+    return response($qr->png(500), 200, [
+        'Content-Type' => 'image/png',
+        'Cache-Control' => 'public, max-age=3600',
+    ]);
+})->name('menu.qr');
+
+// High-resolution variant for print.
+Route::get('/menu/qr/download', function (MenuQrService $qr) {
+    return response($qr->png(1000), 200, [
+        'Content-Type' => 'image/png',
+        'Content-Disposition' => 'attachment; filename="abouelsid-menu-qr.png"',
+    ]);
+})->name('menu.qr.download');
diff --git a/tests/Feature/MenuPdfTest.php b/tests/Feature/MenuPdfTest.php
new file mode 100644
index 0000000..48f635c
--- /dev/null
+++ b/tests/Feature/MenuPdfTest.php
@@ -0,0 +1,154 @@
+<?php
+
+namespace Tests\Feature;
+
+use App\Models\MediaItem;
+use App\Models\Setting;
+use App\Models\User;
+use Illuminate\Foundation\Testing\RefreshDatabase;
+use Illuminate\Http\UploadedFile;
+use Illuminate\Support\Facades\Storage;
+use Laravel\Sanctum\Sanctum;
+use Tests\TestCase;
+
+class MenuPdfTest extends TestCase
+{
+    use RefreshDatabase;
+
+    protected function setUp(): void
+    {
+        parent::setUp();
+
+        Storage::fake('public');
+
+        Setting::create([
+            'key' => 'menu_pdf_path', 'value' => '', 'type' => 'text', 'group' => 'general',
+            'label_ar' => 'ملف قائمة الطعام PDF', 'label_en' => 'Menu PDF File',
+        ]);
+    }
+
+    private function actingAsAdmin(): void
+    {
+        Sanctum::actingAs(User::create([
+            'name' => 'Admin', 'email' => 'admin@example.com',
+            'password' => 'secret', 'role' => 'super_admin',
+        ]));
+    }
+
+    private function pdf(string $name = 'menu.pdf'): UploadedFile
+    {
+        return UploadedFile::fake()->create($name, 120, 'application/pdf');
+    }
+
+    public function test_permanent_url_returns_404_before_any_upload(): void
+    {
+        $this->get('/menu/pdf')->assertNotFound();
+    }
+
+    public function test_admin_can_upload_and_the_permanent_url_serves_the_pdf(): void
+    {
+        $this->actingAsAdmin();
+
+        $this->postJson('/api/v1/admin/menu/pdf', ['pdf' => $this->pdf()])
+            ->assertOk()
+            ->assertJsonPath('data.has_pdf', true)
+            ->assertJsonPath('data.permanent_url', url('/menu/pdf'));
+
+        Storage::disk('public')->assertExists('menu/menu.pdf');
+
+        $this->get('/menu/pdf')
+            ->assertOk()
+            ->assertHeader('content-type', 'application/pdf');
+    }
+
+    public function test_reupload_keeps_the_same_permanent_url(): void
+    {
+        $this->actingAsAdmin();
+
+        $first = $this->postJson('/api/v1/admin/menu/pdf', ['pdf' => $this->pdf()])
+            ->json('data.permanent_url');
+
+        $second = $this->postJson('/api/v1/admin/menu/pdf', ['pdf' => $this->pdf('updated.pdf')])
+            ->json('data.permanent_url');
+
+        $this->assertSame($first, $second);
+        $this->assertSame('menu/menu.pdf', Setting::where('key', 'menu_pdf_path')->value('value'));
+    }
+
+    public function test_upload_rejects_non_pdf(): void
+    {
+        $this->actingAsAdmin();
+
+        $this->postJson('/api/v1/admin/menu/pdf', ['pdf' => UploadedFile::fake()->image('menu.jpg')])
+            ->assertStatus(422)
+            ->assertJsonValidationErrors('pdf');
+    }
+
+    public function test_upload_requires_authentication(): void
+    {
+        $this->postJson('/api/v1/admin/menu/pdf', ['pdf' => $this->pdf()])->assertUnauthorized();
+    }
+
+    public function test_destroy_removes_the_file_and_the_url_404s_again(): void
+    {
+        $this->actingAsAdmin();
+        $this->postJson('/api/v1/admin/menu/pdf', ['pdf' => $this->pdf()]);
+
+        $this->deleteJson('/api/v1/admin/menu/pdf')->assertOk();
+
+        Storage::disk('public')->assertMissing('menu/menu.pdf');
+        $this->get('/menu/pdf')->assertNotFound();
+    }
+
+    public function test_qr_route_returns_a_png(): void
+    {
+        $response = $this->get('/menu/qr');
+
+        $response->assertOk()->assertHeader('content-type', 'image/png');
+
+        $info = getimagesizefromstring($response->getContent());
+        $this->assertSame('image/png', $info['mime']);
+    }
+
+    public function test_qr_download_is_larger_and_sent_as_attachment(): void
+    {
+        $view = getimagesizefromstring($this->get('/menu/qr')->getContent());
+        $download = $this->get('/menu/qr/download');
+
+        $download->assertOk()
+            ->assertHeader('content-disposition', 'attachment; filename="abouelsid-menu-qr.png"');
+
+        $this->assertGreaterThan($view[0], getimagesizefromstring($download->getContent())[0]);
+    }
+
+    public function test_qr_embeds_the_brand_logo_when_present(): void
+    {
+        $withoutLogo = strlen($this->get('/menu/qr')->getContent());
+
+        Storage::disk('public')->put('media/global/logo.png', file_get_contents(
+            $this->createLogoFixture(),
+        ));
+
+        MediaItem::create([
+            'page' => 'global', 'section' => 'brand', 'key' => 'logo_dark',
+            'path' => 'media/global/logo.png', 'url' => '/storage/media/global/logo.png',
+            'alt_ar' => 'شعار', 'alt_en' => 'Logo',
+        ]);
+
+        $withLogo = strlen($this->get('/menu/qr')->getContent());
+
+        $this->assertNotSame($withoutLogo, $withLogo);
+    }
+
+    private function createLogoFixture(): string
+    {
+        $image = imagecreatetruecolor(225, 225);
+        imagefill($image, 0, 0, imagecolorallocate($image, 200, 40, 40));
+
+        $path = tempnam(sys_get_temp_dir(), 'logo').'.png';
+        imagepng($image, $path);
+        imagedestroy($image);
+
+        return $path;
+    }
+}
diff --git a/tests/Feature/RepositoriesTest.php b/tests/Feature/RepositoriesTest.php
index 0b1c4a6..288140c 100644
--- a/tests/Feature/RepositoriesTest.php
+++ b/tests/Feature/RepositoriesTest.php
@@ -115,8 +115,9 @@ public function test_setting_repository_get_all_keyed_by_key(): void
 
         $result = $repo->getAll();
 
-        $this->assertCount(19, $result);
+        $this->assertCount(20, $result);
         $this->assertTrue($result->has('whatsapp_number'));
+        $this->assertTrue($result->has('menu_pdf_path'));
     }
 
     public function test_setting_repository_get_all_filters_by_group(): void
===== END change.diff =====

===== BEGIN context-bundle.md =====
# Context bundle

bundle_version 2 · budget 8000 / used 730 tokens

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\MenuPdfController::deleted
**Reason:** the region calls App\Http\Controllers\Admin\MenuPdfController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/MenuPdfController.php` :: `App\Http\Controllers\Admin\MenuPdfController::deleted` (lines 1-85)
**Tokens:** 62

```text
ASSUMPTION: deleted() is not declared in MenuPdfController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\MenuPdfController::success
**Reason:** the region calls App\Http\Controllers\Admin\MenuPdfController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/MenuPdfController.php` :: `App\Http\Controllers\Admin\MenuPdfController::success` (lines 1-85)
**Tokens:** 62

```text
ASSUMPTION: success() is not declared in MenuPdfController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Models\Setting::updateOrCreate
**Reason:** the region depends on App\Models\Setting::updateOrCreate, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/MenuPdfController.php` :: `App\Models\Setting::updateOrCreate` (lines 1-85)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Models\Setting::where
**Reason:** the region depends on App\Models\Setting::where, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/MenuPdfController.php` :: `App\Models\Setting::where` (lines 1-85)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Subject:** App\Models\MediaItem::where
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

**Subject:** App\Models\MediaItem::where
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

**Subject:** App\Models\Setting::updateOrCreate
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

**Subject:** App\Models\Setting::updateOrCreate
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

**Subject:** App\Models\User::create
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

**Subject:** App\Models\User::create
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

**Subject:** App\Models\User::create
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

**Subject:** App\Models\MediaItem::where
**Reason:** the region depends on App\Models\MediaItem::where, whose contract is defined in another file
**Source:** `app/Services/MenuQrService.php` :: `App\Models\MediaItem::where` (lines 1-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Models\Setting::updateOrCreate
**Reason:** the region depends on App\Models\Setting::updateOrCreate, whose contract is defined in another file
**Source:** `database/seeders/SettingSeeder.php` :: `App\Models\Setting::updateOrCreate` (lines 29-39)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Models\Setting::where
**Reason:** the region depends on App\Models\Setting::where, whose contract is defined in another file
**Source:** `routes/web.php` :: `App\Models\Setting::where` (lines 1-45)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Models\MediaItem::create
**Reason:** the region depends on App\Models\MediaItem::create, whose contract is defined in another file
**Source:** `tests/Feature/MenuPdfTest.php` :: `App\Models\MediaItem::create` (lines 1-154)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Models\Setting::create
**Reason:** the region depends on App\Models\Setting::create, whose contract is defined in another file
**Source:** `tests/Feature/MenuPdfTest.php` :: `App\Models\Setting::create` (lines 1-154)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Models\Setting::where
**Reason:** the region depends on App\Models\Setting::where, whose contract is defined in another file
**Source:** `tests/Feature/MenuPdfTest.php` :: `App\Models\Setting::where` (lines 1-154)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Models\User::create
**Reason:** the region depends on App\Models\User::create, whose contract is defined in another file
**Source:** `tests/Feature/MenuPdfTest.php` :: `App\Models\User::create` (lines 1-154)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · unverifiable_premise

**Subject:** surrounding-transaction
**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Http/Controllers/Admin/MenuPdfController.php` (lines 1-85)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## flagged · unverifiable_premise

**Subject:** surrounding-transaction
**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `tests/Feature/MenuPdfTest.php` (lines 1-154)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## Dropped

Nothing was dropped.
===== END context-bundle.md =====

===== BEGIN context-diagnostics.txt =====
new file: app/Http/Controllers/Admin/MenuPdfController.php — own-file context is in the diff, not fetched
new file: app/Services/MenuQrService.php — own-file context is in the diff, not fetched
new file: tests/Feature/MenuPdfTest.php — own-file context is in the diff, not fetched
inherited member: App\Http\Controllers\Admin\MenuPdfController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\MenuPdfController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
framework reference: Illuminate\Support\Facades\Storage::disk declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Storage.php:11 (@method static \Illuminate\Contracts\Filesystem\Filesystem disk(\UnitEnum|string|null $name = null))
unresolved named_reference: App\Models\Setting::updateOrCreate in app/Http/Controllers/Admin/MenuPdfController.php
unresolved named_reference: App\Models\Setting::where in app/Http/Controllers/Admin/MenuPdfController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
dependency member: Endroid\QrCode\ErrorCorrectionLevel::High declared at vendor/endroid/qr-code/src/ErrorCorrectionLevel.php:9; source not fetched
unresolved named_reference: App\Models\MediaItem::where in app/Services/MenuQrService.php
framework reference: Illuminate\Support\Facades\Storage::disk declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Storage.php:11 (@method static \Illuminate\Contracts\Filesystem\Filesystem disk(\UnitEnum|string|null $name = null))
dependency class: Endroid\QrCode\Writer\PngWriter provided by vendor/endroid/qr-code/src/Writer/PngWriter.php; surface not fetched
dependency class: Endroid\QrCode\Color\Color provided by vendor/endroid/qr-code/src/Color/Color.php; surface not fetched
dependency class: Endroid\QrCode\Builder\Builder provided by vendor/endroid/qr-code/src/Builder/Builder.php; surface not fetched
unresolved named_reference: App\Models\Setting::updateOrCreate in database/seeders/SettingSeeder.php
framework reference: Illuminate\Support\Facades\Route::prefix declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:100 (@method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix))
framework reference: Illuminate\Support\Facades\Route::get declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:6 (@method static \Illuminate\Routing\Route get(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::post declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:7 (@method static \Illuminate\Routing\Route post(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::delete declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:10 (@method static \Illuminate\Routing\Route delete(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::get declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:6 (@method static \Illuminate\Routing\Route get(string $uri, array|string|callable|null $action = null))
unresolved named_reference: App\Models\Setting::where in routes/web.php
framework reference: Illuminate\Support\Facades\Storage::disk declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Storage.php:11 (@method static \Illuminate\Contracts\Filesystem\Filesystem disk(\UnitEnum|string|null $name = null))
already in the diff: App\Services\MenuQrService declared in app/Services/MenuQrService.php; not fetched again
inherited member unresolved: Tests\Feature\MenuPdfTest::get; walked nothing; continues into a dependency, which was not walked (Illuminate\Foundation\Testing\RefreshDatabase)
inherited member unresolved: Tests\Feature\MenuPdfTest::postJson; walked nothing; continues into a dependency, which was not walked (Illuminate\Foundation\Testing\RefreshDatabase)
inherited member unresolved: Tests\Feature\MenuPdfTest::assertSame; walked nothing; continues into a dependency, which was not walked (Illuminate\Foundation\Testing\RefreshDatabase)
inherited member unresolved: Tests\Feature\MenuPdfTest::deleteJson; walked nothing; continues into a dependency, which was not walked (Illuminate\Foundation\Testing\RefreshDatabase)
inherited member unresolved: Tests\Feature\MenuPdfTest::assertGreaterThan; walked nothing; continues into a dependency, which was not walked (Illuminate\Foundation\Testing\RefreshDatabase)
inherited member unresolved: Tests\Feature\MenuPdfTest::assertNotSame; walked nothing; continues into a dependency, which was not walked (Illuminate\Foundation\Testing\RefreshDatabase)
dependency member: Illuminate\Support\Facades\Storage::fake declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Storage.php:94; source not fetched
unresolved named_reference: App\Models\Setting::create in tests/Feature/MenuPdfTest.php
dependency member: Laravel\Sanctum\Sanctum::actingAs declared at vendor/laravel/sanctum/src/Sanctum.php:62; source not fetched
unresolved named_reference: App\Models\User::create in tests/Feature/MenuPdfTest.php
dependency member: Illuminate\Http\UploadedFile::fake declared at vendor/laravel/framework/src/Illuminate/Http/UploadedFile.php:17; source not fetched
framework reference: Illuminate\Support\Facades\Storage::disk declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Storage.php:11 (@method static \Illuminate\Contracts\Filesystem\Filesystem disk(\UnitEnum|string|null $name = null))
unresolved named_reference: App\Models\Setting::where in tests/Feature/MenuPdfTest.php
unresolved named_reference: App\Models\MediaItem::create in tests/Feature/MenuPdfTest.php
dependency class: Illuminate\Http\UploadedFile provided by vendor/laravel/framework/src/Illuminate/Http/UploadedFile.php; surface not fetched
inherited member unresolved: Tests\Feature\RepositoriesTest::assertCount; walked nothing; continues into a dependency, which was not walked (Illuminate\Foundation\Testing\RefreshDatabase)
inherited member unresolved: Tests\Feature\RepositoriesTest::assertTrue; walked nothing; continues into a dependency, which was not walked (Illuminate\Foundation\Testing\RefreshDatabase)
===== END context-diagnostics.txt =====
