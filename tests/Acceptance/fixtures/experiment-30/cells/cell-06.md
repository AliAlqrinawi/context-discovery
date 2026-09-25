<!-- cell-06 -->
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
index e536c5f..a7f1bd0 100644
--- a/app/Http/Controllers/Admin/Auth/AuthController.php
+++ b/app/Http/Controllers/Admin/Auth/AuthController.php
@@ -2,4 +2,34 @@
 
 namespace App\Http\Controllers\Admin\Auth;
 
-class AuthController extends \App\Http\Controllers\Auth\AuthController {}
+use App\Actions\Auth\LoginAction;
+use App\Actions\Auth\LogoutAction;
+use App\DTOs\Auth\LoginDTO;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\Auth\LoginRequest;
+use App\Http\Resources\Auth\AuthResource;
+use App\Http\Resources\User\UserResource;
+use Illuminate\Http\JsonResponse;
+use Illuminate\Http\Request;
+
+class AuthController extends Controller
+{
+    public function login(LoginRequest $request, LoginAction $action): JsonResponse
+    {
+        $result = $action->execute(LoginDTO::fromRequest($request));
+
+        return $this->success(new AuthResource($result), __('messages.login_success'));
+    }
+
+    public function logout(Request $request, LogoutAction $action): JsonResponse
+    {
+        $action->execute($request->user());
+
+        return $this->success(null, __('messages.logout_success'));
+    }
+
+    public function me(Request $request): JsonResponse
+    {
+        return $this->success(new UserResource($request->user()), __('messages.fetched'));
+    }
+}
diff --git a/app/Http/Controllers/Admin/BranchController.php b/app/Http/Controllers/Admin/BranchController.php
index 7aa58ff..7a18f7a 100644
--- a/app/Http/Controllers/Admin/BranchController.php
+++ b/app/Http/Controllers/Admin/BranchController.php
@@ -2,4 +2,49 @@
 
 namespace App\Http\Controllers\Admin;
 
-class BranchController extends \App\Http\Controllers\BranchController {}
+use App\Actions\Branch\CreateBranchAction;
+use App\Actions\Branch\DeleteBranchAction;
+use App\Actions\Branch\GetBranchesAction;
+use App\Actions\Branch\UpdateBranchAction;
+use App\DTOs\Branch\CreateBranchDTO;
+use App\DTOs\Branch\UpdateBranchDTO;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\Branch\StoreBranchRequest;
+use App\Http\Requests\Branch\UpdateBranchRequest;
+use App\Http\Resources\Branch\BranchResource;
+use App\Repositories\BranchRepository;
+use Illuminate\Http\JsonResponse;
+
+class BranchController extends Controller
+{
+    public function index(GetBranchesAction $action): JsonResponse
+    {
+        return $this->success(BranchResource::collection($action->execute()), __('messages.fetched'));
+    }
+
+    public function show(int $id, BranchRepository $repository): JsonResponse
+    {
+        return $this->success(new BranchResource($repository->getById($id)), __('messages.fetched'));
+    }
+
+    public function store(StoreBranchRequest $request, CreateBranchAction $action): JsonResponse
+    {
+        $branch = $action->execute(CreateBranchDTO::fromRequest($request));
+
+        return $this->created(new BranchResource($branch), __('messages.created'));
+    }
+
+    public function update(UpdateBranchRequest $request, int $id, UpdateBranchAction $action): JsonResponse
+    {
+        $branch = $action->execute($id, UpdateBranchDTO::fromRequest($request));
+
+        return $this->success(new BranchResource($branch), __('messages.updated'));
+    }
+
+    public function destroy(int $id, DeleteBranchAction $action): JsonResponse
+    {
+        $action->execute($id);
+
+        return $this->deleted(__('messages.deleted'));
+    }
+}
diff --git a/app/Http/Controllers/Admin/CategoryController.php b/app/Http/Controllers/Admin/CategoryController.php
index dca0eb7..4e0ca40 100644
--- a/app/Http/Controllers/Admin/CategoryController.php
+++ b/app/Http/Controllers/Admin/CategoryController.php
@@ -2,4 +2,41 @@
 
 namespace App\Http\Controllers\Admin;
 
-class CategoryController extends \App\Http\Controllers\CategoryController {}
+use App\Http\Controllers\Controller;
+use App\Http\Resources\Category\CategoryResource;
+use App\Repositories\CategoryRepository;
+use Illuminate\Http\JsonResponse;
+use Illuminate\Http\Request;
+
+class CategoryController extends Controller
+{
+    public function __construct(
+        private readonly CategoryRepository $repository,
+    ) {}
+
+    public function index(): JsonResponse
+    {
+        return $this->success(CategoryResource::collection($this->repository->getAll()), __('messages.fetched'));
+    }
+
+    public function store(Request $request): JsonResponse
+    {
+        $category = $this->repository->create($request->only(['name_ar', 'name_en', 'slug', 'order']));
+
+        return $this->created(new CategoryResource($category), __('messages.created'));
+    }
+
+    public function update(Request $request, int $id): JsonResponse
+    {
+        $category = $this->repository->update($id, $request->only(['name_ar', 'name_en', 'slug', 'order']));
+
+        return $this->success(new CategoryResource($category), __('messages.updated'));
+    }
+
+    public function destroy(int $id): JsonResponse
+    {
+        $this->repository->delete($id);
+
+        return $this->deleted(__('messages.deleted'));
+    }
+}
diff --git a/app/Http/Controllers/Admin/Catering/CateringPackageController.php b/app/Http/Controllers/Admin/Catering/CateringPackageController.php
index c1d7485..78a9df5 100644
--- a/app/Http/Controllers/Admin/Catering/CateringPackageController.php
+++ b/app/Http/Controllers/Admin/Catering/CateringPackageController.php
@@ -2,4 +2,43 @@
 
 namespace App\Http\Controllers\Admin\Catering;
 
-class CateringPackageController extends \App\Http\Controllers\Catering\CateringPackageController {}
+use App\Actions\Catering\CreatePackageAction;
+use App\Actions\Catering\DeletePackageAction;
+use App\Actions\Catering\GetPackagesAction;
+use App\Actions\Catering\UpdatePackageAction;
+use App\DTOs\Catering\CreatePackageDTO;
+use App\DTOs\Catering\UpdatePackageDTO;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\Catering\StorePackageRequest;
+use App\Http\Requests\Catering\UpdatePackageRequest;
+use App\Http\Resources\Catering\PackageResource;
+use Illuminate\Http\JsonResponse;
+
+class CateringPackageController extends Controller
+{
+    public function index(GetPackagesAction $action): JsonResponse
+    {
+        return $this->success(PackageResource::collection($action->execute()), __('messages.fetched'));
+    }
+
+    public function store(StorePackageRequest $request, CreatePackageAction $action): JsonResponse
+    {
+        $package = $action->execute(CreatePackageDTO::fromRequest($request));
+
+        return $this->created(new PackageResource($package), __('messages.created'));
+    }
+
+    public function update(UpdatePackageRequest $request, int $id, UpdatePackageAction $action): JsonResponse
+    {
+        $package = $action->execute($id, UpdatePackageDTO::fromRequest($request));
+
+        return $this->success(new PackageResource($package), __('messages.updated'));
+    }
+
+    public function destroy(int $id, DeletePackageAction $action): JsonResponse
+    {
+        $action->execute($id);
+
+        return $this->deleted(__('messages.deleted'));
+    }
+}
diff --git a/app/Http/Controllers/Admin/Catering/QuoteRequestController.php b/app/Http/Controllers/Admin/Catering/QuoteRequestController.php
index 6912f5f..e1da7ca 100644
--- a/app/Http/Controllers/Admin/Catering/QuoteRequestController.php
+++ b/app/Http/Controllers/Admin/Catering/QuoteRequestController.php
@@ -2,4 +2,39 @@
 
 namespace App\Http\Controllers\Admin\Catering;
 
-class QuoteRequestController extends \App\Http\Controllers\Catering\QuoteRequestController {}
+use App\Actions\Catering\UpdateQuoteRequestStatusAction;
+use App\DTOs\Catering\UpdateQuoteRequestStatusDTO;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\Catering\UpdateQuoteRequestStatusRequest;
+use App\Http\Resources\Catering\QuoteRequestResource;
+use App\Repositories\QuoteRequestRepository;
+use Illuminate\Http\JsonResponse;
+use Illuminate\Http\Request;
+
+class QuoteRequestController extends Controller
+{
+    public function index(Request $request, QuoteRequestRepository $repository): JsonResponse
+    {
+        $filters = array_filter([
+            'status' => $request->filled('status') ? $request->string('status')->toString() : null,
+            'event_type' => $request->filled('event_type') ? $request->string('event_type')->toString() : null,
+            'branch' => $request->filled('branch') ? $request->string('branch')->toString() : null,
+        ]);
+
+        $result = $repository->getAll($filters);
+
+        return $this->paginated(QuoteRequestResource::collection($result), $result, __('messages.fetched'));
+    }
+
+    public function show(int $id, QuoteRequestRepository $repository): JsonResponse
+    {
+        return $this->success(new QuoteRequestResource($repository->getById($id)), __('messages.fetched'));
+    }
+
+    public function updateStatus(UpdateQuoteRequestStatusRequest $request, int $id, UpdateQuoteRequestStatusAction $action): JsonResponse
+    {
+        $quoteRequest = $action->execute($id, UpdateQuoteRequestStatusDTO::fromRequest($request));
+
+        return $this->success(new QuoteRequestResource($quoteRequest), __('messages.updated'));
+    }
+}
diff --git a/app/Http/Controllers/Admin/Catering/SampleMenuController.php b/app/Http/Controllers/Admin/Catering/SampleMenuController.php
index 16c3a27..441b77a 100644
--- a/app/Http/Controllers/Admin/Catering/SampleMenuController.php
+++ b/app/Http/Controllers/Admin/Catering/SampleMenuController.php
@@ -2,4 +2,41 @@
 
 namespace App\Http\Controllers\Admin\Catering;
 
-class SampleMenuController extends \App\Http\Controllers\Catering\SampleMenuController {}
+use App\Actions\Catering\CreateSampleMenuAction;
+use App\Actions\Catering\DeleteSampleMenuAction;
+use App\Actions\Catering\GetSampleMenusAction;
+use App\Actions\Catering\UpdateSampleMenuAction;
+use App\DTOs\Catering\CreateSampleMenuDTO;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\Catering\StoreSampleMenuRequest;
+use App\Http\Resources\Catering\SampleMenuResource;
+use Illuminate\Http\JsonResponse;
+
+class SampleMenuController extends Controller
+{
+    public function index(GetSampleMenusAction $action): JsonResponse
+    {
+        return $this->success(SampleMenuResource::collection($action->execute()), __('messages.fetched'));
+    }
+
+    public function store(StoreSampleMenuRequest $request, CreateSampleMenuAction $action): JsonResponse
+    {
+        $menu = $action->execute(CreateSampleMenuDTO::fromRequest($request));
+
+        return $this->created(new SampleMenuResource($menu), __('messages.created'));
+    }
+
+    public function update(StoreSampleMenuRequest $request, int $id, UpdateSampleMenuAction $action): JsonResponse
+    {
+        $menu = $action->execute($id, CreateSampleMenuDTO::fromRequest($request));
+
+        return $this->success(new SampleMenuResource($menu), __('messages.updated'));
+    }
+
+    public function destroy(int $id, DeleteSampleMenuAction $action): JsonResponse
+    {
+        $action->execute($id);
+
+        return $this->deleted(__('messages.deleted'));
+    }
+}
diff --git a/app/Http/Controllers/Admin/DeliveryAppController.php b/app/Http/Controllers/Admin/DeliveryAppController.php
index bbfd30f..cf9230d 100644
--- a/app/Http/Controllers/Admin/DeliveryAppController.php
+++ b/app/Http/Controllers/Admin/DeliveryAppController.php
@@ -2,4 +2,41 @@
 
 namespace App\Http\Controllers\Admin;
 
-class DeliveryAppController extends \App\Http\Controllers\DeliveryAppController {}
+use App\Actions\DeliveryApp\CreateDeliveryAppAction;
+use App\Actions\DeliveryApp\DeleteDeliveryAppAction;
+use App\Actions\DeliveryApp\GetDeliveryAppsAction;
+use App\Actions\DeliveryApp\UpdateDeliveryAppAction;
+use App\DTOs\DeliveryApp\CreateDeliveryAppDTO;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\DeliveryApp\StoreDeliveryAppRequest;
+use App\Http\Resources\DeliveryApp\DeliveryAppResource;
+use Illuminate\Http\JsonResponse;
+
+class DeliveryAppController extends Controller
+{
+    public function index(GetDeliveryAppsAction $action): JsonResponse
+    {
+        return $this->success(DeliveryAppResource::collection($action->execute(activeOnly: false)), __('messages.fetched'));
+    }
+
+    public function store(StoreDeliveryAppRequest $request, CreateDeliveryAppAction $action): JsonResponse
+    {
+        $deliveryApp = $action->execute(CreateDeliveryAppDTO::fromRequest($request));
+
+        return $this->created(new DeliveryAppResource($deliveryApp), __('messages.created'));
+    }
+
+    public function update(StoreDeliveryAppRequest $request, int $id, UpdateDeliveryAppAction $action): JsonResponse
+    {
+        $deliveryApp = $action->execute($id, CreateDeliveryAppDTO::fromRequest($request));
+
+        return $this->success(new DeliveryAppResource($deliveryApp), __('messages.updated'));
+    }
+
+    public function destroy(int $id, DeleteDeliveryAppAction $action): JsonResponse
+    {
+        $action->execute($id);
+
+        return $this->deleted(__('messages.deleted'));
+    }
+}
diff --git a/app/Http/Controllers/Admin/DishController.php b/app/Http/Controllers/Admin/DishController.php
index 5fb0394..0999745 100644
--- a/app/Http/Controllers/Admin/DishController.php
+++ b/app/Http/Controllers/Admin/DishController.php
@@ -2,4 +2,59 @@
 
 namespace App\Http\Controllers\Admin;
 
-class DishController extends \App\Http\Controllers\DishController {}
+use App\Actions\Dish\CreateDishAction;
+use App\Actions\Dish\DeleteDishAction;
+use App\Actions\Dish\GetDishAction;
+use App\Actions\Dish\GetDishesAction;
+use App\Actions\Dish\UpdateDishAction;
+use App\DTOs\Dish\CreateDishDTO;
+use App\DTOs\Dish\UpdateDishDTO;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\Dish\StoreDishRequest;
+use App\Http\Requests\Dish\UpdateDishRequest;
+use App\Http\Resources\Dish\DishResource;
+use Illuminate\Http\JsonResponse;
+use Illuminate\Http\Request;
+
+class DishController extends Controller
+{
+    public function index(Request $request, GetDishesAction $action): JsonResponse
+    {
+        $filters = array_filter([
+            'category_id' => $request->integer('category_id') ?: null,
+            'featured' => $request->has('featured') ? $request->boolean('featured') : null,
+            'signature' => $request->has('signature') ? $request->boolean('signature') : null,
+            'per_page' => $request->integer('per_page') ?: null,
+        ], fn ($value) => $value !== null);
+
+        $result = $action->execute($filters);
+
+        return $this->paginated(DishResource::collection($result), $result, __('messages.fetched'));
+    }
+
+    public function show(int $id, GetDishAction $action): JsonResponse
+    {
+        return $this->success(new DishResource($action->execute($id)), __('messages.fetched'));
+    }
+
+    public function store(StoreDishRequest $request, CreateDishAction $action): JsonResponse
+    {
+        $dish = $action->execute(CreateDishDTO::fromRequest($request));
+
+        return $this->created(new DishResource($dish), __('messages.created'));
+    }
+
+    public function update(UpdateDishRequest $request, int $id, UpdateDishAction $action): JsonResponse
+    {
+        $dish = $action->execute($id, UpdateDishDTO::fromRequest($request));
+
+        return $this->success(new DishResource($dish), __('messages.updated'));
+    }
+
+    public function destroy(int $id, DeleteDishAction $action): JsonResponse
+    {
+        $action->execute($id);
+
+        return $this->deleted(__('messages.deleted'));
+    }
+}
diff --git a/app/Http/Controllers/Admin/MediaItemController.php b/app/Http/Controllers/Admin/MediaItemController.php
index b965e63..87a24a5 100644
--- a/app/Http/Controllers/Admin/MediaItemController.php
+++ b/app/Http/Controllers/Admin/MediaItemController.php
@@ -2,4 +2,56 @@
 
 namespace App\Http\Controllers\Admin;
 
-class MediaItemController extends \App\Http\Controllers\MediaItemController {}
+use App\Actions\MediaItem\DeleteMediaAction;
+use App\Actions\MediaItem\GetMediaItemsAction;
+use App\Actions\MediaItem\UploadMediaAction;
+use App\DTOs\MediaItem\UploadMediaDTO;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\MediaItem\UploadMediaRequest;
+use App\Http\Resources\MediaItem\MediaItemResource;
+use App\Repositories\MediaItemRepository;
+use Illuminate\Http\JsonResponse;
+use Illuminate\Http\Request;
+
+class MediaItemController extends Controller
+{
+    public function index(Request $request, GetMediaItemsAction $action): JsonResponse
+    {
+        $result = $action->execute(
+            $request->string('page')->toString(),
+            $request->filled('section') ? $request->string('section')->toString() : null,
+        );
+
+        $resourced = $result->map(
+            fn ($section) => $section->map(fn ($item) => new MediaItemResource($item))
+        );
+
+        return $this->success($resourced, __('messages.fetched'));
+    }
+
+    public function store(UploadMediaRequest $request, UploadMediaAction $action): JsonResponse
+    {
+        $mediaItem = $action->execute(UploadMediaDTO::fromRequest($request));
+
+        return $this->created(new MediaItemResource($mediaItem), __('messages.uploaded'));
+    }
+
+    public function show(int $id, MediaItemRepository $repository): JsonResponse
+    {
+        return $this->success(new MediaItemResource($repository->getById($id)), __('messages.fetched'));
+    }
+
+    public function update(UploadMediaRequest $request, int $id, UploadMediaAction $action): JsonResponse
+    {
+        $mediaItem = $action->execute(UploadMediaDTO::fromRequest($request));
+
+        return $this->success(new MediaItemResource($mediaItem), __('messages.updated'));
+    }
+
+    public function destroy(int $id, DeleteMediaAction $action): JsonResponse
+    {
+        $action->execute($id);
+
+        return $this->deleted(__('messages.deleted'));
+    }
+}
diff --git a/app/Http/Controllers/Admin/PageContentController.php b/app/Http/Controllers/Admin/PageContentController.php
index 6db51e9..a78f9b3 100644
--- a/app/Http/Controllers/Admin/PageContentController.php
+++ b/app/Http/Controllers/Admin/PageContentController.php
@@ -2,4 +2,51 @@
 
 namespace App\Http\Controllers\Admin;
 
-class PageContentController extends \App\Http\Controllers\PageContentController {}
+use App\Actions\PageContent\BulkUpdatePageContentAction;
+use App\Actions\PageContent\GetPageContentAction;
+use App\Actions\PageContent\UpdatePageContentAction;
+use App\DTOs\PageContent\BulkUpdatePageContentDTO;
+use App\DTOs\PageContent\UpdatePageContentDTO;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\PageContent\BulkUpdatePageContentRequest;
+use App\Http\Requests\PageContent\UpdatePageContentRequest;
+use App\Http\Resources\PageContent\PageContentResource;
+use App\Repositories\PageContentRepository;
+use Illuminate\Http\JsonResponse;
+use Illuminate\Http\Request;
+
+class PageContentController extends Controller
+{
+    public function index(Request $request, GetPageContentAction $action): JsonResponse
+    {
+        $result = $action->execute(
+            $request->string('page')->toString(),
+            $request->filled('section') ? $request->string('section')->toString() : null,
+        );
+
+        $resourced = $result->map(
+            fn ($section) => $section->map(fn ($item) => new PageContentResource($item))
+        );
+
+        return $this->success($resourced, __('messages.fetched'));
+    }
+
+    public function show(int $id, PageContentRepository $repository): JsonResponse
+    {
+        return $this->success(new PageContentResource($repository->getById($id)), __('messages.fetched'));
+    }
+
+    public function update(UpdatePageContentRequest $request, int $id, UpdatePageContentAction $action): JsonResponse
+    {
+        $pageContent = $action->execute($id, UpdatePageContentDTO::fromRequest($request));
+
+        return $this->success(new PageContentResource($pageContent), __('messages.updated'));
+    }
+
+    public function bulkUpdate(BulkUpdatePageContentRequest $request, BulkUpdatePageContentAction $action): JsonResponse
+    {
+        $action->execute(BulkUpdatePageContentDTO::fromRequest($request));
+
+        return $this->success(null, __('messages.updated'));
+    }
+}
diff --git a/app/Http/Controllers/Admin/SettingController.php b/app/Http/Controllers/Admin/SettingController.php
index d5c9e79..bd10977 100644
--- a/app/Http/Controllers/Admin/SettingController.php
+++ b/app/Http/Controllers/Admin/SettingController.php
@@ -2,4 +2,30 @@
 
 namespace App\Http\Controllers\Admin;
 
-class SettingController extends \App\Http\Controllers\SettingController {}
+use App\Actions\Setting\GetSettingsAction;
+use App\Actions\Setting\UpdateSettingsAction;
+use App\DTOs\Setting\UpdateSettingDTO;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\Setting\UpdateSettingRequest;
+use App\Http\Resources\Setting\SettingResource;
+use Illuminate\Http\JsonResponse;
+use Illuminate\Http\Request;
+
+class SettingController extends Controller
+{
+    public function index(Request $request, GetSettingsAction $action): JsonResponse
+    {
+        $result = $action->execute(
+            $request->filled('group') ? $request->string('group')->toString() : null,
+        );
+
+        return $this->success($result->map(fn ($setting) => new SettingResource($setting)), __('messages.fetched'));
+    }
+
+    public function bulkUpdate(UpdateSettingRequest $request, UpdateSettingsAction $action): JsonResponse
+    {
+        $action->execute(UpdateSettingDTO::fromRequest($request));
+
+        return $this->success(null, __('messages.updated'));
+    }
+}
diff --git a/app/Http/Controllers/Admin/TestimonialController.php b/app/Http/Controllers/Admin/TestimonialController.php
index 3b6066d..71629f3 100644
--- a/app/Http/Controllers/Admin/TestimonialController.php
+++ b/app/Http/Controllers/Admin/TestimonialController.php
@@ -2,4 +2,41 @@
 
 namespace App\Http\Controllers\Admin;
 
-class TestimonialController extends \App\Http\Controllers\TestimonialController {}
+use App\Actions\Testimonial\CreateTestimonialAction;
+use App\Actions\Testimonial\DeleteTestimonialAction;
+use App\Actions\Testimonial\GetTestimonialsAction;
+use App\Actions\Testimonial\UpdateTestimonialAction;
+use App\DTOs\Testimonial\CreateTestimonialDTO;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\Testimonial\StoreTestimonialRequest;
+use App\Http\Resources\Testimonial\TestimonialResource;
+use Illuminate\Http\JsonResponse;
+
+class TestimonialController extends Controller
+{
+    public function index(GetTestimonialsAction $action): JsonResponse
+    {
+        return $this->success(TestimonialResource::collection($action->execute(activeOnly: false)), __('messages.fetched'));
+    }
+
+    public function store(StoreTestimonialRequest $request, CreateTestimonialAction $action): JsonResponse
+    {
+        $testimonial = $action->execute(CreateTestimonialDTO::fromRequest($request));
+
+        return $this->created(new TestimonialResource($testimonial), __('messages.created'));
+    }
+
+    public function update(StoreTestimonialRequest $request, int $id, UpdateTestimonialAction $action): JsonResponse
+    {
+        $testimonial = $action->execute($id, CreateTestimonialDTO::fromRequest($request));
+
+        return $this->success(new TestimonialResource($testimonial), __('messages.updated'));
+    }
+
+    public function destroy(int $id, DeleteTestimonialAction $action): JsonResponse
+    {
+        $action->execute($id);
+
+        return $this->deleted(__('messages.deleted'));
+    }
+}
diff --git a/app/Http/Controllers/Admin/TimelineController.php b/app/Http/Controllers/Admin/TimelineController.php
index 2ab8f09..1388d35 100644
--- a/app/Http/Controllers/Admin/TimelineController.php
+++ b/app/Http/Controllers/Admin/TimelineController.php
@@ -2,4 +2,41 @@
 
 namespace App\Http\Controllers\Admin;
 
-class TimelineController extends \App\Http\Controllers\TimelineController {}
+use App\Actions\Timeline\CreateTimelineAction;
+use App\Actions\Timeline\DeleteTimelineAction;
+use App\Actions\Timeline\GetTimelineAction;
+use App\Actions\Timeline\UpdateTimelineAction;
+use App\DTOs\Timeline\CreateTimelineDTO;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\Timeline\StoreTimelineRequest;
+use App\Http\Resources\Timeline\TimelineResource;
+use Illuminate\Http\JsonResponse;
+
+class TimelineController extends Controller
+{
+    public function index(GetTimelineAction $action): JsonResponse
+    {
+        return $this->success(TimelineResource::collection($action->execute()), __('messages.fetched'));
+    }
+
+    public function store(StoreTimelineRequest $request, CreateTimelineAction $action): JsonResponse
+    {
+        $timeline = $action->execute(CreateTimelineDTO::fromRequest($request));
+
+        return $this->created(new TimelineResource($timeline), __('messages.created'));
+    }
+
+    public function update(StoreTimelineRequest $request, int $id, UpdateTimelineAction $action): JsonResponse
+    {
+        $timeline = $action->execute($id, CreateTimelineDTO::fromRequest($request));
+
+        return $this->success(new TimelineResource($timeline), __('messages.updated'));
+    }
+
+    public function destroy(int $id, DeleteTimelineAction $action): JsonResponse
+    {
+        $action->execute($id);
+
+        return $this->deleted(__('messages.deleted'));
+    }
+}
diff --git a/app/Http/Controllers/Admin/UserController.php b/app/Http/Controllers/Admin/UserController.php
index 8ab19a7..78523b8 100644
--- a/app/Http/Controllers/Admin/UserController.php
+++ b/app/Http/Controllers/Admin/UserController.php
@@ -2,4 +2,51 @@
 
 namespace App\Http\Controllers\Admin;
 
-class UserController extends \App\Http\Controllers\UserController {}
+use App\Actions\User\CreateUserAction;
+use App\Actions\User\DeleteUserAction;
+use App\Actions\User\GetUsersAction;
+use App\Actions\User\UpdateUserAction;
+use App\DTOs\User\CreateUserDTO;
+use App\DTOs\User\UpdateUserDTO;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\User\StoreUserRequest;
+use App\Http\Requests\User\UpdateUserRequest;
+use App\Http\Resources\User\UserResource;
+use App\Repositories\UserRepository;
+use Illuminate\Http\JsonResponse;
+
+class UserController extends Controller
+{
+    public function index(GetUsersAction $action): JsonResponse
+    {
+        $result = $action->execute();
+
+        return $this->paginated(UserResource::collection($result), $result, __('messages.fetched'));
+    }
+
+    public function store(StoreUserRequest $request, CreateUserAction $action): JsonResponse
+    {
+        $user = $action->execute(CreateUserDTO::fromRequest($request));
+
+        return $this->created(new UserResource($user), __('messages.created'));
+    }
+
+    public function show(int $id, UserRepository $repository): JsonResponse
+    {
+        return $this->success(new UserResource($repository->getById($id)), __('messages.fetched'));
+    }
+
+    public function update(UpdateUserRequest $request, int $id, UpdateUserAction $action): JsonResponse
+    {
+        $user = $action->execute($id, UpdateUserDTO::fromRequest($request));
+
+        return $this->success(new UserResource($user), __('messages.updated'));
+    }
+
+    public function destroy(int $id, DeleteUserAction $action): JsonResponse
+    {
+        $action->execute($id);
+
+        return $this->deleted(__('messages.deleted'));
+    }
+}
diff --git a/app/Http/Controllers/Auth/AuthController.php b/app/Http/Controllers/Auth/AuthController.php
deleted file mode 100644
index 563551d..0000000
--- a/app/Http/Controllers/Auth/AuthController.php
+++ /dev/null
@@ -1,35 +0,0 @@
-<?php
-
-namespace App\Http\Controllers\Auth;
-
-use App\Actions\Auth\LoginAction;
-use App\Actions\Auth\LogoutAction;
-use App\DTOs\Auth\LoginDTO;
-use App\Http\Controllers\Controller;
-use App\Http\Requests\Auth\LoginRequest;
-use App\Http\Resources\Auth\AuthResource;
-use App\Http\Resources\User\UserResource;
-use Illuminate\Http\JsonResponse;
-use Illuminate\Http\Request;
-
-class AuthController extends Controller
-{
-    public function login(LoginRequest $request, LoginAction $action): JsonResponse
-    {
-        $result = $action->execute(LoginDTO::fromRequest($request));
-
-        return $this->success(new AuthResource($result), __('messages.login_success'));
-    }
-
-    public function logout(Request $request, LogoutAction $action): JsonResponse
-    {
-        $action->execute($request->user());
-
-        return $this->success(null, __('messages.logout_success'));
-    }
-
-    public function me(Request $request): JsonResponse
-    {
-        return $this->success(new UserResource($request->user()), __('messages.fetched'));
-    }
-}
diff --git a/app/Http/Controllers/BranchController.php b/app/Http/Controllers/BranchController.php
deleted file mode 100644
index 985e499..0000000
--- a/app/Http/Controllers/BranchController.php
+++ /dev/null
@@ -1,49 +0,0 @@
-<?php
-
-namespace App\Http\Controllers;
-
-use App\Actions\Branch\CreateBranchAction;
-use App\Actions\Branch\DeleteBranchAction;
-use App\Actions\Branch\GetBranchesAction;
-use App\Actions\Branch\UpdateBranchAction;
-use App\DTOs\Branch\CreateBranchDTO;
-use App\DTOs\Branch\UpdateBranchDTO;
-use App\Http\Requests\Branch\StoreBranchRequest;
-use App\Http\Requests\Branch\UpdateBranchRequest;
-use App\Http\Resources\Branch\BranchResource;
-use App\Repositories\BranchRepository;
-use Illuminate\Http\JsonResponse;
-
-class BranchController extends Controller
-{
-    public function index(GetBranchesAction $action): JsonResponse
-    {
-        return $this->success(BranchResource::collection($action->execute()), __('messages.fetched'));
-    }
-
-    public function show(int $id, BranchRepository $repository): JsonResponse
-    {
-        return $this->success(new BranchResource($repository->getById($id)), __('messages.fetched'));
-    }
-
-    public function store(StoreBranchRequest $request, CreateBranchAction $action): JsonResponse
-    {
-        $branch = $action->execute(CreateBranchDTO::fromRequest($request));
-
-        return $this->created(new BranchResource($branch), __('messages.created'));
-    }
-
-    public function update(UpdateBranchRequest $request, int $id, UpdateBranchAction $action): JsonResponse
-    {
-        $branch = $action->execute($id, UpdateBranchDTO::fromRequest($request));
-
-        return $this->success(new BranchResource($branch), __('messages.updated'));
-    }
-
-    public function destroy(int $id, DeleteBranchAction $action): JsonResponse
-    {
-        $action->execute($id);
-
-        return $this->deleted(__('messages.deleted'));
-    }
-}
diff --git a/app/Http/Controllers/CategoryController.php b/app/Http/Controllers/CategoryController.php
deleted file mode 100644
index bf1c2f7..0000000
--- a/app/Http/Controllers/CategoryController.php
+++ /dev/null
@@ -1,41 +0,0 @@
-<?php
-
-namespace App\Http\Controllers;
-
-use App\Http\Resources\Category\CategoryResource;
-use App\Repositories\CategoryRepository;
-use Illuminate\Http\JsonResponse;
-use Illuminate\Http\Request;
-
-class CategoryController extends Controller
-{
-    public function __construct(
-        private readonly CategoryRepository $repository,
-    ) {}
-
-    public function index(): JsonResponse
-    {
-        return $this->success(CategoryResource::collection($this->repository->getAll()), __('messages.fetched'));
-    }
-
-    public function store(Request $request): JsonResponse
-    {
-        $category = $this->repository->create($request->only(['name_ar', 'name_en', 'slug', 'order']));
-
-        return $this->created(new CategoryResource($category), __('messages.created'));
-    }
-
-    public function update(Request $request, int $id): JsonResponse
-    {
-        $category = $this->repository->update($id, $request->only(['name_ar', 'name_en', 'slug', 'order']));
-
-        return $this->success(new CategoryResource($category), __('messages.updated'));
-    }
-
-    public function destroy(int $id): JsonResponse
-    {
-        $this->repository->delete($id);
-
-        return $this->deleted(__('messages.deleted'));
-    }
-}
diff --git a/app/Http/Controllers/Catering/CateringPackageController.php b/app/Http/Controllers/Catering/CateringPackageController.php
deleted file mode 100644
index 83dfaca..0000000
--- a/app/Http/Controllers/Catering/CateringPackageController.php
+++ /dev/null
@@ -1,44 +0,0 @@
-<?php
-
-namespace App\Http\Controllers\Catering;
-
-use App\Actions\Catering\CreatePackageAction;
-use App\Actions\Catering\DeletePackageAction;
-use App\Actions\Catering\GetPackagesAction;
-use App\Actions\Catering\UpdatePackageAction;
-use App\DTOs\Catering\CreatePackageDTO;
-use App\DTOs\Catering\UpdatePackageDTO;
-use App\Http\Controllers\Controller;
-use App\Http\Requests\Catering\StorePackageRequest;
-use App\Http\Requests\Catering\UpdatePackageRequest;
-use App\Http\Resources\Catering\PackageResource;
-use Illuminate\Http\JsonResponse;
-
-class CateringPackageController extends Controller
-{
-    public function index(GetPackagesAction $action): JsonResponse
-    {
-        return $this->success(PackageResource::collection($action->execute()), __('messages.fetched'));
-    }
-
-    public function store(StorePackageRequest $request, CreatePackageAction $action): JsonResponse
-    {
-        $package = $action->execute(CreatePackageDTO::fromRequest($request));
-
-        return $this->created(new PackageResource($package), __('messages.created'));
-    }
-
-    public function update(UpdatePackageRequest $request, int $id, UpdatePackageAction $action): JsonResponse
-    {
-        $package = $action->execute($id, UpdatePackageDTO::fromRequest($request));
-
-        return $this->success(new PackageResource($package), __('messages.updated'));
-    }
-
-    public function destroy(int $id, DeletePackageAction $action): JsonResponse
-    {
-        $action->execute($id);
-
-        return $this->deleted(__('messages.deleted'));
-    }
-}
diff --git a/app/Http/Controllers/Catering/QuoteRequestController.php b/app/Http/Controllers/Catering/QuoteRequestController.php
deleted file mode 100644
index e9aa8e2..0000000
--- a/app/Http/Controllers/Catering/QuoteRequestController.php
+++ /dev/null
@@ -1,50 +0,0 @@
-<?php
-
-namespace App\Http\Controllers\Catering;
-
-use App\Actions\Catering\SubmitQuoteRequestAction;
-use App\Actions\Catering\UpdateQuoteRequestStatusAction;
-use App\DTOs\Catering\SubmitQuoteRequestDTO;
-use App\DTOs\Catering\UpdateQuoteRequestStatusDTO;
-use App\Http\Controllers\Controller;
-use App\Http\Requests\Catering\StoreQuoteRequestRequest;
-use App\Http\Requests\Catering\UpdateQuoteRequestStatusRequest;
-use App\Http\Resources\Catering\QuoteRequestResource;
-use App\Repositories\QuoteRequestRepository;
-use Illuminate\Http\JsonResponse;
-use Illuminate\Http\Request;
-
-class QuoteRequestController extends Controller
-{
-    public function store(StoreQuoteRequestRequest $request, SubmitQuoteRequestAction $action): JsonResponse
-    {
-        $quoteRequest = $action->execute(SubmitQuoteRequestDTO::fromRequest($request));
-
-        return $this->created(new QuoteRequestResource($quoteRequest), __('messages.quote_submitted'));
-    }
-
-    public function index(Request $request, QuoteRequestRepository $repository): JsonResponse
-    {
-        $filters = array_filter([
-            'status' => $request->filled('status') ? $request->string('status')->toString() : null,
-            'event_type' => $request->filled('event_type') ? $request->string('event_type')->toString() : null,
-            'branch' => $request->filled('branch') ? $request->string('branch')->toString() : null,
-        ]);
-
-        $result = $repository->getAll($filters);
-
-        return $this->paginated(QuoteRequestResource::collection($result), $result, __('messages.fetched'));
-    }
-
-    public function show(int $id, QuoteRequestRepository $repository): JsonResponse
-    {
-        return $this->success(new QuoteRequestResource($repository->getById($id)), __('messages.fetched'));
-    }
-
-    public function updateStatus(UpdateQuoteRequestStatusRequest $request, int $id, UpdateQuoteRequestStatusAction $action): JsonResponse
-    {
-        $quoteRequest = $action->execute($id, UpdateQuoteRequestStatusDTO::fromRequest($request));
-
-        return $this->success(new QuoteRequestResource($quoteRequest), __('messages.updated'));
-    }
-}
diff --git a/app/Http/Controllers/Catering/SampleMenuController.php b/app/Http/Controllers/Catering/SampleMenuController.php
deleted file mode 100644
index f5b1fb0..0000000
--- a/app/Http/Controllers/Catering/SampleMenuController.php
+++ /dev/null
@@ -1,42 +0,0 @@
-<?php
-
-namespace App\Http\Controllers\Catering;
-
-use App\Actions\Catering\CreateSampleMenuAction;
-use App\Actions\Catering\DeleteSampleMenuAction;
-use App\Actions\Catering\GetSampleMenusAction;
-use App\Actions\Catering\UpdateSampleMenuAction;
-use App\DTOs\Catering\CreateSampleMenuDTO;
-use App\Http\Controllers\Controller;
-use App\Http\Requests\Catering\StoreSampleMenuRequest;
-use App\Http\Resources\Catering\SampleMenuResource;
-use Illuminate\Http\JsonResponse;
-
-class SampleMenuController extends Controller
-{
-    public function index(GetSampleMenusAction $action): JsonResponse
-    {
-        return $this->success(SampleMenuResource::collection($action->execute()), __('messages.fetched'));
-    }
-
-    public function store(StoreSampleMenuRequest $request, CreateSampleMenuAction $action): JsonResponse
-    {
-        $menu = $action->execute(CreateSampleMenuDTO::fromRequest($request));
-
-        return $this->created(new SampleMenuResource($menu), __('messages.created'));
-    }
-
-    public function update(StoreSampleMenuRequest $request, int $id, UpdateSampleMenuAction $action): JsonResponse
-    {
-        $menu = $action->execute($id, CreateSampleMenuDTO::fromRequest($request));
-
-        return $this->success(new SampleMenuResource($menu), __('messages.updated'));
-    }
-
-    public function destroy(int $id, DeleteSampleMenuAction $action): JsonResponse
-    {
-        $action->execute($id);
-
-        return $this->deleted(__('messages.deleted'));
-    }
-}
diff --git a/app/Http/Controllers/DeliveryAppController.php b/app/Http/Controllers/DeliveryAppController.php
deleted file mode 100644
index c2fca72..0000000
--- a/app/Http/Controllers/DeliveryAppController.php
+++ /dev/null
@@ -1,43 +0,0 @@
-<?php
-
-namespace App\Http\Controllers;
-
-use App\Actions\DeliveryApp\CreateDeliveryAppAction;
-use App\Actions\DeliveryApp\DeleteDeliveryAppAction;
-use App\Actions\DeliveryApp\GetDeliveryAppsAction;
-use App\Actions\DeliveryApp\UpdateDeliveryAppAction;
-use App\DTOs\DeliveryApp\CreateDeliveryAppDTO;
-use App\Http\Requests\DeliveryApp\StoreDeliveryAppRequest;
-use App\Http\Resources\DeliveryApp\DeliveryAppResource;
-use Illuminate\Http\JsonResponse;
-
-class DeliveryAppController extends Controller
-{
-    public function index(GetDeliveryAppsAction $action): JsonResponse
-    {
-        $activeOnly = ! auth('sanctum')->check();
-
-        return $this->success(DeliveryAppResource::collection($action->execute($activeOnly)), __('messages.fetched'));
-    }
-
-    public function store(StoreDeliveryAppRequest $request, CreateDeliveryAppAction $action): JsonResponse
-    {
-        $deliveryApp = $action->execute(CreateDeliveryAppDTO::fromRequest($request));
-
-        return $this->created(new DeliveryAppResource($deliveryApp), __('messages.created'));
-    }
-
-    public function update(StoreDeliveryAppRequest $request, int $id, UpdateDeliveryAppAction $action): JsonResponse
-    {
-        $deliveryApp = $action->execute($id, CreateDeliveryAppDTO::fromRequest($request));
-
-        return $this->success(new DeliveryAppResource($deliveryApp), __('messages.updated'));
-    }
-
-    public function destroy(int $id, DeleteDeliveryAppAction $action): JsonResponse
-    {
-        $action->execute($id);
-
-        return $this->deleted(__('messages.deleted'));
-    }
-}
diff --git a/app/Http/Controllers/DishController.php b/app/Http/Controllers/DishController.php
deleted file mode 100644
index b615bf0..0000000
--- a/app/Http/Controllers/DishController.php
+++ /dev/null
@@ -1,59 +0,0 @@
-<?php
-
-namespace App\Http\Controllers;
-
-use App\Actions\Dish\CreateDishAction;
-use App\Actions\Dish\DeleteDishAction;
-use App\Actions\Dish\GetDishAction;
-use App\Actions\Dish\GetDishesAction;
-use App\Actions\Dish\UpdateDishAction;
-use App\DTOs\Dish\CreateDishDTO;
-use App\DTOs\Dish\UpdateDishDTO;
-use App\Http\Requests\Dish\StoreDishRequest;
-use App\Http\Requests\Dish\UpdateDishRequest;
-use App\Http\Resources\Dish\DishResource;
-use Illuminate\Http\JsonResponse;
-use Illuminate\Http\Request;
-
-class DishController extends Controller
-{
-    public function index(Request $request, GetDishesAction $action): JsonResponse
-    {
-        $filters = array_filter([
-            'category_id' => $request->integer('category_id') ?: null,
-            'featured' => $request->has('featured') ? $request->boolean('featured') : null,
-            'signature' => $request->has('signature') ? $request->boolean('signature') : null,
-            'per_page' => $request->integer('per_page') ?: null,
-        ], fn ($value) => $value !== null);
-
-        $result = $action->execute($filters);
-
-        return $this->paginated(DishResource::collection($result), $result, __('messages.fetched'));
-    }
-
-    public function show(int $id, GetDishAction $action): JsonResponse
-    {
-        return $this->success(new DishResource($action->execute($id)), __('messages.fetched'));
-    }
-
-    public function store(StoreDishRequest $request, CreateDishAction $action): JsonResponse
-    {
-        $dish = $action->execute(CreateDishDTO::fromRequest($request));
-
-        return $this->created(new DishResource($dish), __('messages.created'));
-    }
-
-    public function update(UpdateDishRequest $request, int $id, UpdateDishAction $action): JsonResponse
-    {
-        $dish = $action->execute($id, UpdateDishDTO::fromRequest($request));
-
-        return $this->success(new DishResource($dish), __('messages.updated'));
-    }
-
-    public function destroy(int $id, DeleteDishAction $action): JsonResponse
-    {
-        $action->execute($id);
-
-        return $this->deleted(__('messages.deleted'));
-    }
-}
diff --git a/app/Http/Controllers/MediaItemController.php b/app/Http/Controllers/MediaItemController.php
deleted file mode 100644
index cf6933f..0000000
--- a/app/Http/Controllers/MediaItemController.php
+++ /dev/null
@@ -1,56 +0,0 @@
-<?php
-
-namespace App\Http\Controllers;
-
-use App\Actions\MediaItem\DeleteMediaAction;
-use App\Actions\MediaItem\GetMediaItemsAction;
-use App\Actions\MediaItem\UploadMediaAction;
-use App\DTOs\MediaItem\UploadMediaDTO;
-use App\Http\Requests\MediaItem\UploadMediaRequest;
-use App\Http\Resources\MediaItem\MediaItemResource;
-use App\Repositories\MediaItemRepository;
-use Illuminate\Http\JsonResponse;
-use Illuminate\Http\Request;
-
-class MediaItemController extends Controller
-{
-    public function index(Request $request, GetMediaItemsAction $action): JsonResponse
-    {
-        $result = $action->execute(
-            $request->string('page')->toString(),
-            $request->filled('section') ? $request->string('section')->toString() : null,
-        );
-
-        $resourced = $result->map(
-            fn ($section) => $section->map(fn ($item) => new MediaItemResource($item))
-        );
-
-        return $this->success($resourced, __('messages.fetched'));
-    }
-
-    public function store(UploadMediaRequest $request, UploadMediaAction $action): JsonResponse
-    {
-        $mediaItem = $action->execute(UploadMediaDTO::fromRequest($request));
-
-        return $this->created(new MediaItemResource($mediaItem), __('messages.uploaded'));
-    }
-
-    public function show(int $id, MediaItemRepository $repository): JsonResponse
-    {
-        return $this->success(new MediaItemResource($repository->getById($id)), __('messages.fetched'));
-    }
-
-    public function update(UploadMediaRequest $request, int $id, UploadMediaAction $action): JsonResponse
-    {
-        $mediaItem = $action->execute(UploadMediaDTO::fromRequest($request));
-
-        return $this->success(new MediaItemResource($mediaItem), __('messages.updated'));
-    }
-
-    public function destroy(int $id, DeleteMediaAction $action): JsonResponse
-    {
-        $action->execute($id);
-
-        return $this->deleted(__('messages.deleted'));
-    }
-}
diff --git a/app/Http/Controllers/PageContentController.php b/app/Http/Controllers/PageContentController.php
deleted file mode 100644
index d0fdfc3..0000000
--- a/app/Http/Controllers/PageContentController.php
+++ /dev/null
@@ -1,51 +0,0 @@
-<?php
-
-namespace App\Http\Controllers;
-
-use App\Actions\PageContent\BulkUpdatePageContentAction;
-use App\Actions\PageContent\GetPageContentAction;
-use App\Actions\PageContent\UpdatePageContentAction;
-use App\DTOs\PageContent\BulkUpdatePageContentDTO;
-use App\DTOs\PageContent\UpdatePageContentDTO;
-use App\Http\Requests\PageContent\BulkUpdatePageContentRequest;
-use App\Http\Requests\PageContent\UpdatePageContentRequest;
-use App\Http\Resources\PageContent\PageContentResource;
-use App\Repositories\PageContentRepository;
-use Illuminate\Http\JsonResponse;
-use Illuminate\Http\Request;
-
-class PageContentController extends Controller
-{
-    public function index(Request $request, GetPageContentAction $action): JsonResponse
-    {
-        $result = $action->execute(
-            $request->string('page')->toString(),
-            $request->filled('section') ? $request->string('section')->toString() : null,
-        );
-
-        $resourced = $result->map(
-            fn ($section) => $section->map(fn ($item) => new PageContentResource($item))
-        );
-
-        return $this->success($resourced, __('messages.fetched'));
-    }
-
-    public function show(int $id, PageContentRepository $repository): JsonResponse
-    {
-        return $this->success(new PageContentResource($repository->getById($id)), __('messages.fetched'));
-    }
-
-    public function update(UpdatePageContentRequest $request, int $id, UpdatePageContentAction $action): JsonResponse
-    {
-        $pageContent = $action->execute($id, UpdatePageContentDTO::fromRequest($request));
-
-        return $this->success(new PageContentResource($pageContent), __('messages.updated'));
-    }
-
-    public function bulkUpdate(BulkUpdatePageContentRequest $request, BulkUpdatePageContentAction $action): JsonResponse
-    {
-        $action->execute(BulkUpdatePageContentDTO::fromRequest($request));
-
-        return $this->success(null, __('messages.updated'));
-    }
-}
diff --git a/app/Http/Controllers/SettingController.php b/app/Http/Controllers/SettingController.php
deleted file mode 100644
index 76677c5..0000000
--- a/app/Http/Controllers/SettingController.php
+++ /dev/null
@@ -1,30 +0,0 @@
-<?php
-
-namespace App\Http\Controllers;
-
-use App\Actions\Setting\GetSettingsAction;
-use App\Actions\Setting\UpdateSettingsAction;
-use App\DTOs\Setting\UpdateSettingDTO;
-use App\Http\Requests\Setting\UpdateSettingRequest;
-use App\Http\Resources\Setting\SettingResource;
-use Illuminate\Http\JsonResponse;
-use Illuminate\Http\Request;
-
-class SettingController extends Controller
-{
-    public function index(Request $request, GetSettingsAction $action): JsonResponse
-    {
-        $result = $action->execute(
-            $request->filled('group') ? $request->string('group')->toString() : null,
-        );
-
-        return $this->success($result->map(fn ($setting) => new SettingResource($setting)), __('messages.fetched'));
-    }
-
-    public function bulkUpdate(UpdateSettingRequest $request, UpdateSettingsAction $action): JsonResponse
-    {
-        $action->execute(UpdateSettingDTO::fromRequest($request));
-
-        return $this->success(null, __('messages.updated'));
-    }
-}
diff --git a/app/Http/Controllers/TestimonialController.php b/app/Http/Controllers/TestimonialController.php
deleted file mode 100644
index 9eed09a..0000000
--- a/app/Http/Controllers/TestimonialController.php
+++ /dev/null
@@ -1,43 +0,0 @@
-<?php
-
-namespace App\Http\Controllers;
-
-use App\Actions\Testimonial\CreateTestimonialAction;
-use App\Actions\Testimonial\DeleteTestimonialAction;
-use App\Actions\Testimonial\GetTestimonialsAction;
-use App\Actions\Testimonial\UpdateTestimonialAction;
-use App\DTOs\Testimonial\CreateTestimonialDTO;
-use App\Http\Requests\Testimonial\StoreTestimonialRequest;
-use App\Http\Resources\Testimonial\TestimonialResource;
-use Illuminate\Http\JsonResponse;
-
-class TestimonialController extends Controller
-{
-    public function index(GetTestimonialsAction $action): JsonResponse
-    {
-        $activeOnly = ! auth('sanctum')->check();
-
-        return $this->success(TestimonialResource::collection($action->execute($activeOnly)), __('messages.fetched'));
-    }
-
-    public function store(StoreTestimonialRequest $request, CreateTestimonialAction $action): JsonResponse
-    {
-        $testimonial = $action->execute(CreateTestimonialDTO::fromRequest($request));
-
-        return $this->created(new TestimonialResource($testimonial), __('messages.created'));
-    }
-
-    public function update(StoreTestimonialRequest $request, int $id, UpdateTestimonialAction $action): JsonResponse
-    {
-        $testimonial = $action->execute($id, CreateTestimonialDTO::fromRequest($request));
-
-        return $this->success(new TestimonialResource($testimonial), __('messages.updated'));
-    }
-
-    public function destroy(int $id, DeleteTestimonialAction $action): JsonResponse
-    {
-        $action->execute($id);
-
-        return $this->deleted(__('messages.deleted'));
-    }
-}
diff --git a/app/Http/Controllers/TimelineController.php b/app/Http/Controllers/TimelineController.php
deleted file mode 100644
index 69cede4..0000000
--- a/app/Http/Controllers/TimelineController.php
+++ /dev/null
@@ -1,41 +0,0 @@
-<?php
-
-namespace App\Http\Controllers;
-
-use App\Actions\Timeline\CreateTimelineAction;
-use App\Actions\Timeline\DeleteTimelineAction;
-use App\Actions\Timeline\GetTimelineAction;
-use App\Actions\Timeline\UpdateTimelineAction;
-use App\DTOs\Timeline\CreateTimelineDTO;
-use App\Http\Requests\Timeline\StoreTimelineRequest;
-use App\Http\Resources\Timeline\TimelineResource;
-use Illuminate\Http\JsonResponse;
-
-class TimelineController extends Controller
-{
-    public function index(GetTimelineAction $action): JsonResponse
-    {
-        return $this->success(TimelineResource::collection($action->execute()), __('messages.fetched'));
-    }
-
-    public function store(StoreTimelineRequest $request, CreateTimelineAction $action): JsonResponse
-    {
-        $timeline = $action->execute(CreateTimelineDTO::fromRequest($request));
-
-        return $this->created(new TimelineResource($timeline), __('messages.created'));
-    }
-
-    public function update(StoreTimelineRequest $request, int $id, UpdateTimelineAction $action): JsonResponse
-    {
-        $timeline = $action->execute($id, CreateTimelineDTO::fromRequest($request));
-
-        return $this->success(new TimelineResource($timeline), __('messages.updated'));
-    }
-
-    public function destroy(int $id, DeleteTimelineAction $action): JsonResponse
-    {
-        $action->execute($id);
-
-        return $this->deleted(__('messages.deleted'));
-    }
-}
diff --git a/app/Http/Controllers/UserController.php b/app/Http/Controllers/UserController.php
deleted file mode 100644
index bd04933..0000000
--- a/app/Http/Controllers/UserController.php
+++ /dev/null
@@ -1,51 +0,0 @@
-<?php
-
-namespace App\Http\Controllers;
-
-use App\Actions\User\CreateUserAction;
-use App\Actions\User\DeleteUserAction;
-use App\Actions\User\GetUsersAction;
-use App\Actions\User\UpdateUserAction;
-use App\DTOs\User\CreateUserDTO;
-use App\DTOs\User\UpdateUserDTO;
-use App\Http\Requests\User\StoreUserRequest;
-use App\Http\Requests\User\UpdateUserRequest;
-use App\Http\Resources\User\UserResource;
-use App\Repositories\UserRepository;
-use Illuminate\Http\JsonResponse;
-
-class UserController extends Controller
-{
-    public function index(GetUsersAction $action): JsonResponse
-    {
-        $result = $action->execute();
-
-        return $this->paginated(UserResource::collection($result), $result, __('messages.fetched'));
-    }
-
-    public function store(StoreUserRequest $request, CreateUserAction $action): JsonResponse
-    {
-        $user = $action->execute(CreateUserDTO::fromRequest($request));
-
-        return $this->created(new UserResource($user), __('messages.created'));
-    }
-
-    public function show(int $id, UserRepository $repository): JsonResponse
-    {
-        return $this->success(new UserResource($repository->getById($id)), __('messages.fetched'));
-    }
-
-    public function update(UpdateUserRequest $request, int $id, UpdateUserAction $action): JsonResponse
-    {
-        $user = $action->execute($id, UpdateUserDTO::fromRequest($request));
-
-        return $this->success(new UserResource($user), __('messages.updated'));
-    }
-
-    public function destroy(int $id, DeleteUserAction $action): JsonResponse
-    {
-        $action->execute($id);
-
-        return $this->deleted(__('messages.deleted'));
-    }
-}
diff --git a/routes/admin.php b/routes/admin.php
index 2ca5c4b..728f0e8 100644
--- a/routes/admin.php
+++ b/routes/admin.php
@@ -1,25 +1,38 @@
 <?php
 
-use App\Http\Controllers\Admin;
+use App\Http\Controllers\Admin\Auth\AuthController;
+use App\Http\Controllers\Admin\BranchController;
+use App\Http\Controllers\Admin\Catering\CateringPackageController;
+use App\Http\Controllers\Admin\Catering\QuoteRequestController;
+use App\Http\Controllers\Admin\Catering\SampleMenuController;
+use App\Http\Controllers\Admin\CategoryController;
+use App\Http\Controllers\Admin\DeliveryAppController;
+use App\Http\Controllers\Admin\DishController;
+use App\Http\Controllers\Admin\MediaItemController;
+use App\Http\Controllers\Admin\PageContentController;
+use App\Http\Controllers\Admin\SettingController;
+use App\Http\Controllers\Admin\TestimonialController;
+use App\Http\Controllers\Admin\TimelineController;
+use App\Http\Controllers\Admin\UserController;
 use Illuminate\Support\Facades\Route;
 
 Route::prefix('v1/admin')->middleware(['set.locale'])->group(function () {
 
     // ── Auth ──────────────────────────────────────────────
-    Route::prefix('auth')->controller(Admin\Auth\AuthController::class)->group(function () {
+    Route::prefix('auth')->controller(AuthController::class)->group(function () {
         Route::post('/login', 'login');
     });
 
     Route::middleware('auth:sanctum')->group(function () {
 
         // ── Auth (protected) ──────────────────────────────
-        Route::prefix('auth')->controller(Admin\Auth\AuthController::class)->group(function () {
+        Route::prefix('auth')->controller(AuthController::class)->group(function () {
             Route::post('/logout', 'logout');
             Route::get('/me',      'me');
         });
 
         // ── Page Contents ─────────────────────────────────
-        Route::prefix('page-contents')->controller(Admin\PageContentController::class)->group(function () {
+        Route::prefix('page-contents')->controller(PageContentController::class)->group(function () {
             Route::get('/',       'index');
             Route::get('/{id}',   'show');
             Route::put('/{id}',   'update');
@@ -27,7 +40,7 @@
         });
 
         // ── Media Items ───────────────────────────────────
-        Route::prefix('media-items')->controller(Admin\MediaItemController::class)->group(function () {
+        Route::prefix('media-items')->controller(MediaItemController::class)->group(function () {
             Route::get('/',        'index');
             Route::get('/{id}',    'show');
             Route::post('/',       'store');
@@ -36,13 +49,13 @@
         });
 
         // ── Settings ──────────────────────────────────────
-        Route::prefix('settings')->controller(Admin\SettingController::class)->group(function () {
+        Route::prefix('settings')->controller(SettingController::class)->group(function () {
             Route::get('/',  'index');
             Route::put('/',  'bulkUpdate');
         });
 
         // ── Dishes ────────────────────────────────────────
-        Route::prefix('dishes')->controller(Admin\DishController::class)->group(function () {
+        Route::prefix('dishes')->controller(DishController::class)->group(function () {
             Route::get('/',        'index');
             Route::post('/',       'store');
             Route::get('/{id}',    'show');
@@ -51,7 +64,7 @@
         });
 
         // ── Categories ────────────────────────────────────
-        Route::prefix('categories')->controller(Admin\CategoryController::class)->group(function () {
+        Route::prefix('categories')->controller(CategoryController::class)->group(function () {
             Route::get('/',        'index');
             Route::post('/',       'store');
             Route::put('/{id}',    'update');
@@ -59,7 +72,7 @@
         });
 
         // ── Branches ──────────────────────────────────────
-        Route::prefix('branches')->controller(Admin\BranchController::class)->group(function () {
+        Route::prefix('branches')->controller(BranchController::class)->group(function () {
             Route::get('/',        'index');
             Route::post('/',       'store');
             Route::get('/{id}',    'show');
@@ -69,7 +82,7 @@
 
         // ── Catering Packages ─────────────────────────────
         Route::prefix('catering/packages')
-            ->controller(Admin\Catering\CateringPackageController::class)
+            ->controller(CateringPackageController::class)
             ->group(function () {
                 Route::get('/',        'index');
                 Route::post('/',       'store');
@@ -79,7 +92,7 @@
 
         // ── Sample Menus ──────────────────────────────────
         Route::prefix('catering/sample-menus')
-            ->controller(Admin\Catering\SampleMenuController::class)
+            ->controller(SampleMenuController::class)
             ->group(function () {
                 Route::get('/',        'index');
                 Route::post('/',       'store');
@@ -89,7 +102,7 @@
 
         // ── Quote Requests ────────────────────────────────
         Route::prefix('catering/quote-requests')
-            ->controller(Admin\Catering\QuoteRequestController::class)
+            ->controller(QuoteRequestController::class)
             ->group(function () {
                 Route::get('/',              'index');
                 Route::get('/{id}',          'show');
@@ -97,7 +110,7 @@
             });
 
         // ── Testimonials ──────────────────────────────────
-        Route::prefix('testimonials')->controller(Admin\TestimonialController::class)->group(function () {
+        Route::prefix('testimonials')->controller(TestimonialController::class)->group(function () {
             Route::get('/',        'index');
             Route::post('/',       'store');
             Route::put('/{id}',    'update');
@@ -105,7 +118,7 @@
         });
 
         // ── Timeline ──────────────────────────────────────
-        Route::prefix('timeline')->controller(Admin\TimelineController::class)->group(function () {
+        Route::prefix('timeline')->controller(TimelineController::class)->group(function () {
             Route::get('/',        'index');
             Route::post('/',       'store');
             Route::put('/{id}',    'update');
@@ -113,7 +126,7 @@
         });
 
         // ── Delivery Apps ─────────────────────────────────
-        Route::prefix('delivery-apps')->controller(Admin\DeliveryAppController::class)->group(function () {
+        Route::prefix('delivery-apps')->controller(DeliveryAppController::class)->group(function () {
             Route::get('/',        'index');
             Route::post('/',       'store');
             Route::put('/{id}',    'update');
@@ -121,7 +134,7 @@
         });
 
         // ── Users ─────────────────────────────────────────
-        Route::prefix('users')->controller(Admin\UserController::class)->group(function () {
+        Route::prefix('users')->controller(UserController::class)->group(function () {
             Route::get('/',        'index');
             Route::post('/',       'store');
             Route::get('/{id}',    'show');
===== END change.diff =====

===== BEGIN context-bundle.md =====
# Context bundle

bundle_version 2 · budget 8000 / used 7970 tokens

## fetched · named_reference

**Subject:** App\Actions\Auth\LogoutAction
**Reason:** the region depends on App\Actions\Auth\LogoutAction, whose contract is defined in another file
**Source:** `app/Actions/Auth/LogoutAction.php` :: `execute` (lines 9-12)
**Tokens:** 26

```php
    public function execute(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
```

## fetched · named_reference

**Subject:** App\Actions\Branch\CreateBranchAction
**Reason:** the region depends on App\Actions\Branch\CreateBranchAction, whose contract is defined in another file
**Source:** `app/Actions/Branch/CreateBranchAction.php` :: `__construct` (lines 13-16)
**Tokens:** 38

```php
    public function __construct(
        private readonly BranchRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Branch\DeleteBranchAction
**Reason:** the region depends on App\Actions\Branch\DeleteBranchAction, whose contract is defined in another file
**Source:** `app/Actions/Branch/DeleteBranchAction.php` :: `__construct` (lines 11-14)
**Tokens:** 38

```php
    public function __construct(
        private readonly BranchRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Branch\DeleteBranchAction
**Reason:** the region depends on App\Actions\Branch\DeleteBranchAction, whose contract is defined in another file
**Source:** `app/Actions/Branch/DeleteBranchAction.php` :: `execute` (lines 16-27)
**Tokens:** 75

```php
    public function execute(int $id): void
    {
        $branch = $this->repository->getById($id);

        if ($branch->image_path) {
            $this->imageService->delete($branch->image_path);
        }

        $this->repository->delete($id);

        Cache::tags(['branches'])->flush();
    }
```

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

**Subject:** App\Actions\Branch\UpdateBranchAction
**Reason:** the region depends on App\Actions\Branch\UpdateBranchAction, whose contract is defined in another file
**Source:** `app/Actions/Branch/UpdateBranchAction.php` :: `__construct` (lines 14-17)
**Tokens:** 38

```php
    public function __construct(
        private readonly BranchRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Catering\CreatePackageAction
**Reason:** the region depends on App\Actions\Catering\CreatePackageAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/CreatePackageAction.php` :: `__construct` (lines 13-16)
**Tokens:** 40

```php
    public function __construct(
        private readonly CateringPackageRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Catering\CreateSampleMenuAction
**Reason:** the region depends on App\Actions\Catering\CreateSampleMenuAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/CreateSampleMenuAction.php` :: `__construct` (lines 13-16)
**Tokens:** 39

```php
    public function __construct(
        private readonly SampleMenuRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Catering\DeletePackageAction
**Reason:** the region depends on App\Actions\Catering\DeletePackageAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/DeletePackageAction.php` :: `__construct` (lines 11-14)
**Tokens:** 40

```php
    public function __construct(
        private readonly CateringPackageRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Catering\DeletePackageAction
**Reason:** the region depends on App\Actions\Catering\DeletePackageAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/DeletePackageAction.php` :: `execute` (lines 16-27)
**Tokens:** 78

```php
    public function execute(int $id): void
    {
        $package = $this->repository->getById($id);

        if ($package->image_path) {
            $this->imageService->delete($package->image_path);
        }

        $this->repository->delete($id);

        Cache::tags(['catering_packages'])->flush();
    }
```

## fetched · named_reference

**Subject:** App\Actions\Catering\DeleteSampleMenuAction
**Reason:** the region depends on App\Actions\Catering\DeleteSampleMenuAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/DeleteSampleMenuAction.php` :: `__construct` (lines 11-14)
**Tokens:** 39

```php
    public function __construct(
        private readonly SampleMenuRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Catering\DeleteSampleMenuAction
**Reason:** the region depends on App\Actions\Catering\DeleteSampleMenuAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/DeleteSampleMenuAction.php` :: `execute` (lines 16-27)
**Tokens:** 75

```php
    public function execute(int $id): void
    {
        $menu = $this->repository->getById($id);

        if ($menu->image_path) {
            $this->imageService->delete($menu->image_path);
        }

        $this->repository->delete($id);

        Cache::tags(['sample_menus'])->flush();
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

**Subject:** App\Actions\Catering\UpdatePackageAction
**Reason:** the region depends on App\Actions\Catering\UpdatePackageAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/UpdatePackageAction.php` :: `__construct` (lines 14-17)
**Tokens:** 40

```php
    public function __construct(
        private readonly CateringPackageRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Catering\UpdateQuoteRequestStatusAction
**Reason:** the region depends on App\Actions\Catering\UpdateQuoteRequestStatusAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/UpdateQuoteRequestStatusAction.php` :: `__construct` (lines 11-13)
**Tokens:** 26

```php
    public function __construct(
        private readonly QuoteRequestRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Catering\UpdateQuoteRequestStatusAction
**Reason:** the region depends on App\Actions\Catering\UpdateQuoteRequestStatusAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/UpdateQuoteRequestStatusAction.php` :: `execute` (lines 15-18)
**Tokens:** 43

```php
    public function execute(int $id, UpdateQuoteRequestStatusDTO $dto): QuoteRequest
    {
        return $this->repository->updateStatus($id, $dto->status->value);
    }
```

## fetched · named_reference

**Subject:** App\Actions\Catering\UpdateSampleMenuAction
**Reason:** the region depends on App\Actions\Catering\UpdateSampleMenuAction, whose contract is defined in another file
**Source:** `app/Actions/Catering/UpdateSampleMenuAction.php` :: `__construct` (lines 13-16)
**Tokens:** 39

```php
    public function __construct(
        private readonly SampleMenuRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\DeliveryApp\CreateDeliveryAppAction
**Reason:** the region depends on App\Actions\DeliveryApp\CreateDeliveryAppAction, whose contract is defined in another file
**Source:** `app/Actions/DeliveryApp/CreateDeliveryAppAction.php` :: `__construct` (lines 13-16)
**Tokens:** 39

```php
    public function __construct(
        private readonly DeliveryAppRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\DeliveryApp\DeleteDeliveryAppAction
**Reason:** the region depends on App\Actions\DeliveryApp\DeleteDeliveryAppAction, whose contract is defined in another file
**Source:** `app/Actions/DeliveryApp/DeleteDeliveryAppAction.php` :: `__construct` (lines 11-14)
**Tokens:** 39

```php
    public function __construct(
        private readonly DeliveryAppRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\DeliveryApp\DeleteDeliveryAppAction
**Reason:** the region depends on App\Actions\DeliveryApp\DeleteDeliveryAppAction, whose contract is defined in another file
**Source:** `app/Actions/DeliveryApp/DeleteDeliveryAppAction.php` :: `execute` (lines 16-27)
**Tokens:** 80

```php
    public function execute(int $id): void
    {
        $deliveryApp = $this->repository->getById($id);

        if ($deliveryApp->logo_path) {
            $this->imageService->delete($deliveryApp->logo_path);
        }

        $this->repository->delete($id);

        Cache::tags(['delivery_apps'])->flush();
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

**Subject:** App\Actions\DeliveryApp\UpdateDeliveryAppAction
**Reason:** the region depends on App\Actions\DeliveryApp\UpdateDeliveryAppAction, whose contract is defined in another file
**Source:** `app/Actions/DeliveryApp/UpdateDeliveryAppAction.php` :: `__construct` (lines 13-16)
**Tokens:** 39

```php
    public function __construct(
        private readonly DeliveryAppRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Dish\CreateDishAction
**Reason:** the region depends on App\Actions\Dish\CreateDishAction, whose contract is defined in another file
**Source:** `app/Actions/Dish/CreateDishAction.php` :: `__construct` (lines 13-16)
**Tokens:** 37

```php
    public function __construct(
        private readonly DishRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Dish\DeleteDishAction
**Reason:** the region depends on App\Actions\Dish\DeleteDishAction, whose contract is defined in another file
**Source:** `app/Actions/Dish/DeleteDishAction.php` :: `__construct` (lines 11-14)
**Tokens:** 37

```php
    public function __construct(
        private readonly DishRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Dish\DeleteDishAction
**Reason:** the region depends on App\Actions\Dish\DeleteDishAction, whose contract is defined in another file
**Source:** `app/Actions/Dish/DeleteDishAction.php` :: `execute` (lines 16-27)
**Tokens:** 73

```php
    public function execute(int $id): void
    {
        $dish = $this->repository->getById($id);

        if ($dish->image_path) {
            $this->imageService->delete($dish->image_path);
        }

        $this->repository->delete($id);

        Cache::tags(['dishes'])->flush();
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

**Subject:** App\Actions\Dish\UpdateDishAction
**Reason:** the region depends on App\Actions\Dish\UpdateDishAction, whose contract is defined in another file
**Source:** `app/Actions/Dish/UpdateDishAction.php` :: `__construct` (lines 14-17)
**Tokens:** 37

```php
    public function __construct(
        private readonly DishRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\MediaItem\DeleteMediaAction
**Reason:** the region depends on App\Actions\MediaItem\DeleteMediaAction, whose contract is defined in another file
**Source:** `app/Actions/MediaItem/DeleteMediaAction.php` :: `__construct` (lines 11-14)
**Tokens:** 38

```php
    public function __construct(
        private readonly MediaItemRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\MediaItem\DeleteMediaAction
**Reason:** the region depends on App\Actions\MediaItem\DeleteMediaAction, whose contract is defined in another file
**Source:** `app/Actions/MediaItem/DeleteMediaAction.php` :: `execute` (lines 16-27)
**Tokens:** 75

```php
    public function execute(int $id): void
    {
        $mediaItem = $this->repository->getById($id);

        if ($mediaItem->path) {
            $this->imageService->delete($mediaItem->path);
        }

        $this->repository->delete($id);

        Cache::tags(['media_items'])->flush();
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

**Subject:** App\Actions\MediaItem\UploadMediaAction
**Reason:** the region depends on App\Actions\MediaItem\UploadMediaAction, whose contract is defined in another file
**Source:** `app/Actions/MediaItem/UploadMediaAction.php` :: `__construct` (lines 13-16)
**Tokens:** 38

```php
    public function __construct(
        private readonly MediaItemRepository $repository,
        private readonly ImageService $imageService,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\PageContent\BulkUpdatePageContentAction
**Reason:** the region depends on App\Actions\PageContent\BulkUpdatePageContentAction, whose contract is defined in another file
**Source:** `app/Actions/PageContent/BulkUpdatePageContentAction.php` :: `__construct` (lines 11-13)
**Tokens:** 26

```php
    public function __construct(
        private readonly PageContentRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\PageContent\BulkUpdatePageContentAction
**Reason:** the region depends on App\Actions\PageContent\BulkUpdatePageContentAction, whose contract is defined in another file
**Source:** `app/Actions/PageContent/BulkUpdatePageContentAction.php` :: `execute` (lines 15-20)
**Tokens:** 45

```php
    public function execute(BulkUpdatePageContentDTO $dto): void
    {
        $this->repository->bulkUpdate($dto->items);

        Cache::tags(['page_contents'])->flush();
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

**Subject:** App\Actions\PageContent\UpdatePageContentAction
**Reason:** the region depends on App\Actions\PageContent\UpdatePageContentAction, whose contract is defined in another file
**Source:** `app/Actions/PageContent/UpdatePageContentAction.php` :: `__construct` (lines 12-14)
**Tokens:** 26

```php
    public function __construct(
        private readonly PageContentRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\PageContent\UpdatePageContentAction
**Reason:** the region depends on App\Actions\PageContent\UpdatePageContentAction, whose contract is defined in another file
**Source:** `app/Actions/PageContent/UpdatePageContentAction.php` :: `execute` (lines 16-26)
**Tokens:** 80

```php
    public function execute(int $id, UpdatePageContentDTO $dto): PageContent
    {
        $pageContent = $this->repository->update($id, [
            'value_ar' => $dto->value_ar,
            'value_en' => $dto->value_en,
        ]);

        Cache::tags(['page_contents'])->flush();

        return $pageContent;
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

**Subject:** App\Actions\Setting\UpdateSettingsAction
**Reason:** the region depends on App\Actions\Setting\UpdateSettingsAction, whose contract is defined in another file
**Source:** `app/Actions/Setting/UpdateSettingsAction.php` :: `__construct` (lines 11-13)
**Tokens:** 25

```php
    public function __construct(
        private readonly SettingRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Setting\UpdateSettingsAction
**Reason:** the region depends on App\Actions\Setting\UpdateSettingsAction, whose contract is defined in another file
**Source:** `app/Actions/Setting/UpdateSettingsAction.php` :: `execute` (lines 15-20)
**Tokens:** 42

```php
    public function execute(UpdateSettingDTO $dto): void
    {
        $this->repository->bulkUpdate($dto->settings);

        Cache::tags(['settings'])->flush();
    }
```

## fetched · named_reference

**Subject:** App\Actions\Testimonial\CreateTestimonialAction
**Reason:** the region depends on App\Actions\Testimonial\CreateTestimonialAction, whose contract is defined in another file
**Source:** `app/Actions/Testimonial/CreateTestimonialAction.php` :: `__construct` (lines 12-14)
**Tokens:** 26

```php
    public function __construct(
        private readonly TestimonialRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Testimonial\DeleteTestimonialAction
**Reason:** the region depends on App\Actions\Testimonial\DeleteTestimonialAction, whose contract is defined in another file
**Source:** `app/Actions/Testimonial/DeleteTestimonialAction.php` :: `__construct` (lines 10-12)
**Tokens:** 26

```php
    public function __construct(
        private readonly TestimonialRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Testimonial\DeleteTestimonialAction
**Reason:** the region depends on App\Actions\Testimonial\DeleteTestimonialAction, whose contract is defined in another file
**Source:** `app/Actions/Testimonial/DeleteTestimonialAction.php` :: `execute` (lines 14-19)
**Tokens:** 36

```php
    public function execute(int $id): void
    {
        $this->repository->delete($id);

        Cache::tags(['testimonials'])->flush();
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

**Subject:** App\Actions\Testimonial\UpdateTestimonialAction
**Reason:** the region depends on App\Actions\Testimonial\UpdateTestimonialAction, whose contract is defined in another file
**Source:** `app/Actions/Testimonial/UpdateTestimonialAction.php` :: `__construct` (lines 12-14)
**Tokens:** 26

```php
    public function __construct(
        private readonly TestimonialRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Timeline\CreateTimelineAction
**Reason:** the region depends on App\Actions\Timeline\CreateTimelineAction, whose contract is defined in another file
**Source:** `app/Actions/Timeline/CreateTimelineAction.php` :: `__construct` (lines 12-14)
**Tokens:** 25

```php
    public function __construct(
        private readonly TimelineRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Timeline\DeleteTimelineAction
**Reason:** the region depends on App\Actions\Timeline\DeleteTimelineAction, whose contract is defined in another file
**Source:** `app/Actions/Timeline/DeleteTimelineAction.php` :: `__construct` (lines 10-12)
**Tokens:** 25

```php
    public function __construct(
        private readonly TimelineRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\Timeline\DeleteTimelineAction
**Reason:** the region depends on App\Actions\Timeline\DeleteTimelineAction, whose contract is defined in another file
**Source:** `app/Actions/Timeline/DeleteTimelineAction.php` :: `execute` (lines 14-19)
**Tokens:** 35

```php
    public function execute(int $id): void
    {
        $this->repository->delete($id);

        Cache::tags(['timeline'])->flush();
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

**Subject:** App\Actions\Timeline\UpdateTimelineAction
**Reason:** the region depends on App\Actions\Timeline\UpdateTimelineAction, whose contract is defined in another file
**Source:** `app/Actions/Timeline/UpdateTimelineAction.php` :: `__construct` (lines 12-14)
**Tokens:** 25

```php
    public function __construct(
        private readonly TimelineRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\User\CreateUserAction
**Reason:** the region depends on App\Actions\User\CreateUserAction, whose contract is defined in another file
**Source:** `app/Actions/User/CreateUserAction.php` :: `__construct` (lines 12-14)
**Tokens:** 24

```php
    public function __construct(
        private readonly UserRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\User\CreateUserAction
**Reason:** the region depends on App\Actions\User\CreateUserAction, whose contract is defined in another file
**Source:** `app/Actions/User/CreateUserAction.php` :: `execute` (lines 16-24)
**Tokens:** 73

```php
    public function execute(CreateUserDTO $dto): User
    {
        return $this->repository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
            'role' => $dto->role,
        ], $dto->role);
    }
```

## fetched · named_reference

**Subject:** App\Actions\User\DeleteUserAction
**Reason:** the region depends on App\Actions\User\DeleteUserAction, whose contract is defined in another file
**Source:** `app/Actions/User/DeleteUserAction.php` :: `__construct` (lines 9-11)
**Tokens:** 24

```php
    public function __construct(
        private readonly UserRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\User\DeleteUserAction
**Reason:** the region depends on App\Actions\User\DeleteUserAction, whose contract is defined in another file
**Source:** `app/Actions/User/DeleteUserAction.php` :: `execute` (lines 13-16)
**Tokens:** 24

```php
    public function execute(int $id): void
    {
        $this->repository->delete($id);
    }
```

## fetched · named_reference

**Subject:** App\Actions\User\GetUsersAction
**Reason:** the region depends on App\Actions\User\GetUsersAction, whose contract is defined in another file
**Source:** `app/Actions/User/GetUsersAction.php` :: `__construct` (lines 10-12)
**Tokens:** 24

```php
    public function __construct(
        private readonly UserRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\Actions\User\GetUsersAction
**Reason:** the region depends on App\Actions\User\GetUsersAction, whose contract is defined in another file
**Source:** `app/Actions/User/GetUsersAction.php` :: `execute` (lines 14-17)
**Tokens:** 27

```php
    public function execute(): LengthAwarePaginator
    {
        return $this->repository->getAll();
    }
```

## fetched · named_reference

**Subject:** App\Actions\User\UpdateUserAction
**Reason:** the region depends on App\Actions\User\UpdateUserAction, whose contract is defined in another file
**Source:** `app/Actions/User/UpdateUserAction.php` :: `__construct` (lines 13-15)
**Tokens:** 24

```php
    public function __construct(
        private readonly UserRepository $repository,
    ) {}
```

## fetched · named_reference

**Subject:** App\DTOs\Auth\LoginDTO::fromRequest
**Reason:** the region depends on App\DTOs\Auth\LoginDTO::fromRequest, whose contract is defined in another file
**Source:** `app/DTOs/Auth/LoginDTO.php` :: `fromRequest` (lines 14-20)
**Tokens:** 58

```php
    public static function fromRequest(Request $request): self
    {
        return new self(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
        );
    }
```

## fetched · named_reference

**Subject:** App\DTOs\Catering\UpdateQuoteRequestStatusDTO::fromRequest
**Reason:** the region depends on App\DTOs\Catering\UpdateQuoteRequestStatusDTO::fromRequest, whose contract is defined in another file
**Source:** `app/DTOs/Catering/UpdateQuoteRequestStatusDTO.php` :: `fromRequest` (lines 14-19)
**Tokens:** 50

```php
    public static function fromRequest(Request $request): self
    {
        return new self(
            status: QuoteRequestStatusEnum::from($request->string('status')->toString()),
        );
    }
```

## fetched · named_reference

**Subject:** App\DTOs\PageContent\BulkUpdatePageContentDTO::fromRequest
**Reason:** the region depends on App\DTOs\PageContent\BulkUpdatePageContentDTO::fromRequest, whose contract is defined in another file
**Source:** `app/DTOs/PageContent/BulkUpdatePageContentDTO.php` :: `fromRequest` (lines 13-18)
**Tokens:** 40

```php
    public static function fromRequest(Request $request): self
    {
        return new self(
            items: $request->input('items', []),
        );
    }
```

## fetched · named_reference

**Subject:** App\DTOs\PageContent\UpdatePageContentDTO::fromRequest
**Reason:** the region depends on App\DTOs\PageContent\UpdatePageContentDTO::fromRequest, whose contract is defined in another file
**Source:** `app/DTOs/PageContent/UpdatePageContentDTO.php` :: `fromRequest` (lines 14-20)
**Tokens:** 60

```php
    public static function fromRequest(Request $request): self
    {
        return new self(
            value_ar: $request->string('value_ar')->toString(),
            value_en: $request->string('value_en')->toString(),
        );
    }
```

## fetched · named_reference

**Subject:** App\DTOs\Setting\UpdateSettingDTO::fromRequest
**Reason:** the region depends on App\DTOs\Setting\UpdateSettingDTO::fromRequest, whose contract is defined in another file
**Source:** `app/DTOs/Setting/UpdateSettingDTO.php` :: `fromRequest` (lines 13-18)
**Tokens:** 38

```php
    public static function fromRequest(Request $request): self
    {
        return new self(
            settings: $request->all(),
        );
    }
```

## fetched · named_reference

**Subject:** App\DTOs\User\CreateUserDTO::fromRequest
**Reason:** the region depends on App\DTOs\User\CreateUserDTO::fromRequest, whose contract is defined in another file
**Source:** `app/DTOs/User/CreateUserDTO.php` :: `fromRequest` (lines 16-24)
**Tokens:** 86

```php
    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            role: $request->string('role')->toString(),
        );
    }
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\Auth\AuthController::success
**Reason:** the region calls App\Http\Controllers\Admin\Auth\AuthController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/Auth/AuthController.php` :: `App\Http\Controllers\Admin\Auth\AuthController::success` (lines 2-35)
**Tokens:** 61

```text
ASSUMPTION: success() is not declared in AuthController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\BranchController::created
**Reason:** the region calls App\Http\Controllers\Admin\BranchController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/BranchController.php` :: `App\Http\Controllers\Admin\BranchController::created` (lines 2-50)
**Tokens:** 62

```text
ASSUMPTION: created() is not declared in BranchController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\BranchController::deleted
**Reason:** the region calls App\Http\Controllers\Admin\BranchController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/BranchController.php` :: `App\Http\Controllers\Admin\BranchController::deleted` (lines 2-50)
**Tokens:** 62

```text
ASSUMPTION: deleted() is not declared in BranchController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\BranchController::success
**Reason:** the region calls App\Http\Controllers\Admin\BranchController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/BranchController.php` :: `App\Http\Controllers\Admin\BranchController::success` (lines 2-50)
**Tokens:** 62

```text
ASSUMPTION: success() is not declared in BranchController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Branch\BranchResource::collection
**Reason:** the region depends on App\Http\Resources\Branch\BranchResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/BranchController.php` :: `App\Http\Resources\Branch\BranchResource::collection` (lines 2-50)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\CategoryController::created
**Reason:** the region calls App\Http\Controllers\Admin\CategoryController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/CategoryController.php` :: `App\Http\Controllers\Admin\CategoryController::created` (lines 2-42)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in CategoryController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\CategoryController::deleted
**Reason:** the region calls App\Http\Controllers\Admin\CategoryController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/CategoryController.php` :: `App\Http\Controllers\Admin\CategoryController::deleted` (lines 2-42)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in CategoryController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\CategoryController::success
**Reason:** the region calls App\Http\Controllers\Admin\CategoryController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/CategoryController.php` :: `App\Http\Controllers\Admin\CategoryController::success` (lines 2-42)
**Tokens:** 62

```text
ASSUMPTION: success() is not declared in CategoryController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Category\CategoryResource::collection
**Reason:** the region depends on App\Http\Resources\Category\CategoryResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/CategoryController.php` :: `App\Http\Resources\Category\CategoryResource::collection` (lines 2-42)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\Catering\CateringPackageController::created
**Reason:** the region calls App\Http\Controllers\Admin\Catering\CateringPackageController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/Catering/CateringPackageController.php` :: `App\Http\Controllers\Admin\Catering\CateringPackageController::created` (lines 2-44)
**Tokens:** 64

```text
ASSUMPTION: created() is not declared in CateringPackageController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\Catering\CateringPackageController::deleted
**Reason:** the region calls App\Http\Controllers\Admin\Catering\CateringPackageController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/Catering/CateringPackageController.php` :: `App\Http\Controllers\Admin\Catering\CateringPackageController::deleted` (lines 2-44)
**Tokens:** 64

```text
ASSUMPTION: deleted() is not declared in CateringPackageController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\Catering\CateringPackageController::success
**Reason:** the region calls App\Http\Controllers\Admin\Catering\CateringPackageController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/Catering/CateringPackageController.php` :: `App\Http\Controllers\Admin\Catering\CateringPackageController::success` (lines 2-44)
**Tokens:** 64

```text
ASSUMPTION: success() is not declared in CateringPackageController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Catering\PackageResource::collection
**Reason:** the region depends on App\Http\Resources\Catering\PackageResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/Catering/CateringPackageController.php` :: `App\Http\Resources\Catering\PackageResource::collection` (lines 2-44)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\Catering\QuoteRequestController::paginated
**Reason:** the region calls App\Http\Controllers\Admin\Catering\QuoteRequestController::paginated, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/Catering/QuoteRequestController.php` :: `App\Http\Controllers\Admin\Catering\QuoteRequestController::paginated` (lines 2-40)
**Tokens:** 64

```text
ASSUMPTION: paginated() is not declared in QuoteRequestController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:38, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\Catering\QuoteRequestController::success
**Reason:** the region calls App\Http\Controllers\Admin\Catering\QuoteRequestController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/Catering/QuoteRequestController.php` :: `App\Http\Controllers\Admin\Catering\QuoteRequestController::success` (lines 2-40)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in QuoteRequestController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Catering\QuoteRequestResource::collection
**Reason:** the region depends on App\Http\Resources\Catering\QuoteRequestResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/Catering/QuoteRequestController.php` :: `App\Http\Resources\Catering\QuoteRequestResource::collection` (lines 2-40)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\Catering\SampleMenuController::created
**Reason:** the region calls App\Http\Controllers\Admin\Catering\SampleMenuController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/Catering/SampleMenuController.php` :: `App\Http\Controllers\Admin\Catering\SampleMenuController::created` (lines 2-42)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in SampleMenuController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\Catering\SampleMenuController::deleted
**Reason:** the region calls App\Http\Controllers\Admin\Catering\SampleMenuController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/Catering/SampleMenuController.php` :: `App\Http\Controllers\Admin\Catering\SampleMenuController::deleted` (lines 2-42)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in SampleMenuController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\Catering\SampleMenuController::success
**Reason:** the region calls App\Http\Controllers\Admin\Catering\SampleMenuController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/Catering/SampleMenuController.php` :: `App\Http\Controllers\Admin\Catering\SampleMenuController::success` (lines 2-42)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in SampleMenuController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Catering\SampleMenuResource::collection
**Reason:** the region depends on App\Http\Resources\Catering\SampleMenuResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/Catering/SampleMenuController.php` :: `App\Http\Resources\Catering\SampleMenuResource::collection` (lines 2-42)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\DeliveryAppController::created
**Reason:** the region calls App\Http\Controllers\Admin\DeliveryAppController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DeliveryAppController.php` :: `App\Http\Controllers\Admin\DeliveryAppController::created` (lines 2-42)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in DeliveryAppController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\DeliveryAppController::deleted
**Reason:** the region calls App\Http\Controllers\Admin\DeliveryAppController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DeliveryAppController.php` :: `App\Http\Controllers\Admin\DeliveryAppController::deleted` (lines 2-42)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in DeliveryAppController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\DeliveryAppController::success
**Reason:** the region calls App\Http\Controllers\Admin\DeliveryAppController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DeliveryAppController.php` :: `App\Http\Controllers\Admin\DeliveryAppController::success` (lines 2-42)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in DeliveryAppController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\DeliveryApp\DeliveryAppResource::collection
**Reason:** the region depends on App\Http\Resources\DeliveryApp\DeliveryAppResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DeliveryAppController.php` :: `App\Http\Resources\DeliveryApp\DeliveryAppResource::collection` (lines 2-42)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\DishController::created
**Reason:** the region calls App\Http\Controllers\Admin\DishController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DishController.php` :: `App\Http\Controllers\Admin\DishController::created` (lines 2-60)
**Tokens:** 62

```text
ASSUMPTION: created() is not declared in DishController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\DishController::deleted
**Reason:** the region calls App\Http\Controllers\Admin\DishController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DishController.php` :: `App\Http\Controllers\Admin\DishController::deleted` (lines 2-60)
**Tokens:** 62

```text
ASSUMPTION: deleted() is not declared in DishController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\DishController::paginated
**Reason:** the region calls App\Http\Controllers\Admin\DishController::paginated, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DishController.php` :: `App\Http\Controllers\Admin\DishController::paginated` (lines 2-60)
**Tokens:** 62

```text
ASSUMPTION: paginated() is not declared in DishController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:38, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\DishController::success
**Reason:** the region calls App\Http\Controllers\Admin\DishController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DishController.php` :: `App\Http\Controllers\Admin\DishController::success` (lines 2-60)
**Tokens:** 61

```text
ASSUMPTION: success() is not declared in DishController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Dish\DishResource::collection
**Reason:** the region depends on App\Http\Resources\Dish\DishResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/DishController.php` :: `App\Http\Resources\Dish\DishResource::collection` (lines 2-60)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\MediaItemController::created
**Reason:** the region calls App\Http\Controllers\Admin\MediaItemController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/MediaItemController.php` :: `App\Http\Controllers\Admin\MediaItemController::created` (lines 2-57)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in MediaItemController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\MediaItemController::deleted
**Reason:** the region calls App\Http\Controllers\Admin\MediaItemController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/MediaItemController.php` :: `App\Http\Controllers\Admin\MediaItemController::deleted` (lines 2-57)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in MediaItemController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\MediaItemController::success
**Reason:** the region calls App\Http\Controllers\Admin\MediaItemController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/MediaItemController.php` :: `App\Http\Controllers\Admin\MediaItemController::success` (lines 2-57)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in MediaItemController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\PageContentController::success
**Reason:** the region calls App\Http\Controllers\Admin\PageContentController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/PageContentController.php` :: `App\Http\Controllers\Admin\PageContentController::success` (lines 2-52)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in PageContentController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\SettingController::success
**Reason:** the region calls App\Http\Controllers\Admin\SettingController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/SettingController.php` :: `App\Http\Controllers\Admin\SettingController::success` (lines 2-31)
**Tokens:** 62

```text
ASSUMPTION: success() is not declared in SettingController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\TestimonialController::created
**Reason:** the region calls App\Http\Controllers\Admin\TestimonialController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/TestimonialController.php` :: `App\Http\Controllers\Admin\TestimonialController::created` (lines 2-42)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in TestimonialController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\TestimonialController::deleted
**Reason:** the region calls App\Http\Controllers\Admin\TestimonialController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/TestimonialController.php` :: `App\Http\Controllers\Admin\TestimonialController::deleted` (lines 2-42)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in TestimonialController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\TestimonialController::success
**Reason:** the region calls App\Http\Controllers\Admin\TestimonialController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/TestimonialController.php` :: `App\Http\Controllers\Admin\TestimonialController::success` (lines 2-42)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in TestimonialController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Testimonial\TestimonialResource::collection
**Reason:** the region depends on App\Http\Resources\Testimonial\TestimonialResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/TestimonialController.php` :: `App\Http\Resources\Testimonial\TestimonialResource::collection` (lines 2-42)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\TimelineController::created
**Reason:** the region calls App\Http\Controllers\Admin\TimelineController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/TimelineController.php` :: `App\Http\Controllers\Admin\TimelineController::created` (lines 2-42)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in TimelineController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\TimelineController::deleted
**Reason:** the region calls App\Http\Controllers\Admin\TimelineController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/TimelineController.php` :: `App\Http\Controllers\Admin\TimelineController::deleted` (lines 2-42)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in TimelineController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\TimelineController::success
**Reason:** the region calls App\Http\Controllers\Admin\TimelineController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/TimelineController.php` :: `App\Http\Controllers\Admin\TimelineController::success` (lines 2-42)
**Tokens:** 62

```text
ASSUMPTION: success() is not declared in TimelineController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Timeline\TimelineResource::collection
**Reason:** the region depends on App\Http\Resources\Timeline\TimelineResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/TimelineController.php` :: `App\Http\Resources\Timeline\TimelineResource::collection` (lines 2-42)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\UserController::created
**Reason:** the region calls App\Http\Controllers\Admin\UserController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/UserController.php` :: `App\Http\Controllers\Admin\UserController::created` (lines 2-52)
**Tokens:** 62

```text
ASSUMPTION: created() is not declared in UserController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\UserController::deleted
**Reason:** the region calls App\Http\Controllers\Admin\UserController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/UserController.php` :: `App\Http\Controllers\Admin\UserController::deleted` (lines 2-52)
**Tokens:** 62

```text
ASSUMPTION: deleted() is not declared in UserController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\UserController::paginated
**Reason:** the region calls App\Http\Controllers\Admin\UserController::paginated, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/UserController.php` :: `App\Http\Controllers\Admin\UserController::paginated` (lines 2-52)
**Tokens:** 62

```text
ASSUMPTION: paginated() is not declared in UserController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:38, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Admin\UserController::success
**Reason:** the region calls App\Http\Controllers\Admin\UserController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Admin/UserController.php` :: `App\Http\Controllers\Admin\UserController::success` (lines 2-52)
**Tokens:** 61

```text
ASSUMPTION: success() is not declared in UserController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\User\UserResource::collection
**Reason:** the region depends on App\Http\Resources\User\UserResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Admin/UserController.php` :: `App\Http\Resources\User\UserResource::collection` (lines 2-52)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Subject:** App\Http\Requests\Auth\LoginRequest
**Reason:** the region depends on App\Http\Requests\Auth\LoginRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Auth/LoginRequest.php` :: `authorize` (lines 9-12)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Auth\LoginRequest
**Reason:** the region depends on App\Http\Requests\Auth\LoginRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Auth/LoginRequest.php` :: `rules` (lines 14-20)
**Tokens:** 45

```php
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Branch\StoreBranchRequest
**Reason:** the region depends on App\Http\Requests\Branch\StoreBranchRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Branch/StoreBranchRequest.php` :: `authorize` (lines 9-12)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Branch\UpdateBranchRequest
**Reason:** the region depends on App\Http\Requests\Branch\UpdateBranchRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Branch/UpdateBranchRequest.php` :: `authorize` (lines 9-12)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Catering\StorePackageRequest
**Reason:** the region depends on App\Http\Requests\Catering\StorePackageRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Catering/StorePackageRequest.php` :: `authorize` (lines 9-12)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Catering\StoreSampleMenuRequest
**Reason:** the region depends on App\Http\Requests\Catering\StoreSampleMenuRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Catering/StoreSampleMenuRequest.php` :: `authorize` (lines 9-12)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Catering\UpdatePackageRequest
**Reason:** the region depends on App\Http\Requests\Catering\UpdatePackageRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Catering/UpdatePackageRequest.php` :: `authorize` (lines 9-12)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Catering\UpdateQuoteRequestStatusRequest
**Reason:** the region depends on App\Http\Requests\Catering\UpdateQuoteRequestStatusRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Catering/UpdateQuoteRequestStatusRequest.php` :: `authorize` (lines 11-14)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Catering\UpdateQuoteRequestStatusRequest
**Reason:** the region depends on App\Http\Requests\Catering\UpdateQuoteRequestStatusRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Catering/UpdateQuoteRequestStatusRequest.php` :: `rules` (lines 16-21)
**Tokens:** 42

```php
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(QuoteRequestStatusEnum::values())],
        ];
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\DeliveryApp\StoreDeliveryAppRequest
**Reason:** the region depends on App\Http\Requests\DeliveryApp\StoreDeliveryAppRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/DeliveryApp/StoreDeliveryAppRequest.php` :: `authorize` (lines 9-12)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Dish\StoreDishRequest
**Reason:** the region depends on App\Http\Requests\Dish\StoreDishRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Dish/StoreDishRequest.php` :: `authorize` (lines 9-12)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Dish\UpdateDishRequest
**Reason:** the region depends on App\Http\Requests\Dish\UpdateDishRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Dish/UpdateDishRequest.php` :: `authorize` (lines 9-12)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\MediaItem\UploadMediaRequest
**Reason:** the region depends on App\Http\Requests\MediaItem\UploadMediaRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/MediaItem/UploadMediaRequest.php` :: `authorize` (lines 9-12)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\PageContent\BulkUpdatePageContentRequest
**Reason:** the region depends on App\Http\Requests\PageContent\BulkUpdatePageContentRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/PageContent/BulkUpdatePageContentRequest.php` :: `authorize` (lines 9-12)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\PageContent\BulkUpdatePageContentRequest
**Reason:** the region depends on App\Http\Requests\PageContent\BulkUpdatePageContentRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/PageContent/BulkUpdatePageContentRequest.php` :: `rules` (lines 14-22)
**Tokens:** 82

```php
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:page_contents,id'],
            'items.*.value_ar' => ['required', 'string'],
            'items.*.value_en' => ['required', 'string'],
        ];
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\PageContent\UpdatePageContentRequest
**Reason:** the region depends on App\Http\Requests\PageContent\UpdatePageContentRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/PageContent/UpdatePageContentRequest.php` :: `authorize` (lines 9-12)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\PageContent\UpdatePageContentRequest
**Reason:** the region depends on App\Http\Requests\PageContent\UpdatePageContentRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/PageContent/UpdatePageContentRequest.php` :: `rules` (lines 14-20)
**Tokens:** 44

```php
    public function rules(): array
    {
        return [
            'value_ar' => ['required', 'string'],
            'value_en' => ['required', 'string'],
        ];
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Setting\UpdateSettingRequest
**Reason:** the region depends on App\Http\Requests\Setting\UpdateSettingRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Setting/UpdateSettingRequest.php` :: `authorize` (lines 11-14)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Setting\UpdateSettingRequest
**Reason:** the region depends on App\Http\Requests\Setting\UpdateSettingRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Setting/UpdateSettingRequest.php` :: `rules` (lines 16-19)
**Tokens:** 17

```php
    public function rules(): array
    {
        return [];
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Testimonial\StoreTestimonialRequest
**Reason:** the region depends on App\Http\Requests\Testimonial\StoreTestimonialRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Testimonial/StoreTestimonialRequest.php` :: `authorize` (lines 9-12)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Testimonial\StoreTestimonialRequest
**Reason:** the region depends on App\Http\Requests\Testimonial\StoreTestimonialRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Testimonial/StoreTestimonialRequest.php` :: `rules` (lines 14-23)
**Tokens:** 79

```php
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'quote_ar' => ['required', 'string'],
            'quote_en' => ['required', 'string'],
            'is_active' => ['boolean'],
            'order' => ['integer', 'min:0'],
        ];
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\Timeline\StoreTimelineRequest
**Reason:** the region depends on App\Http\Requests\Timeline\StoreTimelineRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Timeline/StoreTimelineRequest.php` :: `authorize` (lines 9-12)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\User\StoreUserRequest
**Reason:** the region depends on App\Http\Requests\User\StoreUserRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/User/StoreUserRequest.php` :: `authorize` (lines 9-12)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\User\StoreUserRequest
**Reason:** the region depends on App\Http\Requests\User\StoreUserRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/User/StoreUserRequest.php` :: `rules` (lines 14-22)
**Tokens:** 88

```php
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'in:super_admin,admin,manager'],
        ];
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\User\UpdateUserRequest
**Reason:** the region depends on App\Http\Requests\User\UpdateUserRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/User/UpdateUserRequest.php` :: `authorize` (lines 10-13)
**Tokens:** 18

```php
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Subject:** App\Http\Requests\User\UpdateUserRequest
**Reason:** the region depends on App\Http\Requests\User\UpdateUserRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/User/UpdateUserRequest.php` :: `rules` (lines 15-23)
**Tokens:** 97

```php
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($this->route('id'))],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['nullable', 'string', 'in:super_admin,admin,manager'],
        ];
    }
```

## fetched · named_reference

**Subject:** App\Http\Resources\Category\CategoryResource
**Reason:** the region depends on App\Http\Resources\Category\CategoryResource, whose contract is defined in another file
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

**Subject:** App\Repositories\CategoryRepository::create
**Reason:** the region depends on App\Repositories\CategoryRepository::create, whose contract is defined in another file
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
**Source:** `app/Repositories/CategoryRepository.php` :: `create` (lines 20-23)
**Tokens:** 26

```php
    public function create(array $data): Category
    {
        return Category::create($data);
    }
```

## fetched · named_reference

**Subject:** App\Repositories\CategoryRepository::delete
**Reason:** the region depends on App\Repositories\CategoryRepository::delete, whose contract is defined in another file
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
**Source:** `app/Repositories/CategoryRepository.php` :: `delete` (lines 33-36)
**Tokens:** 25

```php
    public function delete(int $id): void
    {
        Category::findOrFail($id)->delete();
    }
```

## fetched · named_reference

**Subject:** App\Repositories\CategoryRepository::getAll
**Reason:** the region depends on App\Repositories\CategoryRepository::getAll, whose contract is defined in another file
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

**Subject:** App\Repositories\CategoryRepository::update
**Reason:** the region depends on App\Repositories\CategoryRepository::update, whose contract is defined in another file
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

## fetched · named_reference

**Subject:** App\Repositories\MediaItemRepository
**Reason:** the region depends on App\Repositories\MediaItemRepository, whose contract is defined in another file
**Source:** `app/Repositories/MediaItemRepository.php` :: `create` (lines 31-34)
**Tokens:** 26

```php
    public function create(array $data): MediaItem
    {
        return MediaItem::create($data);
    }
```

## fetched · named_reference

**Subject:** App\Repositories\MediaItemRepository
**Reason:** the region depends on App\Repositories\MediaItemRepository, whose contract is defined in another file
**Source:** `app/Repositories/MediaItemRepository.php` :: `delete` (lines 44-47)
**Tokens:** 25

```php
    public function delete(int $id): void
    {
        MediaItem::findOrFail($id)->delete();
    }
```

## fetched · named_reference

**Subject:** App\Repositories\MediaItemRepository
**Reason:** the region depends on App\Repositories\MediaItemRepository, whose contract is defined in another file
**Source:** `app/Repositories/MediaItemRepository.php` :: `findBySlot` (lines 23-29)
**Tokens:** 61

```php
    public function findBySlot(string $page, string $section, string $key): ?MediaItem
    {
        return MediaItem::where('page', $page)
            ->where('section', $section)
            ->where('key', $key)
            ->first();
    }
```

## fetched · named_reference

**Subject:** App\Repositories\MediaItemRepository
**Reason:** the region depends on App\Repositories\MediaItemRepository, whose contract is defined in another file
**Source:** `app/Repositories/MediaItemRepository.php` :: `getById` (lines 18-21)
**Tokens:** 26

```php
    public function getById(int $id): MediaItem
    {
        return MediaItem::findOrFail($id);
    }
```

## fetched · named_reference

**Subject:** App\Repositories\MediaItemRepository
**Reason:** the region depends on App\Repositories\MediaItemRepository, whose contract is defined in another file
**Source:** `app/Repositories/MediaItemRepository.php` :: `getByPage` (lines 10-16)
**Tokens:** 66

```php
    public function getByPage(string $page, ?string $section = null): Collection
    {
        return MediaItem::forPage($page, $section)
            ->get()
            ->groupBy('section')
            ->map(fn (Collection $items) => $items->keyBy('key'));
    }
```

## fetched · named_reference

**Subject:** App\Repositories\MediaItemRepository
**Reason:** the region depends on App\Repositories\MediaItemRepository, whose contract is defined in another file
**Source:** `app/Repositories/MediaItemRepository.php` :: `update` (lines 36-42)
**Tokens:** 46

```php
    public function update(int $id, array $data): MediaItem
    {
        $mediaItem = MediaItem::findOrFail($id);
        $mediaItem->update($data);

        return $mediaItem;
    }
```

## fetched · named_reference

**Subject:** App\Repositories\PageContentRepository
**Reason:** the region depends on App\Repositories\PageContentRepository, whose contract is defined in another file
**Source:** `app/Repositories/PageContentRepository.php` :: `bulkUpdate` (lines 34-42)
**Tokens:** 77

```php
    public function bulkUpdate(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                PageContent::findOrFail($item['id'])
                    ->update(Arr::only($item, ['value_ar', 'value_en']));
            }
        });
    }
```

## fetched · named_reference

**Subject:** App\Repositories\PageContentRepository
**Reason:** the region depends on App\Repositories\PageContentRepository, whose contract is defined in another file
**Source:** `app/Repositories/PageContentRepository.php` :: `getById` (lines 21-24)
**Tokens:** 27

```php
    public function getById(int $id): PageContent
    {
        return PageContent::findOrFail($id);
    }
```

## fetched · named_reference

**Subject:** App\Repositories\PageContentRepository
**Reason:** the region depends on App\Repositories\PageContentRepository, whose contract is defined in another file
**Source:** `app/Repositories/PageContentRepository.php` :: `getByPage` (lines 12-19)
**Tokens:** 74

```php
    public function getByPage(string $page, ?string $section = null): Collection
    {
        return PageContent::forPage($page, $section)
            ->orderBy('order')
            ->get()
            ->groupBy('section')
            ->map(fn (Collection $items) => $items->keyBy('key'));
    }
```

## fetched · named_reference

**Subject:** App\Repositories\PageContentRepository
**Reason:** the region depends on App\Repositories\PageContentRepository, whose contract is defined in another file
**Source:** `app/Repositories/PageContentRepository.php` :: `update` (lines 26-32)
**Tokens:** 58

```php
    public function update(int $id, array $data): PageContent
    {
        $pageContent = PageContent::findOrFail($id);
        $pageContent->update(Arr::only($data, ['value_ar', 'value_en']));

        return $pageContent;
    }
```

## fetched · named_reference

**Subject:** App\Repositories\QuoteRequestRepository
**Reason:** the region depends on App\Repositories\QuoteRequestRepository, whose contract is defined in another file
**Source:** `app/Repositories/QuoteRequestRepository.php` :: `create` (lines 34-37)
**Tokens:** 28

```php
    public function create(array $data): QuoteRequest
    {
        return QuoteRequest::create($data);
    }
```

## fetched · named_reference

**Subject:** App\Repositories\QuoteRequestRepository
**Reason:** the region depends on App\Repositories\QuoteRequestRepository, whose contract is defined in another file
**Source:** `app/Repositories/QuoteRequestRepository.php` :: `getById` (lines 29-32)
**Tokens:** 27

```php
    public function getById(int $id): QuoteRequest
    {
        return QuoteRequest::findOrFail($id);
    }
```

## fetched · named_reference

**Subject:** App\Repositories\QuoteRequestRepository
**Reason:** the region depends on App\Repositories\QuoteRequestRepository, whose contract is defined in another file
**Source:** `app/Repositories/QuoteRequestRepository.php` :: `updateStatus` (lines 39-45)
**Tokens:** 56

```php
    public function updateStatus(int $id, string $status): QuoteRequest
    {
        $quoteRequest = QuoteRequest::findOrFail($id);
        $quoteRequest->update(['status' => $status]);

        return $quoteRequest;
    }
```

## fetched · named_reference

**Subject:** App\Repositories\UserRepository
**Reason:** the region depends on App\Repositories\UserRepository, whose contract is defined in another file
**Source:** `app/Repositories/UserRepository.php` :: `create` (lines 21-29)
**Tokens:** 64

```php
    public function create(array $data, string $role): User
    {
        return DB::transaction(function () use ($data, $role) {
            $user = User::create($data);
            $user->assignRole($role);

            return $user;
        });
    }
```

## fetched · named_reference

**Subject:** App\Repositories\UserRepository
**Reason:** the region depends on App\Repositories\UserRepository, whose contract is defined in another file
**Source:** `app/Repositories/UserRepository.php` :: `delete` (lines 39-42)
**Tokens:** 24

```php
    public function delete(int $id): void
    {
        User::findOrFail($id)->delete();
    }
```

## fetched · named_reference

**Subject:** App\Repositories\UserRepository
**Reason:** the region depends on App\Repositories\UserRepository, whose contract is defined in another file
**Source:** `app/Repositories/UserRepository.php` :: `getAll` (lines 11-14)
**Tokens:** 28

```php
    public function getAll(): LengthAwarePaginator
    {
        return User::with('roles')->paginate(15);
    }
```

## fetched · named_reference

**Subject:** App\Repositories\UserRepository
**Reason:** the region depends on App\Repositories\UserRepository, whose contract is defined in another file
**Source:** `app/Repositories/UserRepository.php` :: `getById` (lines 16-19)
**Tokens:** 27

```php
    public function getById(int $id): User
    {
        return User::with('roles')->findOrFail($id);
    }
```

## fetched · named_reference

**Subject:** App\Repositories\UserRepository
**Reason:** the region depends on App\Repositories\UserRepository, whose contract is defined in another file
**Source:** `app/Repositories/UserRepository.php` :: `update` (lines 31-37)
**Tokens:** 40

```php
    public function update(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->update($data);

        return $user;
    }
```

## flagged · unverifiable_premise

**Subject:** surrounding-transaction
**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Http/Controllers/Admin/CategoryController.php` (lines 2-42)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## Dropped

- **the region depends on App\DTOs\Branch\UpdateBranchDTO::fromRequest, whose contract is defined in another file** — below budget priority (299 tokens)
- **the region depends on App\Actions\Branch\UpdateBranchAction, whose contract is defined in another file** — below budget priority (272 tokens)
- **the region depends on App\Actions\Catering\UpdatePackageAction, whose contract is defined in another file** — below budget priority (268 tokens)
- **the region depends on App\DTOs\Dish\UpdateDishDTO::fromRequest, whose contract is defined in another file** — below budget priority (267 tokens)
- **the region depends on App\DTOs\Catering\UpdatePackageDTO::fromRequest, whose contract is defined in another file** — below budget priority (264 tokens)
- **the region depends on App\Http\Resources\Catering\PackageResource, whose contract is defined in another file** — below budget priority (261 tokens)
- **the region depends on App\Actions\Dish\UpdateDishAction, whose contract is defined in another file** — below budget priority (261 tokens)
- **the region depends on App\Http\Requests\Catering\UpdatePackageRequest, whose contract is defined in another file** — below budget priority (228 tokens)
- **the region depends on App\Http\Requests\Catering\StorePackageRequest, whose contract is defined in another file** — below budget priority (218 tokens)
- **the region depends on App\Actions\Branch\CreateBranchAction, whose contract is defined in another file** — below budget priority (218 tokens)
- **the region depends on App\Actions\Catering\UpdateSampleMenuAction, whose contract is defined in another file** — below budget priority (217 tokens)
- **the region depends on App\Actions\DeliveryApp\UpdateDeliveryAppAction, whose contract is defined in another file** — below budget priority (215 tokens)
- **the region depends on App\DTOs\Branch\CreateBranchDTO::fromRequest, whose contract is defined in another file** — below budget priority (214 tokens)
- **the region depends on App\Actions\Catering\CreatePackageAction, whose contract is defined in another file** — below budget priority (214 tokens)
- **the region depends on App\Http\Resources\Dish\DishResource, whose contract is defined in another file** — below budget priority (208 tokens)
- **the region depends on App\Actions\Dish\CreateDishAction, whose contract is defined in another file** — below budget priority (207 tokens)
- **the region depends on App\Http\Resources\Branch\BranchResource, whose contract is defined in another file** — below budget priority (206 tokens)
- **the region depends on App\DTOs\Catering\CreatePackageDTO::fromRequest, whose contract is defined in another file** — below budget priority (206 tokens)
- **the region depends on App\Actions\MediaItem\UploadMediaAction, whose contract is defined in another file** — below budget priority (203 tokens)
- **the region depends on App\Http\Resources\Catering\SampleMenuResource, whose contract is defined in another file** — below budget priority (190 tokens)
- **the region depends on App\Actions\Dish\GetDishesAction, whose contract is defined in another file** — below budget priority (190 tokens)
- **the region depends on App\Http\Requests\Branch\UpdateBranchRequest, whose contract is defined in another file** — below budget priority (187 tokens)
- **the region depends on App\Http\Resources\Catering\QuoteRequestResource, whose contract is defined in another file** — below budget priority (186 tokens)
- **the region depends on App\Http\Requests\Branch\StoreBranchRequest, whose contract is defined in another file** — below budget priority (184 tokens)
- **the region depends on App\DTOs\Dish\CreateDishDTO::fromRequest, whose contract is defined in another file** — below budget priority (180 tokens)
- **the region depends on App\Http\Requests\Dish\UpdateDishRequest, whose contract is defined in another file** — below budget priority (173 tokens)
- **the region depends on App\Http\Requests\Catering\StoreSampleMenuRequest, whose contract is defined in another file** — below budget priority (171 tokens)
- **the region depends on App\Actions\Catering\CreateSampleMenuAction, whose contract is defined in another file** — below budget priority (168 tokens)
- **the region depends on App\Actions\DeliveryApp\CreateDeliveryAppAction, whose contract is defined in another file** — below budget priority (166 tokens)
- **the region depends on App\Http\Resources\Timeline\TimelineResource, whose contract is defined in another file** — below budget priority (165 tokens)
- **the region depends on App\Http\Requests\Dish\StoreDishRequest, whose contract is defined in another file** — below budget priority (164 tokens)
- **the region depends on App\Http\Requests\Setting\UpdateSettingRequest, whose contract is defined in another file** — below budget priority (162 tokens)
- **the region depends on App\DTOs\Timeline\CreateTimelineDTO::fromRequest, whose contract is defined in another file** — below budget priority (153 tokens)
- **the region depends on App\DTOs\Catering\CreateSampleMenuDTO::fromRequest, whose contract is defined in another file** — below budget priority (135 tokens)
- **the region depends on App\Actions\Timeline\UpdateTimelineAction, whose contract is defined in another file** — below budget priority (133 tokens)
- **the region depends on App\Repositories\QuoteRequestRepository, whose contract is defined in another file** — below budget priority (131 tokens)
- **the region depends on App\Http\Resources\PageContent\PageContentResource, whose contract is defined in another file** — below budget priority (129 tokens)
- **the region depends on App\Actions\Timeline\CreateTimelineAction, whose contract is defined in another file** — below budget priority (129 tokens)
- **the region depends on App\Http\Resources\DeliveryApp\DeliveryAppResource, whose contract is defined in another file** — below budget priority (124 tokens)
- **the region depends on App\DTOs\User\UpdateUserDTO::fromRequest, whose contract is defined in another file** — below budget priority (122 tokens)
- **the region depends on App\Http\Resources\Setting\SettingResource, whose contract is defined in another file** — below budget priority (119 tokens)
- **the region depends on App\Http\Requests\Timeline\StoreTimelineRequest, whose contract is defined in another file** — below budget priority (119 tokens)
- **the region depends on App\Actions\User\UpdateUserAction, whose contract is defined in another file** — below budget priority (116 tokens)
- **the region depends on App\Http\Resources\MediaItem\MediaItemResource, whose contract is defined in another file** — below budget priority (115 tokens)
- **the region depends on App\DTOs\DeliveryApp\CreateDeliveryAppDTO::fromRequest, whose contract is defined in another file** — below budget priority (115 tokens)
- **the region depends on App\Http\Resources\Testimonial\TestimonialResource, whose contract is defined in another file** — below budget priority (113 tokens)
- **the region depends on App\DTOs\MediaItem\UploadMediaDTO::fromRequest, whose contract is defined in another file** — below budget priority (112 tokens)
- **the region depends on App\Actions\Auth\LoginAction, whose contract is defined in another file** — below budget priority (112 tokens)
- **the region depends on App\Actions\Testimonial\UpdateTestimonialAction, whose contract is defined in another file** — below budget priority (109 tokens)
- **the region depends on App\Actions\Testimonial\CreateTestimonialAction, whose contract is defined in another file** — below budget priority (105 tokens)
- **the region depends on App\Http\Requests\MediaItem\UploadMediaRequest, whose contract is defined in another file** — below budget priority (104 tokens)
- **the region depends on App\Http\Requests\DeliveryApp\StoreDeliveryAppRequest, whose contract is defined in another file** — below budget priority (104 tokens)
- **the region depends on App\DTOs\Testimonial\CreateTestimonialDTO::fromRequest, whose contract is defined in another file** — below budget priority (103 tokens)
- **the region depends on App\Http\Resources\Auth\AuthResource, whose contract is defined in another file** — below budget priority (102 tokens)
===== END context-bundle.md =====

===== BEGIN context-diagnostics.txt =====
unreadable path: app/Http/Controllers/Auth/AuthController.php (no assertions extracted)
unreadable path: app/Http/Controllers/BranchController.php (no assertions extracted)
unreadable path: app/Http/Controllers/CategoryController.php (no assertions extracted)
unreadable path: app/Http/Controllers/Catering/CateringPackageController.php (no assertions extracted)
unreadable path: app/Http/Controllers/Catering/QuoteRequestController.php (no assertions extracted)
unreadable path: app/Http/Controllers/Catering/SampleMenuController.php (no assertions extracted)
unreadable path: app/Http/Controllers/DeliveryAppController.php (no assertions extracted)
unreadable path: app/Http/Controllers/DishController.php (no assertions extracted)
unreadable path: app/Http/Controllers/MediaItemController.php (no assertions extracted)
unreadable path: app/Http/Controllers/PageContentController.php (no assertions extracted)
unreadable path: app/Http/Controllers/SettingController.php (no assertions extracted)
unreadable path: app/Http/Controllers/TestimonialController.php (no assertions extracted)
unreadable path: app/Http/Controllers/TimelineController.php (no assertions extracted)
unreadable path: app/Http/Controllers/UserController.php (no assertions extracted)
inherited member: App\Http\Controllers\Admin\Auth\AuthController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
inherited member: App\Http\Controllers\Admin\BranchController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\BranchController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\BranchController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\Branch\BranchResource::collection in app/Http/Controllers/Admin/BranchController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
inherited member: App\Http\Controllers\Admin\CategoryController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\CategoryController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\CategoryController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\Category\CategoryResource::collection in app/Http/Controllers/Admin/CategoryController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
inherited member: App\Http\Controllers\Admin\Catering\CateringPackageController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\Catering\CateringPackageController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\Catering\CateringPackageController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\Catering\PackageResource::collection in app/Http/Controllers/Admin/Catering/CateringPackageController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
inherited member: App\Http\Controllers\Admin\Catering\QuoteRequestController::paginated declared at app/Traits/ApiResponse.php:38 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\Catering\QuoteRequestController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\Catering\QuoteRequestResource::collection in app/Http/Controllers/Admin/Catering/QuoteRequestController.php
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
inherited member: App\Http\Controllers\Admin\Catering\SampleMenuController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\Catering\SampleMenuController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\Catering\SampleMenuController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\Catering\SampleMenuResource::collection in app/Http/Controllers/Admin/Catering/SampleMenuController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
inherited member: App\Http\Controllers\Admin\DeliveryAppController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\DeliveryAppController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\DeliveryAppController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\DeliveryApp\DeliveryAppResource::collection in app/Http/Controllers/Admin/DeliveryAppController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
inherited member: App\Http\Controllers\Admin\DishController::paginated declared at app/Traits/ApiResponse.php:38 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\DishController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\DishController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\DishController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\Dish\DishResource::collection in app/Http/Controllers/Admin/DishController.php
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
inherited member: App\Http\Controllers\Admin\MediaItemController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\MediaItemController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\MediaItemController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
inherited member: App\Http\Controllers\Admin\PageContentController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
inherited member: App\Http\Controllers\Admin\SettingController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
inherited member: App\Http\Controllers\Admin\TestimonialController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\TestimonialController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\TestimonialController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\Testimonial\TestimonialResource::collection in app/Http/Controllers/Admin/TestimonialController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
inherited member: App\Http\Controllers\Admin\TimelineController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\TimelineController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\TimelineController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\Timeline\TimelineResource::collection in app/Http/Controllers/Admin/TimelineController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
inherited member: App\Http\Controllers\Admin\UserController::paginated declared at app/Traits/ApiResponse.php:38 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\UserController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\UserController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Admin\UserController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\User\UserResource::collection in app/Http/Controllers/Admin/UserController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
framework reference: Illuminate\Support\Facades\Route::prefix declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:100 (@method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix))
framework reference: Illuminate\Support\Facades\Route::prefix declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:100 (@method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix))
framework reference: Illuminate\Support\Facades\Route::prefix declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:100 (@method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix))
framework reference: Illuminate\Support\Facades\Route::prefix declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:100 (@method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix))
framework reference: Illuminate\Support\Facades\Route::prefix declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:100 (@method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix))
framework reference: Illuminate\Support\Facades\Route::prefix declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:100 (@method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix))
framework reference: Illuminate\Support\Facades\Route::prefix declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:100 (@method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix))
framework reference: Illuminate\Support\Facades\Route::prefix declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:100 (@method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix))
framework reference: Illuminate\Support\Facades\Route::prefix declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:100 (@method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix))
===== END context-diagnostics.txt =====
