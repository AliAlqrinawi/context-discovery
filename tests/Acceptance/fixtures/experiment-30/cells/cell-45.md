<!-- cell-45 -->
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
diff --git a/app/Actions/Personality/CreatePersonalityAction.php b/app/Actions/Personality/CreatePersonalityAction.php
new file mode 100644
index 0000000..5e6b311
--- /dev/null
+++ b/app/Actions/Personality/CreatePersonalityAction.php
@@ -0,0 +1,39 @@
+<?php
+
+namespace App\Actions\Personality;
+
+use App\DTOs\Personality\CreatePersonalityDTO;
+use App\Models\Personality;
+use App\Repositories\PersonalityRepository;
+use App\Services\ImageService;
+use Illuminate\Support\Facades\Cache;
+
+class CreatePersonalityAction
+{
+    public function __construct(
+        private readonly PersonalityRepository $repository,
+        private readonly ImageService $imageService,
+    ) {}
+
+    public function execute(CreatePersonalityDTO $dto): Personality
+    {
+        $data = [
+            'name_ar' => $dto->name_ar,
+            'name_en' => $dto->name_en,
+            'order' => $dto->order,
+            'is_active' => $dto->is_active,
+        ];
+
+        if ($dto->image) {
+            $stored = $this->imageService->store($dto->image, 'personalities');
+            $data['image_path'] = $stored['path'];
+            $data['image_url'] = $stored['url'];
+        }
+
+        $personality = $this->repository->create($data);
+
+        Cache::tags(['personalities'])->flush();
+
+        return $personality;
+    }
+}
diff --git a/app/Actions/Personality/DeletePersonalityAction.php b/app/Actions/Personality/DeletePersonalityAction.php
new file mode 100644
index 0000000..8d79b25
--- /dev/null
+++ b/app/Actions/Personality/DeletePersonalityAction.php
@@ -0,0 +1,28 @@
+<?php
+
+namespace App\Actions\Personality;
+
+use App\Repositories\PersonalityRepository;
+use App\Services\ImageService;
+use Illuminate\Support\Facades\Cache;
+
+class DeletePersonalityAction
+{
+    public function __construct(
+        private readonly PersonalityRepository $repository,
+        private readonly ImageService $imageService,
+    ) {}
+
+    public function execute(int $id): void
+    {
+        $personality = $this->repository->getById($id);
+
+        if ($personality->image_path) {
+            $this->imageService->delete($personality->image_path);
+        }
+
+        $this->repository->delete($id);
+
+        Cache::tags(['personalities'])->flush();
+    }
+}
diff --git a/app/Actions/Personality/GetPersonalitiesAction.php b/app/Actions/Personality/GetPersonalitiesAction.php
new file mode 100644
index 0000000..105577e
--- /dev/null
+++ b/app/Actions/Personality/GetPersonalitiesAction.php
@@ -0,0 +1,26 @@
+<?php
+
+namespace App\Actions\Personality;
+
+use App\Repositories\PersonalityRepository;
+use Illuminate\Database\Eloquent\Collection;
+use Illuminate\Support\Facades\Cache;
+
+class GetPersonalitiesAction
+{
+    public function __construct(
+        private readonly PersonalityRepository $repository,
+    ) {}
+
+    public function execute(bool $activeOnly = true): Collection
+    {
+        $locale = app()->getLocale();
+        $key = "personalities_{$locale}_".($activeOnly ? 'active' : 'all');
+
+        return Cache::tags(['personalities'])->remember(
+            $key,
+            now()->addHour(),
+            fn () => $this->repository->getAll($activeOnly)
+        );
+    }
+}
diff --git a/app/Actions/Personality/UpdatePersonalityAction.php b/app/Actions/Personality/UpdatePersonalityAction.php
new file mode 100644
index 0000000..1bd60eb
--- /dev/null
+++ b/app/Actions/Personality/UpdatePersonalityAction.php
@@ -0,0 +1,46 @@
+<?php
+
+namespace App\Actions\Personality;
+
+use App\DTOs\Personality\UpdatePersonalityDTO;
+use App\Models\Personality;
+use App\Repositories\PersonalityRepository;
+use App\Services\ImageService;
+use Illuminate\Support\Arr;
+use Illuminate\Support\Facades\Cache;
+
+class UpdatePersonalityAction
+{
+    public function __construct(
+        private readonly PersonalityRepository $repository,
+        private readonly ImageService $imageService,
+    ) {}
+
+    public function execute(int $id, UpdatePersonalityDTO $dto): Personality
+    {
+        $data = Arr::whereNotNull([
+            'name_ar' => $dto->name_ar,
+            'name_en' => $dto->name_en,
+            'order' => $dto->order,
+            'is_active' => $dto->is_active,
+        ]);
+
+        if ($dto->image) {
+            $existing = $this->repository->getById($id);
+
+            if ($existing->image_path) {
+                $this->imageService->delete($existing->image_path);
+            }
+
+            $stored = $this->imageService->store($dto->image, 'personalities');
+            $data['image_path'] = $stored['path'];
+            $data['image_url'] = $stored['url'];
+        }
+
+        $personality = $this->repository->update($id, $data);
+
+        Cache::tags(['personalities'])->flush();
+
+        return $personality;
+    }
+}
diff --git a/app/DTOs/Personality/CreatePersonalityDTO.php b/app/DTOs/Personality/CreatePersonalityDTO.php
new file mode 100644
index 0000000..4a78112
--- /dev/null
+++ b/app/DTOs/Personality/CreatePersonalityDTO.php
@@ -0,0 +1,28 @@
+<?php
+
+namespace App\DTOs\Personality;
+
+use Illuminate\Http\Request;
+use Illuminate\Http\UploadedFile;
+
+class CreatePersonalityDTO
+{
+    public function __construct(
+        public readonly string $name_ar,
+        public readonly string $name_en,
+        public readonly ?UploadedFile $image,
+        public readonly int $order,
+        public readonly bool $is_active,
+    ) {}
+
+    public static function fromRequest(Request $request): self
+    {
+        return new self(
+            name_ar: $request->string('name_ar')->toString(),
+            name_en: $request->string('name_en')->toString(),
+            image: $request->file('image'),
+            order: (int) $request->input('order', 0),
+            is_active: $request->boolean('is_active', true),
+        );
+    }
+}
diff --git a/app/DTOs/Personality/UpdatePersonalityDTO.php b/app/DTOs/Personality/UpdatePersonalityDTO.php
new file mode 100644
index 0000000..93cf3b4
--- /dev/null
+++ b/app/DTOs/Personality/UpdatePersonalityDTO.php
@@ -0,0 +1,28 @@
+<?php
+
+namespace App\DTOs\Personality;
+
+use Illuminate\Http\Request;
+use Illuminate\Http\UploadedFile;
+
+class UpdatePersonalityDTO
+{
+    public function __construct(
+        public readonly ?string $name_ar,
+        public readonly ?string $name_en,
+        public readonly ?UploadedFile $image,
+        public readonly ?int $order,
+        public readonly ?bool $is_active,
+    ) {}
+
+    public static function fromRequest(Request $request): self
+    {
+        return new self(
+            name_ar: $request->filled('name_ar') ? $request->string('name_ar')->toString() : null,
+            name_en: $request->filled('name_en') ? $request->string('name_en')->toString() : null,
+            image: $request->file('image'),
+            order: $request->filled('order') ? (int) $request->input('order') : null,
+            is_active: $request->has('is_active') ? $request->boolean('is_active') : null,
+        );
+    }
+}
diff --git a/app/Http/Controllers/Admin/PersonalityController.php b/app/Http/Controllers/Admin/PersonalityController.php
new file mode 100644
index 0000000..2cb0a26
--- /dev/null
+++ b/app/Http/Controllers/Admin/PersonalityController.php
@@ -0,0 +1,44 @@
+<?php
+
+namespace App\Http\Controllers\Admin;
+
+use App\Actions\Personality\CreatePersonalityAction;
+use App\Actions\Personality\DeletePersonalityAction;
+use App\Actions\Personality\GetPersonalitiesAction;
+use App\Actions\Personality\UpdatePersonalityAction;
+use App\DTOs\Personality\CreatePersonalityDTO;
+use App\DTOs\Personality\UpdatePersonalityDTO;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\Personality\StorePersonalityRequest;
+use App\Http\Requests\Personality\UpdatePersonalityRequest;
+use App\Http\Resources\Personality\PersonalityResource;
+use Illuminate\Http\JsonResponse;
+
+class PersonalityController extends Controller
+{
+    public function index(GetPersonalitiesAction $action): JsonResponse
+    {
+        return $this->success(PersonalityResource::collection($action->execute(activeOnly: false)), __('messages.fetched'));
+    }
+
+    public function store(StorePersonalityRequest $request, CreatePersonalityAction $action): JsonResponse
+    {
+        $personality = $action->execute(CreatePersonalityDTO::fromRequest($request));
+
+        return $this->created(new PersonalityResource($personality), __('messages.created'));
+    }
+
+    public function update(UpdatePersonalityRequest $request, int $id, UpdatePersonalityAction $action): JsonResponse
+    {
+        $personality = $action->execute($id, UpdatePersonalityDTO::fromRequest($request));
+
+        return $this->success(new PersonalityResource($personality), __('messages.updated'));
+    }
+
+    public function destroy(int $id, DeletePersonalityAction $action): JsonResponse
+    {
+        $action->execute($id);
+
+        return $this->deleted(__('messages.deleted'));
+    }
+}
diff --git a/app/Http/Controllers/Public/PersonalityController.php b/app/Http/Controllers/Public/PersonalityController.php
new file mode 100644
index 0000000..2c480f1
--- /dev/null
+++ b/app/Http/Controllers/Public/PersonalityController.php
@@ -0,0 +1,18 @@
+<?php
+
+namespace App\Http\Controllers\Public;
+
+use App\Actions\Personality\GetPersonalitiesAction;
+use App\Http\Controllers\Controller;
+use App\Http\Resources\Personality\PersonalityResource;
+use Illuminate\Http\JsonResponse;
+
+class PersonalityController extends Controller
+{
+    public function index(GetPersonalitiesAction $action): JsonResponse
+    {
+        $personalities = $action->execute(activeOnly: true);
+
+        return $this->success(PersonalityResource::collection($personalities), __('messages.fetched'));
+    }
+}
diff --git a/app/Http/Requests/Personality/StorePersonalityRequest.php b/app/Http/Requests/Personality/StorePersonalityRequest.php
new file mode 100644
index 0000000..6ae16a8
--- /dev/null
+++ b/app/Http/Requests/Personality/StorePersonalityRequest.php
@@ -0,0 +1,24 @@
+<?php
+
+namespace App\Http\Requests\Personality;
+
+use App\Http\Requests\BaseFormRequest;
+
+class StorePersonalityRequest extends BaseFormRequest
+{
+    public function authorize(): bool
+    {
+        return true;
+    }
+
+    public function rules(): array
+    {
+        return [
+            'name_ar' => ['required', 'string', 'max:200'],
+            'name_en' => ['required', 'string', 'max:200'],
+            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
+            'order' => ['integer', 'min:0'],
+            'is_active' => ['boolean'],
+        ];
+    }
+}
diff --git a/app/Http/Requests/Personality/UpdatePersonalityRequest.php b/app/Http/Requests/Personality/UpdatePersonalityRequest.php
new file mode 100644
index 0000000..7ae130b
--- /dev/null
+++ b/app/Http/Requests/Personality/UpdatePersonalityRequest.php
@@ -0,0 +1,24 @@
+<?php
+
+namespace App\Http\Requests\Personality;
+
+use App\Http\Requests\BaseFormRequest;
+
+class UpdatePersonalityRequest extends BaseFormRequest
+{
+    public function authorize(): bool
+    {
+        return true;
+    }
+
+    public function rules(): array
+    {
+        return [
+            'name_ar' => ['nullable', 'string', 'max:200'],
+            'name_en' => ['nullable', 'string', 'max:200'],
+            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
+            'order' => ['nullable', 'integer', 'min:0'],
+            'is_active' => ['nullable', 'boolean'],
+        ];
+    }
+}
diff --git a/app/Http/Resources/Personality/PersonalityResource.php b/app/Http/Resources/Personality/PersonalityResource.php
new file mode 100644
index 0000000..a167e12
--- /dev/null
+++ b/app/Http/Resources/Personality/PersonalityResource.php
@@ -0,0 +1,26 @@
+<?php
+
+namespace App\Http\Resources\Personality;
+
+use App\Http\Resources\Concerns\ResolvesLocale;
+use Illuminate\Http\Request;
+use Illuminate\Http\Resources\Json\JsonResource;
+
+class PersonalityResource extends JsonResource
+{
+    use ResolvesLocale;
+
+    public function toArray(Request $request): array
+    {
+        return [
+            'id' => $this->id,
+            'name' => $this->resolveLocale($this->name_ar, $this->name_en),
+            'name_ar' => $this->name_ar,
+            'name_en' => $this->name_en,
+            'image_url' => $this->image_url,
+            'order' => $this->order,
+            'is_active' => $this->is_active,
+            'locale' => app()->getLocale(),
+        ];
+    }
+}
diff --git a/app/Models/Personality.php b/app/Models/Personality.php
new file mode 100644
index 0000000..30ead1f
--- /dev/null
+++ b/app/Models/Personality.php
@@ -0,0 +1,21 @@
+<?php
+
+namespace App\Models;
+
+use Illuminate\Database\Eloquent\Model;
+
+class Personality extends Model
+{
+    protected $fillable = [
+        'name_ar',
+        'name_en',
+        'image_path',
+        'image_url',
+        'order',
+        'is_active',
+    ];
+
+    protected $casts = [
+        'is_active' => 'boolean',
+    ];
+}
diff --git a/app/Repositories/PersonalityRepository.php b/app/Repositories/PersonalityRepository.php
new file mode 100644
index 0000000..347bedd
--- /dev/null
+++ b/app/Repositories/PersonalityRepository.php
@@ -0,0 +1,43 @@
+<?php
+
+namespace App\Repositories;
+
+use App\Models\Personality;
+use Illuminate\Database\Eloquent\Collection;
+
+class PersonalityRepository
+{
+    public function getAll(bool $activeOnly = true): Collection
+    {
+        $query = Personality::orderBy('order');
+
+        if ($activeOnly) {
+            $query->where('is_active', true);
+        }
+
+        return $query->get();
+    }
+
+    public function getById(int $id): Personality
+    {
+        return Personality::findOrFail($id);
+    }
+
+    public function create(array $data): Personality
+    {
+        return Personality::create($data);
+    }
+
+    public function update(int $id, array $data): Personality
+    {
+        $personality = Personality::findOrFail($id);
+        $personality->update($data);
+
+        return $personality;
+    }
+
+    public function delete(int $id): void
+    {
+        Personality::findOrFail($id)->delete();
+    }
+}
diff --git a/database/migrations/2026_07_25_144324_create_personalities_table.php b/database/migrations/2026_07_25_144324_create_personalities_table.php
new file mode 100644
index 0000000..d203555
--- /dev/null
+++ b/database/migrations/2026_07_25_144324_create_personalities_table.php
@@ -0,0 +1,33 @@
+<?php
+
+use Illuminate\Database\Migrations\Migration;
+use Illuminate\Database\Schema\Blueprint;
+use Illuminate\Support\Facades\Schema;
+
+return new class extends Migration
+{
+    /**
+     * Run the migrations.
+     */
+    public function up(): void
+    {
+        Schema::create('personalities', function (Blueprint $table) {
+            $table->id();
+            $table->string('name_ar', 200);
+            $table->string('name_en', 200);
+            $table->string('image_path', 500)->nullable();
+            $table->string('image_url', 500)->nullable();
+            $table->integer('order')->default(0);
+            $table->boolean('is_active')->default(true);
+            $table->timestamps();
+        });
+    }
+
+    /**
+     * Reverse the migrations.
+     */
+    public function down(): void
+    {
+        Schema::dropIfExists('personalities');
+    }
+};
diff --git a/database/seeders/DatabaseSeeder.php b/database/seeders/DatabaseSeeder.php
index ac67ede..3e74bf0 100644
--- a/database/seeders/DatabaseSeeder.php
+++ b/database/seeders/DatabaseSeeder.php
@@ -23,6 +23,7 @@ public function run(): void
             SampleMenuSeeder::class,
             TestimonialSeeder::class,
             TimelineSeeder::class,
+            PersonalitySeeder::class,
             DeliveryAppSeeder::class,
             PageContentSeeder::class,
             SettingSeeder::class,
diff --git a/database/seeders/PageContentSeeder.php b/database/seeders/PageContentSeeder.php
index 8d6e245..0b20d4b 100644
--- a/database/seeders/PageContentSeeder.php
+++ b/database/seeders/PageContentSeeder.php
@@ -58,6 +58,8 @@ public function run(): void
             ['page' => 'story', 'section' => 'journey', 'key' => 'badge', 'value_ar' => 'رحلتنا · OUR JOURNEY', 'value_en' => 'Our Journey · OUR JOURNEY'],
             ['page' => 'story', 'section' => 'journey', 'key' => 'title', 'value_ar' => 'رحلتنا', 'value_en' => 'Our Journey'],
             ['page' => 'story', 'section' => 'journey', 'key' => 'subtitle', 'value_ar' => 'من القاهرة القديمة، إلى ساحل البحر الأحمر، إلى قلب الرياض.', 'value_en' => 'From Old Cairo, to the Red Sea coast, to the heart of Riyadh.'],
+            ['page' => 'story', 'section' => 'personalities', 'key' => 'badge', 'type' => 'text', 'value_ar' => 'الزمن الجميل · GOLDEN ERA', 'value_en' => 'Golden Era · GOLDEN ERA'],
+            ['page' => 'story', 'section' => 'personalities', 'key' => 'title', 'type' => 'text', 'value_ar' => 'وجوهٌ أحبّت الموائد', 'value_en' => 'Faces Who Loved the Table'],
 
             // ── MENU PAGE ────────────────────────────────────────────
             ['page' => 'menu', 'section' => 'hero', 'key' => 'badge', 'value_ar' => 'أطباقنا · OUR MENU', 'value_en' => 'Our Menu · OUR MENU'],
diff --git a/database/seeders/PersonalitySeeder.php b/database/seeders/PersonalitySeeder.php
new file mode 100644
index 0000000..d73b322
--- /dev/null
+++ b/database/seeders/PersonalitySeeder.php
@@ -0,0 +1,27 @@
+<?php
+
+namespace Database\Seeders;
+
+use App\Models\Personality;
+use Illuminate\Database\Seeder;
+
+class PersonalitySeeder extends Seeder
+{
+    public function run(): void
+    {
+        $personalities = [
+            ['name_ar' => 'أم كلثوم', 'name_en' => 'Umm Kulthum', 'order' => 1],
+            ['name_ar' => 'فاتن حمامة', 'name_en' => 'Faten Hamama', 'order' => 2],
+            ['name_ar' => 'نجيب الريحاني', 'name_en' => 'Naguib El-Rihani', 'order' => 3],
+            ['name_ar' => 'هند رستم', 'name_en' => 'Hind Rostom', 'order' => 4],
+            ['name_ar' => 'لبنى عبد العزيز', 'name_en' => 'Lubna Abdel Aziz', 'order' => 5],
+        ];
+
+        foreach ($personalities as $personality) {
+            Personality::updateOrCreate(
+                ['name_ar' => $personality['name_ar']],
+                $personality
+            );
+        }
+    }
+}
diff --git a/routes/admin.php b/routes/admin.php
index 6462b9f..e2f8f8a 100644
--- a/routes/admin.php
+++ b/routes/admin.php
@@ -11,6 +11,7 @@
 use App\Http\Controllers\Admin\DishController;
 use App\Http\Controllers\Admin\MediaItemController;
 use App\Http\Controllers\Admin\PageContentController;
+use App\Http\Controllers\Admin\PersonalityController;
 use App\Http\Controllers\Admin\ProfileController;
 use App\Http\Controllers\Admin\SettingController;
 use App\Http\Controllers\Admin\TestimonialController;
@@ -71,7 +72,7 @@
             Route::get('/',        'index');
             Route::post('/',       'store');
             Route::get('/{id}',    'show');
-            Route::post('/{id}',   'update');
+            Route::put('/{id}',   'update');
             Route::delete('/{id}', 'destroy');
         });
 
@@ -88,7 +89,7 @@
             Route::get('/',        'index');
             Route::post('/',       'store');
             Route::get('/{id}',    'show');
-            Route::post('/{id}',   'update');
+            Route::put('/{id}',   'update');
             Route::delete('/{id}', 'destroy');
         });
 
@@ -98,7 +99,7 @@
             ->group(function () {
                 Route::get('/',        'index');
                 Route::post('/',       'store');
-                Route::post('/{id}',   'update');
+                Route::put('/{id}',   'update');
                 Route::delete('/{id}', 'destroy');
             });
 
@@ -108,7 +109,7 @@
             ->group(function () {
                 Route::get('/',        'index');
                 Route::post('/',       'store');
-                Route::post('/{id}',   'update');
+                Route::put('/{id}',   'update');
                 Route::delete('/{id}', 'destroy');
             });
 
@@ -137,6 +138,14 @@
             Route::delete('/{id}', 'destroy');
         });
 
+        // ── Personalities ─────────────────────────────────
+        Route::prefix('personalities')->controller(PersonalityController::class)->group(function () {
+            Route::get('/',        'index');
+            Route::post('/',       'store');
+            Route::put('/{id}',    'update');
+            Route::delete('/{id}', 'destroy');
+        });
+
         // ── Delivery Apps ─────────────────────────────────
         Route::prefix('delivery-apps')->controller(DeliveryAppController::class)->group(function () {
             Route::get('/',        'index');
diff --git a/routes/api.php b/routes/api.php
index 6dbba22..00b6d86 100644
--- a/routes/api.php
+++ b/routes/api.php
@@ -6,6 +6,7 @@
 use App\Http\Controllers\Public\DishController;
 use App\Http\Controllers\Public\MediaItemController;
 use App\Http\Controllers\Public\PageContentController;
+use App\Http\Controllers\Public\PersonalityController;
 use App\Http\Controllers\Public\SettingController;
 use App\Http\Controllers\Public\TestimonialController;
 use App\Http\Controllers\Public\TimelineController;
@@ -80,6 +81,11 @@
         Route::get('/', 'index');
     });
 
+    // ── Personalities ─────────────────────────────────────
+    Route::prefix('personalities')->controller(PersonalityController::class)->group(function () {
+        Route::get('/', 'index');
+    });
+
     // ── Delivery Apps ─────────────────────────────────────
     Route::prefix('delivery-apps')->controller(DeliveryAppController::class)->group(function () {
         Route::get('/', 'index');
diff --git a/tests/Feature/PublicApiTest.php b/tests/Feature/PublicApiTest.php
index 9e02ff3..054fea5 100644
--- a/tests/Feature/PublicApiTest.php
+++ b/tests/Feature/PublicApiTest.php
@@ -109,6 +109,20 @@ public function test_get_testimonials_returns_ten(): void
             ->assertJsonCount(10, 'data');
     }
 
+    public function test_get_personalities_returns_five_active_only(): void
+    {
+        \App\Models\Personality::first()->update(['is_active' => false]);
+
+        $response = $this->getJson('/api/v1/personalities', ['X-API-Key' => $this->apiKey()]);
+
+        $response->assertStatus(200)
+            ->assertJsonCount(4, 'data');
+
+        foreach ($response->json('data') as $personality) {
+            $this->assertTrue($personality['is_active']);
+        }
+    }
+
     public function test_submit_quote_request_with_valid_data_returns_201(): void
     {
         $response = $this->postJson('/api/v1/catering/quote-requests', [
diff --git a/tests/Feature/RepositoriesTest.php b/tests/Feature/RepositoriesTest.php
index ab89f4a..40687c7 100644
--- a/tests/Feature/RepositoriesTest.php
+++ b/tests/Feature/RepositoriesTest.php
@@ -9,6 +9,7 @@
 use App\Repositories\DishRepository;
 use App\Repositories\MediaItemRepository;
 use App\Repositories\PageContentRepository;
+use App\Repositories\PersonalityRepository;
 use App\Repositories\QuoteRequestRepository;
 use App\Repositories\SampleMenuRepository;
 use App\Repositories\SettingRepository;
@@ -332,6 +333,24 @@ public function test_testimonial_repository_get_all_can_include_inactive(): void
         $this->assertCount(10, $repo->getAll(false));
     }
 
+    // ── PersonalityRepository ────────────────────────────────────
+
+    public function test_personality_repository_get_all_active_only_by_default(): void
+    {
+        $repo = new PersonalityRepository;
+
+        $this->assertCount(5, $repo->getAll());
+    }
+
+    public function test_personality_repository_get_all_can_include_inactive(): void
+    {
+        $repo = new PersonalityRepository;
+        \App\Models\Personality::first()->update(['is_active' => false]);
+
+        $this->assertCount(4, $repo->getAll(true));
+        $this->assertCount(5, $repo->getAll(false));
+    }
+
     // ── TimelineRepository ────────────────────────────────────────
 
     public function test_timeline_repository_get_all_returns_five(): void
===== END change.diff =====

===== BEGIN context-bundle.md =====
# Context bundle

bundle_version 2 · budget 8000 / used 1078 tokens

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

**Subject:** App\Http\Resources\Personality\PersonalityResource::collection
**Reason:** the region depends on App\Http\Resources\Personality\PersonalityResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/PersonalityController.php` :: `App\Http\Resources\Personality\PersonalityResource::collection` (lines 1-44)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
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
===== END context-bundle.md =====

===== BEGIN context-diagnostics.txt =====
new file: app/Actions/Personality/CreatePersonalityAction.php — own-file context is in the diff, not fetched
new file: app/Actions/Personality/DeletePersonalityAction.php — own-file context is in the diff, not fetched
new file: app/Actions/Personality/GetPersonalitiesAction.php — own-file context is in the diff, not fetched
new file: app/Actions/Personality/UpdatePersonalityAction.php — own-file context is in the diff, not fetched
new file: app/DTOs/Personality/CreatePersonalityDTO.php — own-file context is in the diff, not fetched
new file: app/DTOs/Personality/UpdatePersonalityDTO.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Admin/PersonalityController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Public/PersonalityController.php — own-file context is in the diff, not fetched
new file: app/Http/Requests/Personality/StorePersonalityRequest.php — own-file context is in the diff, not fetched
new file: app/Http/Requests/Personality/UpdatePersonalityRequest.php — own-file context is in the diff, not fetched
new file: app/Http/Resources/Personality/PersonalityResource.php — own-file context is in the diff, not fetched
new file: app/Models/Personality.php — own-file context is in the diff, not fetched
new file: app/Repositories/PersonalityRepository.php — own-file context is in the diff, not fetched
new file: database/migrations/2026_07_25_144324_create_personalities_table.php — own-file context is in the diff, not fetched
new file: database/seeders/PersonalitySeeder.php — own-file context is in the diff, not fetched
framework reference: Illuminate\Support\Facades\Cache::tags declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Cache.php:50 (@method static \Illuminate\Cache\TaggedCache tags(mixed $names))
already in the diff: App\Repositories\PersonalityRepository::create declared in app/Repositories/PersonalityRepository.php; not fetched again
already in the diff: App\Repositories\PersonalityRepository declared in app/Repositories/PersonalityRepository.php; not fetched again
already in the diff: App\DTOs\Personality\CreatePersonalityDTO declared in app/DTOs/Personality/CreatePersonalityDTO.php; not fetched again
already in the diff: App\Models\Personality declared in app/Models/Personality.php; not fetched again
framework reference: Illuminate\Support\Facades\Cache::tags declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Cache.php:50 (@method static \Illuminate\Cache\TaggedCache tags(mixed $names))
already in the diff: App\Repositories\PersonalityRepository::getById declared in app/Repositories/PersonalityRepository.php; not fetched again
already in the diff: App\Repositories\PersonalityRepository::delete declared in app/Repositories/PersonalityRepository.php; not fetched again
already in the diff: App\Repositories\PersonalityRepository declared in app/Repositories/PersonalityRepository.php; not fetched again
framework reference: Illuminate\Support\Facades\Cache::tags declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Cache.php:50 (@method static \Illuminate\Cache\TaggedCache tags(mixed $names))
already in the diff: App\Repositories\PersonalityRepository::getAll declared in app/Repositories/PersonalityRepository.php; not fetched again
already in the diff: App\Repositories\PersonalityRepository declared in app/Repositories/PersonalityRepository.php; not fetched again
dependency class: Illuminate\Database\Eloquent\Collection provided by vendor/laravel/framework/src/Illuminate/Database/Eloquent/Collection.php; surface not fetched
dependency member: Illuminate\Support\Arr::whereNotNull declared at vendor/laravel/framework/src/Illuminate/Collections/Arr.php:1284; source not fetched
framework reference: Illuminate\Support\Facades\Cache::tags declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Cache.php:50 (@method static \Illuminate\Cache\TaggedCache tags(mixed $names))
already in the diff: App\Repositories\PersonalityRepository::getById declared in app/Repositories/PersonalityRepository.php; not fetched again
already in the diff: App\Repositories\PersonalityRepository::update declared in app/Repositories/PersonalityRepository.php; not fetched again
already in the diff: App\Repositories\PersonalityRepository declared in app/Repositories/PersonalityRepository.php; not fetched again
already in the diff: App\DTOs\Personality\UpdatePersonalityDTO declared in app/DTOs/Personality/UpdatePersonalityDTO.php; not fetched again
already in the diff: App\Models\Personality declared in app/Models/Personality.php; not fetched again
dependency class: Illuminate\Http\UploadedFile provided by vendor/laravel/framework/src/Illuminate/Http/UploadedFile.php; surface not fetched
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
dependency class: Illuminate\Http\UploadedFile provided by vendor/laravel/framework/src/Illuminate/Http/UploadedFile.php; surface not fetched
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
unresolved named_reference: App\Http\Resources\Personality\PersonalityResource::collection in app/Http/Controllers/Admin/PersonalityController.php
already in the diff: App\DTOs\Personality\CreatePersonalityDTO::fromRequest declared in app/DTOs/Personality/CreatePersonalityDTO.php; not fetched again
already in the diff: App\DTOs\Personality\UpdatePersonalityDTO::fromRequest declared in app/DTOs/Personality/UpdatePersonalityDTO.php; not fetched again
already in the diff: App\Actions\Personality\GetPersonalitiesAction declared in app/Actions/Personality/GetPersonalitiesAction.php; not fetched again
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
already in the diff: App\Http\Requests\Personality\StorePersonalityRequest declared in app/Http/Requests/Personality/StorePersonalityRequest.php; not fetched again
already in the diff: App\Actions\Personality\CreatePersonalityAction declared in app/Actions/Personality/CreatePersonalityAction.php; not fetched again
already in the diff: App\Http\Resources\Personality\PersonalityResource declared in app/Http/Resources/Personality/PersonalityResource.php; not fetched again
already in the diff: App\Http\Requests\Personality\UpdatePersonalityRequest declared in app/Http/Requests/Personality/UpdatePersonalityRequest.php; not fetched again
already in the diff: App\Actions\Personality\UpdatePersonalityAction declared in app/Actions/Personality/UpdatePersonalityAction.php; not fetched again
already in the diff: App\Actions\Personality\DeletePersonalityAction declared in app/Actions/Personality/DeletePersonalityAction.php; not fetched again
unresolved named_reference: App\Http\Resources\Personality\PersonalityResource::collection in app/Http/Controllers/Public/PersonalityController.php
already in the diff: App\Actions\Personality\GetPersonalitiesAction declared in app/Actions/Personality/GetPersonalitiesAction.php; not fetched again
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
unresolved named_reference: App\Models\Personality::orderBy in app/Repositories/PersonalityRepository.php
unresolved named_reference: App\Models\Personality::findOrFail in app/Repositories/PersonalityRepository.php
unresolved named_reference: App\Models\Personality::create in app/Repositories/PersonalityRepository.php
dependency class: Illuminate\Database\Eloquent\Collection provided by vendor/laravel/framework/src/Illuminate/Database/Eloquent/Collection.php; surface not fetched
already in the diff: App\Models\Personality declared in app/Models/Personality.php; not fetched again
framework reference: Illuminate\Support\Facades\Schema::create declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Schema.php:34 (@method static void create(string $table, \Closure $callback))
framework reference: Illuminate\Support\Facades\Schema::dropIfExists declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Schema.php:36 (@method static void dropIfExists(string $table))
dependency class: Illuminate\Database\Schema\Blueprint provided by vendor/laravel/framework/src/Illuminate/Database/Schema/Blueprint.php; surface not fetched
unresolved named_reference: App\Models\Personality::updateOrCreate in database/seeders/PersonalitySeeder.php
framework reference: Illuminate\Support\Facades\Route::put declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:8 (@method static \Illuminate\Routing\Route put(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::put declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:8 (@method static \Illuminate\Routing\Route put(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::put declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:8 (@method static \Illuminate\Routing\Route put(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::put declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:8 (@method static \Illuminate\Routing\Route put(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::prefix declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:100 (@method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix))
framework reference: Illuminate\Support\Facades\Route::get declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:6 (@method static \Illuminate\Routing\Route get(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::post declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:7 (@method static \Illuminate\Routing\Route post(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::put declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:8 (@method static \Illuminate\Routing\Route put(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::delete declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:10 (@method static \Illuminate\Routing\Route delete(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::prefix declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:100 (@method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix))
framework reference: Illuminate\Support\Facades\Route::get declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:6 (@method static \Illuminate\Routing\Route get(string $uri, array|string|callable|null $action = null))
already in the diff: App\Repositories\PersonalityRepository declared in app/Repositories/PersonalityRepository.php; not fetched again
===== END context-diagnostics.txt =====
