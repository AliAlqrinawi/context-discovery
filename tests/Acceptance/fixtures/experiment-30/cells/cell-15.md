<!-- cell-15 -->
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
diff --git a/app/Http/Controllers/Admin/Auth/AuthController.php b/app/Http/Controllers/Admin/Auth/AuthController.php
new file mode 100644
index 0000000..e536c5f
--- /dev/null
+++ b/app/Http/Controllers/Admin/Auth/AuthController.php
@@ -0,0 +1,5 @@
+<?php
+
+namespace App\Http\Controllers\Admin\Auth;
+
+class AuthController extends \App\Http\Controllers\Auth\AuthController {}
diff --git a/app/Http/Controllers/Admin/BranchController.php b/app/Http/Controllers/Admin/BranchController.php
new file mode 100644
index 0000000..7aa58ff
--- /dev/null
+++ b/app/Http/Controllers/Admin/BranchController.php
@@ -0,0 +1,5 @@
+<?php
+
+namespace App\Http\Controllers\Admin;
+
+class BranchController extends \App\Http\Controllers\BranchController {}
diff --git a/app/Http/Controllers/Admin/CategoryController.php b/app/Http/Controllers/Admin/CategoryController.php
new file mode 100644
index 0000000..dca0eb7
--- /dev/null
+++ b/app/Http/Controllers/Admin/CategoryController.php
@@ -0,0 +1,5 @@
+<?php
+
+namespace App\Http\Controllers\Admin;
+
+class CategoryController extends \App\Http\Controllers\CategoryController {}
diff --git a/app/Http/Controllers/Admin/Catering/CateringPackageController.php b/app/Http/Controllers/Admin/Catering/CateringPackageController.php
new file mode 100644
index 0000000..c1d7485
--- /dev/null
+++ b/app/Http/Controllers/Admin/Catering/CateringPackageController.php
@@ -0,0 +1,5 @@
+<?php
+
+namespace App\Http\Controllers\Admin\Catering;
+
+class CateringPackageController extends \App\Http\Controllers\Catering\CateringPackageController {}
diff --git a/app/Http/Controllers/Admin/Catering/QuoteRequestController.php b/app/Http/Controllers/Admin/Catering/QuoteRequestController.php
new file mode 100644
index 0000000..6912f5f
--- /dev/null
+++ b/app/Http/Controllers/Admin/Catering/QuoteRequestController.php
@@ -0,0 +1,5 @@
+<?php
+
+namespace App\Http\Controllers\Admin\Catering;
+
+class QuoteRequestController extends \App\Http\Controllers\Catering\QuoteRequestController {}
diff --git a/app/Http/Controllers/Admin/Catering/SampleMenuController.php b/app/Http/Controllers/Admin/Catering/SampleMenuController.php
new file mode 100644
index 0000000..16c3a27
--- /dev/null
+++ b/app/Http/Controllers/Admin/Catering/SampleMenuController.php
@@ -0,0 +1,5 @@
+<?php
+
+namespace App\Http\Controllers\Admin\Catering;
+
+class SampleMenuController extends \App\Http\Controllers\Catering\SampleMenuController {}
diff --git a/app/Http/Controllers/Admin/DeliveryAppController.php b/app/Http/Controllers/Admin/DeliveryAppController.php
new file mode 100644
index 0000000..bbfd30f
--- /dev/null
+++ b/app/Http/Controllers/Admin/DeliveryAppController.php
@@ -0,0 +1,5 @@
+<?php
+
+namespace App\Http\Controllers\Admin;
+
+class DeliveryAppController extends \App\Http\Controllers\DeliveryAppController {}
diff --git a/app/Http/Controllers/Admin/DishController.php b/app/Http/Controllers/Admin/DishController.php
new file mode 100644
index 0000000..5fb0394
--- /dev/null
+++ b/app/Http/Controllers/Admin/DishController.php
@@ -0,0 +1,5 @@
+<?php
+
+namespace App\Http\Controllers\Admin;
+
+class DishController extends \App\Http\Controllers\DishController {}
diff --git a/app/Http/Controllers/Admin/MediaItemController.php b/app/Http/Controllers/Admin/MediaItemController.php
new file mode 100644
index 0000000..b965e63
--- /dev/null
+++ b/app/Http/Controllers/Admin/MediaItemController.php
@@ -0,0 +1,5 @@
+<?php
+
+namespace App\Http\Controllers\Admin;
+
+class MediaItemController extends \App\Http\Controllers\MediaItemController {}
diff --git a/app/Http/Controllers/Admin/PageContentController.php b/app/Http/Controllers/Admin/PageContentController.php
new file mode 100644
index 0000000..6db51e9
--- /dev/null
+++ b/app/Http/Controllers/Admin/PageContentController.php
@@ -0,0 +1,5 @@
+<?php
+
+namespace App\Http\Controllers\Admin;
+
+class PageContentController extends \App\Http\Controllers\PageContentController {}
diff --git a/app/Http/Controllers/Admin/SettingController.php b/app/Http/Controllers/Admin/SettingController.php
new file mode 100644
index 0000000..d5c9e79
--- /dev/null
+++ b/app/Http/Controllers/Admin/SettingController.php
@@ -0,0 +1,5 @@
+<?php
+
+namespace App\Http\Controllers\Admin;
+
+class SettingController extends \App\Http\Controllers\SettingController {}
diff --git a/app/Http/Controllers/Admin/TestimonialController.php b/app/Http/Controllers/Admin/TestimonialController.php
new file mode 100644
index 0000000..3b6066d
--- /dev/null
+++ b/app/Http/Controllers/Admin/TestimonialController.php
@@ -0,0 +1,5 @@
+<?php
+
+namespace App\Http\Controllers\Admin;
+
+class TestimonialController extends \App\Http\Controllers\TestimonialController {}
diff --git a/app/Http/Controllers/Admin/TimelineController.php b/app/Http/Controllers/Admin/TimelineController.php
new file mode 100644
index 0000000..2ab8f09
--- /dev/null
+++ b/app/Http/Controllers/Admin/TimelineController.php
@@ -0,0 +1,5 @@
+<?php
+
+namespace App\Http\Controllers\Admin;
+
+class TimelineController extends \App\Http\Controllers\TimelineController {}
diff --git a/app/Http/Controllers/Admin/UserController.php b/app/Http/Controllers/Admin/UserController.php
new file mode 100644
index 0000000..8ab19a7
--- /dev/null
+++ b/app/Http/Controllers/Admin/UserController.php
@@ -0,0 +1,5 @@
+<?php
+
+namespace App\Http\Controllers\Admin;
+
+class UserController extends \App\Http\Controllers\UserController {}
diff --git a/app/Http/Controllers/BranchController.php b/app/Http/Controllers/BranchController.php
index 3961a3e..985e499 100644
--- a/app/Http/Controllers/BranchController.php
+++ b/app/Http/Controllers/BranchController.php
@@ -11,6 +11,7 @@
 use App\Http\Requests\Branch\StoreBranchRequest;
 use App\Http\Requests\Branch\UpdateBranchRequest;
 use App\Http\Resources\Branch\BranchResource;
+use App\Repositories\BranchRepository;
 use Illuminate\Http\JsonResponse;
 
 class BranchController extends Controller
@@ -20,6 +21,11 @@ public function index(GetBranchesAction $action): JsonResponse
         return $this->success(BranchResource::collection($action->execute()), __('messages.fetched'));
     }
 
+    public function show(int $id, BranchRepository $repository): JsonResponse
+    {
+        return $this->success(new BranchResource($repository->getById($id)), __('messages.fetched'));
+    }
+
     public function store(StoreBranchRequest $request, CreateBranchAction $action): JsonResponse
     {
         $branch = $action->execute(CreateBranchDTO::fromRequest($request));
diff --git a/app/Http/Controllers/Public/BranchController.php b/app/Http/Controllers/Public/BranchController.php
new file mode 100644
index 0000000..e516400
--- /dev/null
+++ b/app/Http/Controllers/Public/BranchController.php
@@ -0,0 +1,18 @@
+<?php
+
+namespace App\Http\Controllers\Public;
+
+use App\Actions\Branch\GetBranchesAction;
+use App\Http\Controllers\Controller;
+use App\Http\Resources\Branch\BranchResource;
+use Illuminate\Http\JsonResponse;
+
+class BranchController extends Controller
+{
+    public function index(GetBranchesAction $action): JsonResponse
+    {
+        $branches = $action->execute();
+
+        return $this->success(BranchResource::collection($branches), __('messages.fetched'));
+    }
+}
diff --git a/app/Http/Controllers/Public/CategoryController.php b/app/Http/Controllers/Public/CategoryController.php
new file mode 100644
index 0000000..5e16c70
--- /dev/null
+++ b/app/Http/Controllers/Public/CategoryController.php
@@ -0,0 +1,18 @@
+<?php
+
+namespace App\Http\Controllers\Public;
+
+use App\Http\Controllers\Controller;
+use App\Http\Resources\Category\CategoryResource;
+use App\Repositories\CategoryRepository;
+use Illuminate\Http\JsonResponse;
+
+class CategoryController extends Controller
+{
+    public function index(CategoryRepository $repository): JsonResponse
+    {
+        $categories = $repository->getAll();
+
+        return $this->success(CategoryResource::collection($categories), __('messages.fetched'));
+    }
+}
diff --git a/app/Http/Controllers/Public/Catering/CateringPackageController.php b/app/Http/Controllers/Public/Catering/CateringPackageController.php
new file mode 100644
index 0000000..c5e0601
--- /dev/null
+++ b/app/Http/Controllers/Public/Catering/CateringPackageController.php
@@ -0,0 +1,18 @@
+<?php
+
+namespace App\Http\Controllers\Public\Catering;
+
+use App\Actions\Catering\GetPackagesAction;
+use App\Http\Controllers\Controller;
+use App\Http\Resources\Catering\PackageResource;
+use Illuminate\Http\JsonResponse;
+
+class CateringPackageController extends Controller
+{
+    public function index(GetPackagesAction $action): JsonResponse
+    {
+        $packages = $action->execute();
+
+        return $this->success(PackageResource::collection($packages), __('messages.fetched'));
+    }
+}
diff --git a/app/Http/Controllers/Public/Catering/QuoteRequestController.php b/app/Http/Controllers/Public/Catering/QuoteRequestController.php
new file mode 100644
index 0000000..20c27ba
--- /dev/null
+++ b/app/Http/Controllers/Public/Catering/QuoteRequestController.php
@@ -0,0 +1,21 @@
+<?php
+
+namespace App\Http\Controllers\Public\Catering;
+
+use App\Actions\Catering\SubmitQuoteRequestAction;
+use App\DTOs\Catering\SubmitQuoteRequestDTO;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\Catering\StoreQuoteRequestRequest;
+use App\Http\Resources\Catering\QuoteRequestResource;
+use Illuminate\Http\JsonResponse;
+
+class QuoteRequestController extends Controller
+{
+    public function store(StoreQuoteRequestRequest $request, SubmitQuoteRequestAction $action): JsonResponse
+    {
+        $dto = SubmitQuoteRequestDTO::fromRequest($request);
+        $quoteRequest = $action->execute($dto);
+
+        return $this->created(new QuoteRequestResource($quoteRequest), __('messages.quote_submitted'));
+    }
+}
diff --git a/app/Http/Controllers/Public/Catering/SampleMenuController.php b/app/Http/Controllers/Public/Catering/SampleMenuController.php
new file mode 100644
index 0000000..8bdb79f
--- /dev/null
+++ b/app/Http/Controllers/Public/Catering/SampleMenuController.php
@@ -0,0 +1,18 @@
+<?php
+
+namespace App\Http\Controllers\Public\Catering;
+
+use App\Actions\Catering\GetSampleMenusAction;
+use App\Http\Controllers\Controller;
+use App\Http\Resources\Catering\SampleMenuResource;
+use Illuminate\Http\JsonResponse;
+
+class SampleMenuController extends Controller
+{
+    public function index(GetSampleMenusAction $action): JsonResponse
+    {
+        $menus = $action->execute();
+
+        return $this->success(SampleMenuResource::collection($menus), __('messages.fetched'));
+    }
+}
diff --git a/app/Http/Controllers/Public/DeliveryAppController.php b/app/Http/Controllers/Public/DeliveryAppController.php
new file mode 100644
index 0000000..847d5c1
--- /dev/null
+++ b/app/Http/Controllers/Public/DeliveryAppController.php
@@ -0,0 +1,18 @@
+<?php
+
+namespace App\Http\Controllers\Public;
+
+use App\Actions\DeliveryApp\GetDeliveryAppsAction;
+use App\Http\Controllers\Controller;
+use App\Http\Resources\DeliveryApp\DeliveryAppResource;
+use Illuminate\Http\JsonResponse;
+
+class DeliveryAppController extends Controller
+{
+    public function index(GetDeliveryAppsAction $action): JsonResponse
+    {
+        $apps = $action->execute(activeOnly: true);
+
+        return $this->success(DeliveryAppResource::collection($apps), __('messages.fetched'));
+    }
+}
diff --git a/app/Http/Controllers/Public/DishController.php b/app/Http/Controllers/Public/DishController.php
new file mode 100644
index 0000000..33b644e
--- /dev/null
+++ b/app/Http/Controllers/Public/DishController.php
@@ -0,0 +1,32 @@
+<?php
+
+namespace App\Http\Controllers\Public;
+
+use App\Actions\Dish\GetDishAction;
+use App\Actions\Dish\GetDishesAction;
+use App\Http\Controllers\Controller;
+use App\Http\Resources\Dish\DishResource;
+use Illuminate\Http\JsonResponse;
+use Illuminate\Http\Request;
+
+class DishController extends Controller
+{
+    public function index(Request $request, GetDishesAction $action): JsonResponse
+    {
+        $filters = $request->only(['category_id', 'featured', 'signature', 'per_page', 'page']);
+        $dishes = $action->execute($filters);
+
+        return $this->paginated(
+            DishResource::collection($dishes),
+            $dishes,
+            __('messages.fetched')
+        );
+    }
+
+    public function show(int $id, GetDishAction $action): JsonResponse
+    {
+        $dish = $action->execute($id);
+
+        return $this->success(new DishResource($dish), __('messages.fetched'));
+    }
+}
diff --git a/app/Http/Controllers/Public/MediaItemController.php b/app/Http/Controllers/Public/MediaItemController.php
new file mode 100644
index 0000000..16a6ea7
--- /dev/null
+++ b/app/Http/Controllers/Public/MediaItemController.php
@@ -0,0 +1,25 @@
+<?php
+
+namespace App\Http\Controllers\Public;
+
+use App\Actions\MediaItem\GetMediaItemsAction;
+use App\Http\Controllers\Controller;
+use App\Http\Resources\MediaItem\MediaItemResource;
+use Illuminate\Http\JsonResponse;
+use Illuminate\Http\Request;
+
+class MediaItemController extends Controller
+{
+    public function index(Request $request, GetMediaItemsAction $action): JsonResponse
+    {
+        $page = $request->query('page', 'home');
+        $section = $request->query('section');
+
+        $result = $action->execute($page, $section);
+        $resourced = $result->map(
+            fn ($section) => $section->map(fn ($item) => new MediaItemResource($item))
+        );
+
+        return $this->success($resourced, __('messages.fetched'));
+    }
+}
diff --git a/app/Http/Controllers/Public/PageContentController.php b/app/Http/Controllers/Public/PageContentController.php
new file mode 100644
index 0000000..5883f6c
--- /dev/null
+++ b/app/Http/Controllers/Public/PageContentController.php
@@ -0,0 +1,25 @@
+<?php
+
+namespace App\Http\Controllers\Public;
+
+use App\Actions\PageContent\GetPageContentAction;
+use App\Http\Controllers\Controller;
+use App\Http\Resources\PageContent\PageContentResource;
+use Illuminate\Http\JsonResponse;
+use Illuminate\Http\Request;
+
+class PageContentController extends Controller
+{
+    public function index(Request $request, GetPageContentAction $action): JsonResponse
+    {
+        $page = $request->query('page', 'home');
+        $section = $request->query('section');
+
+        $result = $action->execute($page, $section);
+        $resourced = $result->map(
+            fn ($section) => $section->map(fn ($item) => new PageContentResource($item))
+        );
+
+        return $this->success($resourced, __('messages.fetched'));
+    }
+}
diff --git a/app/Http/Controllers/Public/SettingController.php b/app/Http/Controllers/Public/SettingController.php
new file mode 100644
index 0000000..b3f8b7e
--- /dev/null
+++ b/app/Http/Controllers/Public/SettingController.php
@@ -0,0 +1,21 @@
+<?php
+
+namespace App\Http\Controllers\Public;
+
+use App\Actions\Setting\GetSettingsAction;
+use App\Http\Controllers\Controller;
+use App\Http\Resources\Setting\SettingResource;
+use Illuminate\Http\JsonResponse;
+use Illuminate\Http\Request;
+
+class SettingController extends Controller
+{
+    public function index(Request $request, GetSettingsAction $action): JsonResponse
+    {
+        $group = $request->query('group');
+
+        $result = $action->execute($group);
+
+        return $this->success($result->map(fn ($setting) => new SettingResource($setting)), __('messages.fetched'));
+    }
+}
diff --git a/app/Http/Controllers/Public/TestimonialController.php b/app/Http/Controllers/Public/TestimonialController.php
new file mode 100644
index 0000000..e23e22c
--- /dev/null
+++ b/app/Http/Controllers/Public/TestimonialController.php
@@ -0,0 +1,18 @@
+<?php
+
+namespace App\Http\Controllers\Public;
+
+use App\Actions\Testimonial\GetTestimonialsAction;
+use App\Http\Controllers\Controller;
+use App\Http\Resources\Testimonial\TestimonialResource;
+use Illuminate\Http\JsonResponse;
+
+class TestimonialController extends Controller
+{
+    public function index(GetTestimonialsAction $action): JsonResponse
+    {
+        $testimonials = $action->execute(activeOnly: true);
+
+        return $this->success(TestimonialResource::collection($testimonials), __('messages.fetched'));
+    }
+}
diff --git a/app/Http/Controllers/Public/TimelineController.php b/app/Http/Controllers/Public/TimelineController.php
new file mode 100644
index 0000000..ff869a2
--- /dev/null
+++ b/app/Http/Controllers/Public/TimelineController.php
@@ -0,0 +1,18 @@
+<?php
+
+namespace App\Http\Controllers\Public;
+
+use App\Actions\Timeline\GetTimelineAction;
+use App\Http\Controllers\Controller;
+use App\Http\Resources\Timeline\TimelineResource;
+use Illuminate\Http\JsonResponse;
+
+class TimelineController extends Controller
+{
+    public function index(GetTimelineAction $action): JsonResponse
+    {
+        $timeline = $action->execute();
+
+        return $this->success(TimelineResource::collection($timeline), __('messages.fetched'));
+    }
+}
diff --git a/bootstrap/app.php b/bootstrap/app.php
index 6f0ca4b..1a41b17 100644
--- a/bootstrap/app.php
+++ b/bootstrap/app.php
@@ -8,12 +8,18 @@
 use Illuminate\Foundation\Configuration\Exceptions;
 use Illuminate\Foundation\Configuration\Middleware;
 use Illuminate\Http\Request;
+use Illuminate\Support\Facades\Route;
 use Illuminate\Validation\ValidationException;
 
 return Application::configure(basePath: dirname(__DIR__))
     ->withRouting(
         web: __DIR__.'/../routes/web.php',
         api: __DIR__.'/../routes/api.php',
+        then: function () {
+            Route::middleware('api')
+                ->prefix('api')
+                ->group(base_path('routes/admin.php'));
+        },
         commands: __DIR__.'/../routes/console.php',
         health: '/up',
     )
@@ -29,6 +35,7 @@
 
         $middleware->alias([
             'check.api.key' => CheckApiKey::class,
+            'set.locale' => SetLocale::class,
         ]);
 
         // This is an API-only application with no web login route, so guests
diff --git a/routes/admin.php b/routes/admin.php
new file mode 100644
index 0000000..2ca5c4b
--- /dev/null
+++ b/routes/admin.php
@@ -0,0 +1,132 @@
+<?php
+
+use App\Http\Controllers\Admin;
+use Illuminate\Support\Facades\Route;
+
+Route::prefix('v1/admin')->middleware(['set.locale'])->group(function () {
+
+    // ── Auth ──────────────────────────────────────────────
+    Route::prefix('auth')->controller(Admin\Auth\AuthController::class)->group(function () {
+        Route::post('/login', 'login');
+    });
+
+    Route::middleware('auth:sanctum')->group(function () {
+
+        // ── Auth (protected) ──────────────────────────────
+        Route::prefix('auth')->controller(Admin\Auth\AuthController::class)->group(function () {
+            Route::post('/logout', 'logout');
+            Route::get('/me',      'me');
+        });
+
+        // ── Page Contents ─────────────────────────────────
+        Route::prefix('page-contents')->controller(Admin\PageContentController::class)->group(function () {
+            Route::get('/',       'index');
+            Route::get('/{id}',   'show');
+            Route::put('/{id}',   'update');
+            Route::post('/bulk',  'bulkUpdate');
+        });
+
+        // ── Media Items ───────────────────────────────────
+        Route::prefix('media-items')->controller(Admin\MediaItemController::class)->group(function () {
+            Route::get('/',        'index');
+            Route::get('/{id}',    'show');
+            Route::post('/',       'store');
+            Route::put('/{id}',    'update');
+            Route::delete('/{id}', 'destroy');
+        });
+
+        // ── Settings ──────────────────────────────────────
+        Route::prefix('settings')->controller(Admin\SettingController::class)->group(function () {
+            Route::get('/',  'index');
+            Route::put('/',  'bulkUpdate');
+        });
+
+        // ── Dishes ────────────────────────────────────────
+        Route::prefix('dishes')->controller(Admin\DishController::class)->group(function () {
+            Route::get('/',        'index');
+            Route::post('/',       'store');
+            Route::get('/{id}',    'show');
+            Route::post('/{id}',   'update');
+            Route::delete('/{id}', 'destroy');
+        });
+
+        // ── Categories ────────────────────────────────────
+        Route::prefix('categories')->controller(Admin\CategoryController::class)->group(function () {
+            Route::get('/',        'index');
+            Route::post('/',       'store');
+            Route::put('/{id}',    'update');
+            Route::delete('/{id}', 'destroy');
+        });
+
+        // ── Branches ──────────────────────────────────────
+        Route::prefix('branches')->controller(Admin\BranchController::class)->group(function () {
+            Route::get('/',        'index');
+            Route::post('/',       'store');
+            Route::get('/{id}',    'show');
+            Route::post('/{id}',   'update');
+            Route::delete('/{id}', 'destroy');
+        });
+
+        // ── Catering Packages ─────────────────────────────
+        Route::prefix('catering/packages')
+            ->controller(Admin\Catering\CateringPackageController::class)
+            ->group(function () {
+                Route::get('/',        'index');
+                Route::post('/',       'store');
+                Route::post('/{id}',   'update');
+                Route::delete('/{id}', 'destroy');
+            });
+
+        // ── Sample Menus ──────────────────────────────────
+        Route::prefix('catering/sample-menus')
+            ->controller(Admin\Catering\SampleMenuController::class)
+            ->group(function () {
+                Route::get('/',        'index');
+                Route::post('/',       'store');
+                Route::post('/{id}',   'update');
+                Route::delete('/{id}', 'destroy');
+            });
+
+        // ── Quote Requests ────────────────────────────────
+        Route::prefix('catering/quote-requests')
+            ->controller(Admin\Catering\QuoteRequestController::class)
+            ->group(function () {
+                Route::get('/',              'index');
+                Route::get('/{id}',          'show');
+                Route::patch('/{id}/status', 'updateStatus');
+            });
+
+        // ── Testimonials ──────────────────────────────────
+        Route::prefix('testimonials')->controller(Admin\TestimonialController::class)->group(function () {
+            Route::get('/',        'index');
+            Route::post('/',       'store');
+            Route::put('/{id}',    'update');
+            Route::delete('/{id}', 'destroy');
+        });
+
+        // ── Timeline ──────────────────────────────────────
+        Route::prefix('timeline')->controller(Admin\TimelineController::class)->group(function () {
+            Route::get('/',        'index');
+            Route::post('/',       'store');
+            Route::put('/{id}',    'update');
+            Route::delete('/{id}', 'destroy');
+        });
+
+        // ── Delivery Apps ─────────────────────────────────
+        Route::prefix('delivery-apps')->controller(Admin\DeliveryAppController::class)->group(function () {
+            Route::get('/',        'index');
+            Route::post('/',       'store');
+            Route::put('/{id}',    'update');
+            Route::delete('/{id}', 'destroy');
+        });
+
+        // ── Users ─────────────────────────────────────────
+        Route::prefix('users')->controller(Admin\UserController::class)->group(function () {
+            Route::get('/',        'index');
+            Route::post('/',       'store');
+            Route::get('/{id}',    'show');
+            Route::put('/{id}',    'update');
+            Route::delete('/{id}', 'destroy');
+        });
+    });
+});
diff --git a/routes/api.php b/routes/api.php
index 719c6e7..6dbba22 100644
--- a/routes/api.php
+++ b/routes/api.php
@@ -1,117 +1,87 @@
 <?php
 
-use App\Http\Controllers\Auth\AuthController;
-use App\Http\Controllers\BranchController;
-use App\Http\Controllers\Catering\CateringPackageController;
-use App\Http\Controllers\Catering\QuoteRequestController;
-use App\Http\Controllers\Catering\SampleMenuController;
-use App\Http\Controllers\CategoryController;
-use App\Http\Controllers\DeliveryAppController;
-use App\Http\Controllers\DishController;
-use App\Http\Controllers\MediaItemController;
-use App\Http\Controllers\PageContentController;
-use App\Http\Controllers\SettingController;
-use App\Http\Controllers\TestimonialController;
-use App\Http\Controllers\TimelineController;
-use App\Http\Controllers\UserController;
+use App\Http\Controllers\Public\BranchController;
+use App\Http\Controllers\Public\CategoryController;
+use App\Http\Controllers\Public\DeliveryAppController;
+use App\Http\Controllers\Public\DishController;
+use App\Http\Controllers\Public\MediaItemController;
+use App\Http\Controllers\Public\PageContentController;
+use App\Http\Controllers\Public\SettingController;
+use App\Http\Controllers\Public\TestimonialController;
+use App\Http\Controllers\Public\TimelineController;
+use App\Http\Controllers\Public\Catering\CateringPackageController;
+use App\Http\Controllers\Public\Catering\QuoteRequestController;
+use App\Http\Controllers\Public\Catering\SampleMenuController;
 use Illuminate\Support\Facades\Route;
 
-Route::prefix('v1')->group(function () {
+Route::prefix('v1')->middleware(['check.api.key', 'set.locale'])->group(function () {
 
-    // ── PUBLIC (X-API-Key) ──────────────────────────────────
-    Route::middleware('check.api.key')->group(function () {
+    // ── Ping (test) ───────────────────────────────────────
+    Route::get('/ping', fn() => response()->json([
+        'success' => true,
+        'message' => 'pong',
+        'locale'  => app()->getLocale(),
+    ]));
 
-        Route::get('/ping', function () {
-            return response()->json([
-                'success' => true,
-                'message' => 'pong',
-                'locale' => app()->getLocale(),
-            ]);
+    // ── Page Contents ─────────────────────────────────────
+    Route::prefix('page-contents')->controller(PageContentController::class)->group(function () {
+        Route::get('/', 'index');
+    });
+
+    // ── Media Items ───────────────────────────────────────
+    Route::prefix('media-items')->controller(MediaItemController::class)->group(function () {
+        Route::get('/', 'index');
+    });
+
+    // ── Settings ──────────────────────────────────────────
+    Route::prefix('settings')->controller(SettingController::class)->group(function () {
+        Route::get('/', 'index');
+    });
+
+    // ── Categories ────────────────────────────────────────
+    Route::prefix('categories')->controller(CategoryController::class)->group(function () {
+        Route::get('/', 'index');
+    });
+
+    // ── Dishes ────────────────────────────────────────────
+    Route::prefix('dishes')->controller(DishController::class)->group(function () {
+        Route::get('/',     'index');
+        Route::get('/{id}', 'show');
+    });
+
+    // ── Branches ──────────────────────────────────────────
+    Route::prefix('branches')->controller(BranchController::class)->group(function () {
+        Route::get('/', 'index');
+    });
+
+    // ── Catering ──────────────────────────────────────────
+    Route::prefix('catering')->group(function () {
+
+        Route::prefix('packages')->controller(CateringPackageController::class)->group(function () {
+            Route::get('/', 'index');
+        });
+
+        Route::prefix('sample-menus')->controller(SampleMenuController::class)->group(function () {
+            Route::get('/', 'index');
         });
 
-        // Content & Media (full CMS)
-        Route::get('page-contents', [PageContentController::class, 'index']);
-        Route::get('media-items', [MediaItemController::class, 'index']);
-        Route::get('settings', [SettingController::class, 'index']);
-
-        // Core data
-        Route::get('categories', [CategoryController::class, 'index']);
-        Route::get('dishes', [DishController::class, 'index']);
-        Route::get('dishes/{id}', [DishController::class, 'show']);
-        Route::get('branches', [BranchController::class, 'index']);
-        Route::get('catering/packages', [CateringPackageController::class, 'index']);
-        Route::get('catering/sample-menus', [SampleMenuController::class, 'index']);
-        Route::get('testimonials', [TestimonialController::class, 'index']);
-        Route::get('timeline', [TimelineController::class, 'index']);
-        Route::get('delivery-apps', [DeliveryAppController::class, 'index']);
-
-        // Public form submission
-        Route::post('catering/quote-requests', [QuoteRequestController::class, 'store']);
+        Route::prefix('quote-requests')->controller(QuoteRequestController::class)->group(function () {
+            Route::post('/', 'store');
+        });
+    });
+
+    // ── Testimonials ──────────────────────────────────────
+    Route::prefix('testimonials')->controller(TestimonialController::class)->group(function () {
+        Route::get('/', 'index');
+    });
+
+    // ── Timeline ──────────────────────────────────────────
+    Route::prefix('timeline')->controller(TimelineController::class)->group(function () {
+        Route::get('/', 'index');
     });
 
-    // ── AUTH ────────────────────────────────────────────────
-    Route::post('auth/login', [AuthController::class, 'login']);
-
-    Route::middleware('auth:sanctum')->group(function () {
-        Route::post('auth/logout', [AuthController::class, 'logout']);
-        Route::get('auth/me', [AuthController::class, 'me']);
-
-        // ── CMS (Page Content & Media) ───────────────────────
-        Route::get('page-contents/{id}', [PageContentController::class, 'show']);
-        Route::put('page-contents/{id}', [PageContentController::class, 'update']);
-        Route::post('page-contents/bulk', [PageContentController::class, 'bulkUpdate']);
-
-        Route::get('media-items/{id}', [MediaItemController::class, 'show']);
-        Route::post('media-items', [MediaItemController::class, 'store']);
-        Route::put('media-items/{id}', [MediaItemController::class, 'update']);
-        Route::delete('media-items/{id}', [MediaItemController::class, 'destroy']);
-
-        // ── Settings ─────────────────────────────────────────
-        Route::put('settings', [SettingController::class, 'bulkUpdate']);
-
-        // ── Core CRUD ────────────────────────────────────────
-        Route::post('categories', [CategoryController::class, 'store']);
-        Route::put('categories/{id}', [CategoryController::class, 'update']);
-        Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
-
-        Route::post('dishes', [DishController::class, 'store']);
-        Route::put('dishes/{id}', [DishController::class, 'update']);
-        Route::delete('dishes/{id}', [DishController::class, 'destroy']);
-
-        Route::post('branches', [BranchController::class, 'store']);
-        Route::put('branches/{id}', [BranchController::class, 'update']);
-        Route::delete('branches/{id}', [BranchController::class, 'destroy']);
-
-        Route::post('catering/packages', [CateringPackageController::class, 'store']);
-        Route::put('catering/packages/{id}', [CateringPackageController::class, 'update']);
-        Route::delete('catering/packages/{id}', [CateringPackageController::class, 'destroy']);
-
-        Route::post('catering/sample-menus', [SampleMenuController::class, 'store']);
-        Route::put('catering/sample-menus/{id}', [SampleMenuController::class, 'update']);
-        Route::delete('catering/sample-menus/{id}', [SampleMenuController::class, 'destroy']);
-
-        // ── Quote Requests ───────────────────────────────────
-        Route::get('catering/quote-requests', [QuoteRequestController::class, 'index']);
-        Route::get('catering/quote-requests/{id}', [QuoteRequestController::class, 'show']);
-        Route::patch('catering/quote-requests/{id}/status', [QuoteRequestController::class, 'updateStatus']);
-
-        Route::post('testimonials', [TestimonialController::class, 'store']);
-        Route::put('testimonials/{id}', [TestimonialController::class, 'update']);
-        Route::delete('testimonials/{id}', [TestimonialController::class, 'destroy']);
-
-        Route::post('timeline', [TimelineController::class, 'store']);
-        Route::put('timeline/{id}', [TimelineController::class, 'update']);
-        Route::delete('timeline/{id}', [TimelineController::class, 'destroy']);
-
-        Route::post('delivery-apps', [DeliveryAppController::class, 'store']);
-        Route::put('delivery-apps/{id}', [DeliveryAppController::class, 'update']);
-        Route::delete('delivery-apps/{id}', [DeliveryAppController::class, 'destroy']);
-
-        // ── Users ────────────────────────────────────────────
-        Route::get('users', [UserController::class, 'index']);
-        Route::post('users', [UserController::class, 'store']);
-        Route::get('users/{id}', [UserController::class, 'show']);
-        Route::put('users/{id}', [UserController::class, 'update']);
-        Route::delete('users/{id}', [UserController::class, 'destroy']);
+    // ── Delivery Apps ─────────────────────────────────────
+    Route::prefix('delivery-apps')->controller(DeliveryAppController::class)->group(function () {
+        Route::get('/', 'index');
     });
 });
diff --git a/tests/Feature/AdminApiTest.php b/tests/Feature/AdminApiTest.php
index c2f485c..8100e00 100644
--- a/tests/Feature/AdminApiTest.php
+++ b/tests/Feature/AdminApiTest.php
@@ -19,7 +19,7 @@ protected function setUp(): void
 
     public function test_login_with_correct_credentials_returns_200_with_token(): void
     {
-        $response = $this->postJson('/api/v1/auth/login', [
+        $response = $this->postJson('/api/v1/admin/auth/login', [
             'email' => 'admin@abouelsid.com',
             'password' => 'password',
         ]);
@@ -31,7 +31,7 @@ public function test_login_with_correct_credentials_returns_200_with_token(): vo
 
     public function test_login_with_wrong_password_returns_401(): void
     {
-        $response = $this->postJson('/api/v1/auth/login', [
+        $response = $this->postJson('/api/v1/admin/auth/login', [
             'email' => 'admin@abouelsid.com',
             'password' => 'wrong-password',
         ]);
@@ -42,7 +42,7 @@ public function test_login_with_wrong_password_returns_401(): void
 
     public function test_get_quote_requests_without_token_returns_401(): void
     {
-        $response = $this->getJson('/api/v1/catering/quote-requests');
+        $response = $this->getJson('/api/v1/admin/catering/quote-requests');
 
         $response->assertStatus(401)
             ->assertJson(['success' => false]);
@@ -53,7 +53,7 @@ public function test_get_quote_requests_with_token_returns_200(): void
         $user = User::where('email', 'admin@abouelsid.com')->first();
         $token = $user->createToken('test-token')->plainTextToken;
 
-        $response = $this->getJson('/api/v1/catering/quote-requests', [
+        $response = $this->getJson('/api/v1/admin/catering/quote-requests', [
             'Authorization' => "Bearer {$token}",
         ]);
 
===== END change.diff =====

===== BEGIN context-bundle.md =====
# Context bundle

bundle_version 2 · budget 8000 / used 4134 tokens

## fetched · named_reference

**Subject:** App\Actions\Branch\GetBranchesAction
**Reason:** the region depends on App\Actions\Branch\GetBranchesAction, whose contract is defined in another file
**Source:** `app/Actions/Branch/GetBranchesAction.php` :: `__construct` (lines 11-13)
**Tokens:** 24

```php
    public function __construct(
        private readonly BranchRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Branch\GetBranchesAction
**Reason:** the region depends on App\Actions\Branch\GetBranchesAction, whose contract is defined in another file
**Source:** `app/Actions/Branch/GetBranchesAction.php` :: `execute` (lines 15-25)
**Tokens:** 73

```php
    public function execute(): Collection
    {
        $locale = app()->getLocale();
        $key = "branches_{$locale}";

        return Cache::tags(['branches'])->remember(
            $key,
            now()->addHour(),
            fn () => $this->repository->getAll()
        );
    }
```

## fetched · named_reference

**Subject:** App\Actions\Catering\GetPackagesAction
**Reason:** the region depends on App\Actions\Catering\GetPackagesAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/GetPackagesAction.php` :: `__construct` (lines 11-13)
**Tokens:** 27

```php
    public function __construct(
        private readonly CateringPackageRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Catering\GetPackagesAction
**Reason:** the region depends on App\Actions\Catering\GetPackagesAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/GetPackagesAction.php` :: `execute` (lines 15-25)
**Tokens:** 77

```php
    public function execute(): Collection
    {
        $locale = app()->getLocale();
        $key = "catering_packages_{$locale}";

        return Cache::tags(['catering_packages'])->remember(
            $key,
            now()->addHour(),
            fn () => $this->repository->getAll()
        );
    }
```

## fetched · named_reference

**Subject:** App\Actions\Catering\GetSampleMenusAction
**Reason:** the region depends on App\Actions\Catering\GetSampleMenusAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/GetSampleMenusAction.php` :: `__construct` (lines 11-13)
**Tokens:** 25

```php
    public function __construct(
        private readonly SampleMenuRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Catering\GetSampleMenusAction
**Reason:** the region depends on App\Actions\Catering\GetSampleMenusAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/GetSampleMenusAction.php` :: `execute` (lines 15-25)
**Tokens:** 75

```php
    public function execute(): Collection
    {
        $locale = app()->getLocale();
        $key = "sample_menus_{$locale}";

        return Cache::tags(['sample_menus'])->remember(
            $key,
            now()->addHour(),
            fn () => $this->repository->getAll()
        );
    }
```

## fetched · named_reference

**Subject:** App\Actions\Catering\SubmitQuoteRequestAction
**Reason:** the region depends on App\Actions\Catering\SubmitQuoteRequestAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/SubmitQuoteRequestAction.php` :: `__construct` (lines 11-13)
**Tokens:** 26

```php
    public function __construct(
        private readonly QuoteRequestRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Catering\SubmitQuoteRequestAction
**Reason:** the region depends on App\Actions\Catering\SubmitQuoteRequestAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/SubmitQuoteRequestAction.php` :: `execute` (lines 15-29)
**Tokens:** 136

```php
    public function execute(SubmitQuoteRequestDTO $dto): QuoteRequest
    {
        return $this->repository->create([
            'name' => $dto->name,
            'phone' => $dto->phone,
            'email' => $dto->email,
            'event_date' => $dto->event_date,
            'guests_count' => $dto->guests_count,
            'branch' => $dto->branch,
            'event_type' => $dto->event_type,
            'budget_range' => $dto->budget_range,
            'notes' => $dto->notes,
            'status' => 'pending',
        ]);
    }
```

## fetched · named_reference

**Subject:** App\Actions\DeliveryApp\GetDeliveryAppsAction
**Reason:** the region depends on App\Actions\DeliveryApp\GetDeliveryAppsAction, whose contract is defined in another file
**Source:** `app/Actions/DeliveryApp/GetDeliveryAppsAction.php` :: `__construct` (lines 11-13)
**Tokens:** 26

```php
    public function __construct(
        private readonly DeliveryAppRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\DeliveryApp\GetDeliveryAppsAction
**Reason:** the region depends on App\Actions\DeliveryApp\GetDeliveryAppsAction, whose contract is defined in another file
**Source:** `app/Actions/DeliveryApp/GetDeliveryAppsAction.php` :: `execute` (lines 15-25)
**Tokens:** 92

```php
    public function execute(bool $activeOnly = true): Collection
    {
        $locale = app()->getLocale();
        $key = "delivery_apps_{$locale}_".($activeOnly ? 'active' : 'all');

        return Cache::tags(['delivery_apps'])->remember(
            $key,
            now()->addHour(),
            fn () => $this->repository->getAll($activeOnly)
        );
    }
```

## fetched · named_reference

**Subject:** App\Actions\Dish\GetDishAction
**Reason:** the region depends on App\Actions\Dish\GetDishAction, whose contract is defined in another file
**Source:** `app/Actions/Dish/GetDishAction.php` :: `__construct` (lines 10-12)
**Tokens:** 24

```php
    public function __construct(
        private readonly DishRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Dish\GetDishAction
**Reason:** the region depends on App\Actions\Dish\GetDishAction, whose contract is defined in another file
**Source:** `app/Actions/Dish/GetDishAction.php` :: `execute` (lines 14-17)
**Tokens:** 26

```php
    public function execute(int $id): Dish
    {
        return $this->repository->getById($id);
    }
```

## fetched · named_reference

**Subject:** App\Actions\Dish\GetDishesAction
**Reason:** the region depends on App\Actions\Dish\GetDishesAction, whose contract is defined in another file
**Source:** `app/Actions/Dish/GetDishesAction.php` :: `__construct` (lines 11-13)
**Tokens:** 24

```php
    public function __construct(
        private readonly DishRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Dish\GetDishesAction
**Reason:** the region depends on App\Actions\Dish\GetDishesAction, whose contract is defined in another file
**Source:** `app/Actions/Dish/GetDishesAction.php` :: `execute` (lines 15-31)
**Tokens:** 190

```php
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $locale = app()->getLocale();
        $categoryId = $filters['category_id'] ?? 'all';
        $featured = array_key_exists('featured', $filters) ? var_export($filters['featured'], true) : 'all';
        $signature = array_key_exists('signature', $filters) ? var_export($filters['signature'], true) : 'all';
        $perPage = $filters['per_page'] ?? 'default';
        $page = request()->integer('page', 1);

        $key = "dishes_{$locale}_{$categoryId}_{$featured}_{$signature}_{$perPage}_{$page}";

        return Cache::tags(['dishes'])->remember(
            $key,
            now()->addHour(),
            fn () => $this->repository->getAll($filters)
        );
    }
```

## fetched · named_reference

**Subject:** App\Actions\MediaItem\GetMediaItemsAction
**Reason:** the region depends on App\Actions\MediaItem\GetMediaItemsAction, whose contract is defined in another file
**Source:** `app/Actions/MediaItem/GetMediaItemsAction.php` :: `__construct` (lines 11-13)
**Tokens:** 25

```php
    public function __construct(
        private readonly MediaItemRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\MediaItem\GetMediaItemsAction
**Reason:** the region depends on App\Actions\MediaItem\GetMediaItemsAction, whose contract is defined in another file
**Source:** `app/Actions/MediaItem/GetMediaItemsAction.php` :: `execute` (lines 15-24)
**Tokens:** 80

```php
    public function execute(string $page, ?string $section = null): Collection
    {
        $key = "media_items_{$page}_{$section}";

        return Cache::tags(['media_items'])->remember(
            $key,
            now()->addDay(),
            fn () => $this->repository->getByPage($page, $section)
        );
    }
```

## fetched · named_reference

**Subject:** App\Actions\PageContent\GetPageContentAction
**Reason:** the region depends on App\Actions\PageContent\GetPageContentAction, whose contract is defined in another file
**Source:** `app/Actions/PageContent/GetPageContentAction.php` :: `__construct` (lines 11-13)
**Tokens:** 26

```php
    public function __construct(
        private readonly PageContentRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\PageContent\GetPageContentAction
**Reason:** the region depends on App\Actions\PageContent\GetPageContentAction, whose contract is defined in another file
**Source:** `app/Actions/PageContent/GetPageContentAction.php` :: `execute` (lines 15-25)
**Tokens:** 93

```php
    public function execute(string $page, ?string $section = null): Collection
    {
        $locale = app()->getLocale();
        $key = "page_contents_{$page}_{$section}_{$locale}";

        return Cache::tags(['page_contents'])->remember(
            $key,
            now()->addDay(),
            fn () => $this->repository->getByPage($page, $section)
        );
    }
```

## fetched · named_reference

**Subject:** App\Actions\Setting\GetSettingsAction
**Reason:** the region depends on App\Actions\Setting\GetSettingsAction, whose contract is defined in another file
**Source:** `app/Actions/Setting/GetSettingsAction.php` :: `__construct` (lines 11-13)
**Tokens:** 25

```php
    public function __construct(
        private readonly SettingRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Setting\GetSettingsAction
**Reason:** the region depends on App\Actions\Setting\GetSettingsAction, whose contract is defined in another file
**Source:** `app/Actions/Setting/GetSettingsAction.php` :: `execute` (lines 15-24)
**Tokens:** 69

```php
    public function execute(?string $group = null): Collection
    {
        $key = "settings_{$group}";

        return Cache::tags(['settings'])->remember(
            $key,
            now()->addDay(),
            fn () => $this->repository->getAll($group)
        );
    }
```

## fetched · named_reference

**Subject:** App\Actions\Testimonial\GetTestimonialsAction
**Reason:** the region depends on App\Actions\Testimonial\GetTestimonialsAction, whose contract is defined in another file
**Source:** `app/Actions/Testimonial/GetTestimonialsAction.php` :: `__construct` (lines 11-13)
**Tokens:** 26

```php
    public function __construct(
        private readonly TestimonialRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Testimonial\GetTestimonialsAction
**Reason:** the region depends on App\Actions\Testimonial\GetTestimonialsAction, whose contract is defined in another file
**Source:** `app/Actions/Testimonial/GetTestimonialsAction.php` :: `execute` (lines 15-25)
**Tokens:** 92

```php
    public function execute(bool $activeOnly = true): Collection
    {
        $locale = app()->getLocale();
        $key = "testimonials_{$locale}_".($activeOnly ? 'active' : 'all');

        return Cache::tags(['testimonials'])->remember(
            $key,
            now()->addHour(),
            fn () => $this->repository->getAll($activeOnly)
        );
    }
```

## fetched · named_reference

**Subject:** App\Actions\Timeline\GetTimelineAction
**Reason:** the region depends on App\Actions\Timeline\GetTimelineAction, whose contract is defined in another file
**Source:** `app/Actions/Timeline/GetTimelineAction.php` :: `__construct` (lines 11-13)
**Tokens:** 25

```php
    public function __construct(
        private readonly TimelineRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Timeline\GetTimelineAction
**Reason:** the region depends on App\Actions\Timeline\GetTimelineAction, whose contract is defined in another file
**Source:** `app/Actions/Timeline/GetTimelineAction.php` :: `execute` (lines 15-25)
**Tokens:** 72

```php
    public function execute(): Collection
    {
        $locale = app()->getLocale();
        $key = "timeline_{$locale}";

        return Cache::tags(['timeline'])->remember(
            $key,
            now()->addDay(),
            fn () => $this->repository->getAll()
        );
    }
```

## fetched · named_reference

**Subject:** App\DTOs\Catering\SubmitQuoteRequestDTO::fromRequest
**Reason:** the region depends on App\DTOs\Catering\SubmitQuoteRequestDTO::fromRequest, whose contract is defined in another file
**Source:** `app/DTOs/Catering/SubmitQuoteRequestDTO.php` :: `fromRequest` (lines 21-34)
**Tokens:** 197

```php
    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            phone: $request->string('phone')->toString(),
            email: $request->filled('email') ? $request->string('email')->toString() : null,
            event_date: $request->string('event_date')->toString(),
            guests_count: (int) $request->input('guests_count'),
            branch: $request->string('branch')->toString(),
            event_type: $request->string('event_type')->toString(),
            budget_range: $request->filled('budget_range') ? $request->string('budget_range')->toString() : null,
            notes: $request->filled('notes') ? $request->string('notes')->toString() : null,
        );
    }
```

## flagged · named_reference

**Subject:** App\Http\Resources\Branch\BranchResource::collection
**Reason:** the region depends on App\Http\Resources\Branch\BranchResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/BranchController.php` :: `App\Http\Resources\Branch\BranchResource::collection` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Category\CategoryResource::collection
**Reason:** the region depends on App\Http\Resources\Category\CategoryResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/CategoryController.php` :: `App\Http\Resources\Category\CategoryResource::collection` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Catering\PackageResource::collection
**Reason:** the region depends on App\Http\Resources\Catering\PackageResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/Catering/CateringPackageController.php` :: `App\Http\Resources\Catering\PackageResource::collection` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Catering\SampleMenuResource::collection
**Reason:** the region depends on App\Http\Resources\Catering\SampleMenuResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/Catering/SampleMenuController.php` :: `App\Http\Resources\Catering\SampleMenuResource::collection` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\DeliveryApp\DeliveryAppResource::collection
**Reason:** the region depends on App\Http\Resources\DeliveryApp\DeliveryAppResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/DeliveryAppController.php` :: `App\Http\Resources\DeliveryApp\DeliveryAppResource::collection` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Dish\DishResource::collection
**Reason:** the region depends on App\Http\Resources\Dish\DishResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/DishController.php` :: `App\Http\Resources\Dish\DishResource::collection` (lines 1-32)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Testimonial\TestimonialResource::collection
**Reason:** the region depends on App\Http\Resources\Testimonial\TestimonialResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/TestimonialController.php` :: `App\Http\Resources\Testimonial\TestimonialResource::collection` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Timeline\TimelineResource::collection
**Reason:** the region depends on App\Http\Resources\Timeline\TimelineResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Public/TimelineController.php` :: `App\Http\Resources\Timeline\TimelineResource::collection` (lines 1-18)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Subject:** App\Http\Requests\Catering\StoreQuoteRequestRequest
**Reason:** the region depends on App\Http\Requests\Catering\StoreQuoteRequestRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Catering/StoreQuoteRequestRequest.php` :: `authorize` (lines 13-16)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Catering\StoreQuoteRequestRequest
**Reason:** the region depends on App\Http\Requests\Catering\StoreQuoteRequestRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Catering/StoreQuoteRequestRequest.php` :: `rules` (lines 18-31)
**Tokens:** 178

```php
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'event_date' => ['required', 'date', 'after:today'],
            'guests_count' => ['required', 'integer', 'min:1', 'max:10000'],
            'branch' => ['required', 'string', Rule::in(BranchLocationEnum::values())],
            'event_type' => ['required', 'string', Rule::in(EventTypeEnum::values())],
            'budget_range' => ['nullable', 'string', Rule::in(BudgetRangeEnum::values())],
            'notes' => ['nullable', 'string', 'max:1000'],
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

**Subject:** App\Http\Resources\Category\CategoryResource::collection
**Reason:** the region depends on App\Http\Resources\Category\CategoryResource::collection, whose contract is defined in another file
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

**Subject:** App\Http\Resources\Catering\PackageResource::collection
**Reason:** the region depends on App\Http\Resources\Catering\PackageResource::collection, whose contract is defined in another file
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

**Subject:** App\Http\Resources\Catering\SampleMenuResource::collection
**Reason:** the region depends on App\Http\Resources\Catering\SampleMenuResource::collection, whose contract is defined in another file
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

**Subject:** App\Http\Resources\DeliveryApp\DeliveryAppResource::collection
**Reason:** the region depends on App\Http\Resources\DeliveryApp\DeliveryAppResource::collection, whose contract is defined in another file
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

**Subject:** App\Http\Resources\Testimonial\TestimonialResource::collection
**Reason:** the region depends on App\Http\Resources\Testimonial\TestimonialResource::collection, whose contract is defined in another file
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

**Subject:** App\Http\Resources\Timeline\TimelineResource::collection
**Reason:** the region depends on App\Http\Resources\Timeline\TimelineResource::collection, whose contract is defined in another file
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

**Subject:** App\Repositories\BranchRepository
**Reason:** the region depends on App\Repositories\BranchRepository, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `create` (lines 20-23)
**Tokens:** 25

```php
    public function create(array $data): Branch
    {
        return Branch::create($data);
    }
```

## fetched · named_reference

**Subject:** App\Repositories\BranchRepository
**Reason:** the region depends on App\Repositories\BranchRepository, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `delete` (lines 33-36)
**Tokens:** 24

```php
    public function delete(int $id): void
    {
        Branch::findOrFail($id)->delete();
    }
```

## fetched · named_reference

**Subject:** App\Repositories\BranchRepository
**Reason:** the region depends on App\Repositories\BranchRepository, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `getAll` (lines 10-13)
**Tokens:** 25

```php
    public function getAll(): Collection
    {
        return Branch::orderBy('order')->get();
    }
```

## fetched · named_reference

**Subject:** App\Repositories\BranchRepository
**Reason:** the region depends on App\Repositories\BranchRepository, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `getById` (lines 15-18)
**Tokens:** 24

```php
    public function getById(int $id): Branch
    {
        return Branch::findOrFail($id);
    }
```

## fetched · named_reference

**Subject:** App\Repositories\BranchRepository
**Reason:** the region depends on App\Repositories\BranchRepository, whose contract is defined in another file
**Source:** `app/Repositories/BranchRepository.php` :: `update` (lines 25-31)
**Tokens:** 42

```php
    public function update(int $id, array $data): Branch
    {
        $branch = Branch::findOrFail($id);
        $branch->update($data);

        return $branch;
    }
```

## fetched · named_reference

**Subject:** App\Repositories\CategoryRepository
**Reason:** the region depends on App\Repositories\CategoryRepository, whose contract is defined in another file
**Source:** `app/Repositories/CategoryRepository.php` :: `create` (lines 20-23)
**Tokens:** 26

```php
    public function create(array $data): Category
    {
        return Category::create($data);
    }
```

## fetched · named_reference

**Subject:** App\Repositories\CategoryRepository
**Reason:** the region depends on App\Repositories\CategoryRepository, whose contract is defined in another file
**Source:** `app/Repositories/CategoryRepository.php` :: `delete` (lines 33-36)
**Tokens:** 25

```php
    public function delete(int $id): void
    {
        Category::findOrFail($id)->delete();
    }
```

## fetched · named_reference

**Subject:** App\Repositories\CategoryRepository
**Reason:** the region depends on App\Repositories\CategoryRepository, whose contract is defined in another file
**Source:** `app/Repositories/CategoryRepository.php` :: `getAll` (lines 10-13)
**Tokens:** 26

```php
    public function getAll(): Collection
    {
        return Category::orderBy('order')->get();
    }
```

## fetched · named_reference

**Subject:** App\Repositories\CategoryRepository
**Reason:** the region depends on App\Repositories\CategoryRepository, whose contract is defined in another file
**Source:** `app/Repositories/CategoryRepository.php` :: `getById` (lines 15-18)
**Tokens:** 25

```php
    public function getById(int $id): Category
    {
        return Category::findOrFail($id);
    }
```

## fetched · named_reference

**Subject:** App\Repositories\CategoryRepository
**Reason:** the region depends on App\Repositories\CategoryRepository, whose contract is defined in another file
**Source:** `app/Repositories/CategoryRepository.php` :: `update` (lines 25-31)
**Tokens:** 45

```php
    public function update(int $id, array $data): Category
    {
        $category = Category::findOrFail($id);
        $category->update($data);

        return $category;
    }
```

## Dropped

Nothing was dropped.
===== END context-bundle.md =====

===== BEGIN context-diagnostics.txt =====
new file: app/Http/Controllers/Admin/Auth/AuthController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Admin/BranchController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Admin/CategoryController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Admin/Catering/CateringPackageController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Admin/Catering/QuoteRequestController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Admin/Catering/SampleMenuController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Admin/DeliveryAppController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Admin/DishController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Admin/MediaItemController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Admin/PageContentController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Admin/SettingController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Admin/TestimonialController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Admin/TimelineController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Admin/UserController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Public/BranchController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Public/CategoryController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Public/Catering/CateringPackageController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Public/Catering/QuoteRequestController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Public/Catering/SampleMenuController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Public/DeliveryAppController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Public/DishController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Public/MediaItemController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Public/PageContentController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Public/SettingController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Public/TestimonialController.php — own-file context is in the diff, not fetched
new file: app/Http/Controllers/Public/TimelineController.php — own-file context is in the diff, not fetched
new file: routes/admin.php — own-file context is in the diff, not fetched
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
unresolved named_reference: App\Http\Resources\Branch\BranchResource::collection in app/Http/Controllers/Public/BranchController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
unresolved named_reference: App\Http\Resources\Category\CategoryResource::collection in app/Http/Controllers/Public/CategoryController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
unresolved named_reference: App\Http\Resources\Catering\PackageResource::collection in app/Http/Controllers/Public/Catering/CateringPackageController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
unresolved named_reference: App\Http\Resources\Catering\SampleMenuResource::collection in app/Http/Controllers/Public/Catering/SampleMenuController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
unresolved named_reference: App\Http\Resources\DeliveryApp\DeliveryAppResource::collection in app/Http/Controllers/Public/DeliveryAppController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
unresolved named_reference: App\Http\Resources\Dish\DishResource::collection in app/Http/Controllers/Public/DishController.php
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
unresolved named_reference: App\Http\Resources\Testimonial\TestimonialResource::collection in app/Http/Controllers/Public/TestimonialController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
unresolved named_reference: App\Http\Resources\Timeline\TimelineResource::collection in app/Http/Controllers/Public/TimelineController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
framework reference: Illuminate\Support\Facades\Route::middleware declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:96 (@method static \Illuminate\Routing\RouteRegistrar middleware(array|string|null $middleware))
framework reference: Illuminate\Support\Facades\Route::prefix declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:100 (@method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix))
framework reference: Illuminate\Support\Facades\Route::post declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:7 (@method static \Illuminate\Routing\Route post(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::middleware declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:96 (@method static \Illuminate\Routing\RouteRegistrar middleware(array|string|null $middleware))
framework reference: Illuminate\Support\Facades\Route::get declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:6 (@method static \Illuminate\Routing\Route get(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::put declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:8 (@method static \Illuminate\Routing\Route put(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::delete declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:10 (@method static \Illuminate\Routing\Route delete(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::patch declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:9 (@method static \Illuminate\Routing\Route patch(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::prefix declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:100 (@method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix))
framework reference: Illuminate\Support\Facades\Route::get declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:6 (@method static \Illuminate\Routing\Route get(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::post declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:7 (@method static \Illuminate\Routing\Route post(string $uri, array|string|callable|null $action = null))
===== END context-diagnostics.txt =====
