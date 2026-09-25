<!-- cell-50 -->
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
diff --git a/app/Http/Controllers/Auth/AuthController.php b/app/Http/Controllers/Auth/AuthController.php
index e4e6283..028d0a0 100644
--- a/app/Http/Controllers/Auth/AuthController.php
+++ b/app/Http/Controllers/Auth/AuthController.php
@@ -18,30 +18,18 @@ public function login(LoginRequest $request, LoginAction $action): JsonResponse
     {
         $result = $action->execute(LoginDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Login successful',
-            'data' => new AuthResource($result),
-        ]);
+        return $this->success(new AuthResource($result), 'Login successful');
     }
 
     public function logout(Request $request, LogoutAction $action): JsonResponse
     {
         $action->execute($request->user());
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Logout successful',
-            'data' => null,
-        ]);
+        return $this->success(null, 'Logout successful');
     }
 
     public function me(Request $request): JsonResponse
     {
-        return response()->json([
-            'success' => true,
-            'message' => 'Authenticated user',
-            'data' => new UserResource($request->user()),
-        ]);
+        return $this->success(new UserResource($request->user()), 'Authenticated user');
     }
 }
diff --git a/app/Http/Controllers/BranchController.php b/app/Http/Controllers/BranchController.php
index 10aeeb5..a9cb02f 100644
--- a/app/Http/Controllers/BranchController.php
+++ b/app/Http/Controllers/BranchController.php
@@ -17,43 +17,27 @@ class BranchController extends Controller
 {
     public function index(GetBranchesAction $action): JsonResponse
     {
-        return response()->json([
-            'success' => true,
-            'message' => 'Branches retrieved',
-            'data' => BranchResource::collection($action->execute()),
-        ]);
+        return $this->success(BranchResource::collection($action->execute()), 'Branches retrieved');
     }
 
     public function store(StoreBranchRequest $request, CreateBranchAction $action): JsonResponse
     {
         $branch = $action->execute(CreateBranchDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Branch created',
-            'data' => new BranchResource($branch),
-        ], 201);
+        return $this->created(new BranchResource($branch), 'Branch created');
     }
 
     public function update(UpdateBranchRequest $request, int $id, UpdateBranchAction $action): JsonResponse
     {
         $branch = $action->execute($id, UpdateBranchDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Branch updated',
-            'data' => new BranchResource($branch),
-        ]);
+        return $this->success(new BranchResource($branch), 'Branch updated');
     }
 
     public function destroy(int $id, DeleteBranchAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Branch deleted',
-            'data' => null,
-        ]);
+        return $this->deleted('Branch deleted');
     }
 }
diff --git a/app/Http/Controllers/CategoryController.php b/app/Http/Controllers/CategoryController.php
index 119e920..2a886d6 100644
--- a/app/Http/Controllers/CategoryController.php
+++ b/app/Http/Controllers/CategoryController.php
@@ -15,43 +15,27 @@ public function __construct(
 
     public function index(): JsonResponse
     {
-        return response()->json([
-            'success' => true,
-            'message' => 'Categories retrieved',
-            'data' => CategoryResource::collection($this->repository->getAll()),
-        ]);
+        return $this->success(CategoryResource::collection($this->repository->getAll()), 'Categories retrieved');
     }
 
     public function store(Request $request): JsonResponse
     {
         $category = $this->repository->create($request->only(['name_ar', 'name_en', 'slug', 'order']));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Category created',
-            'data' => new CategoryResource($category),
-        ], 201);
+        return $this->created(new CategoryResource($category), 'Category created');
     }
 
     public function update(Request $request, int $id): JsonResponse
     {
         $category = $this->repository->update($id, $request->only(['name_ar', 'name_en', 'slug', 'order']));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Category updated',
-            'data' => new CategoryResource($category),
-        ]);
+        return $this->success(new CategoryResource($category), 'Category updated');
     }
 
     public function destroy(int $id): JsonResponse
     {
         $this->repository->delete($id);
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Category deleted',
-            'data' => null,
-        ]);
+        return $this->deleted('Category deleted');
     }
 }
diff --git a/app/Http/Controllers/Catering/CateringPackageController.php b/app/Http/Controllers/Catering/CateringPackageController.php
index 413f202..4fdd1b8 100644
--- a/app/Http/Controllers/Catering/CateringPackageController.php
+++ b/app/Http/Controllers/Catering/CateringPackageController.php
@@ -18,43 +18,27 @@ class CateringPackageController extends Controller
 {
     public function index(GetPackagesAction $action): JsonResponse
     {
-        return response()->json([
-            'success' => true,
-            'message' => 'Catering packages retrieved',
-            'data' => PackageResource::collection($action->execute()),
-        ]);
+        return $this->success(PackageResource::collection($action->execute()), 'Catering packages retrieved');
     }
 
     public function store(StorePackageRequest $request, CreatePackageAction $action): JsonResponse
     {
         $package = $action->execute(CreatePackageDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Catering package created',
-            'data' => new PackageResource($package),
-        ], 201);
+        return $this->created(new PackageResource($package), 'Catering package created');
     }
 
     public function update(UpdatePackageRequest $request, int $id, UpdatePackageAction $action): JsonResponse
     {
         $package = $action->execute($id, UpdatePackageDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Catering package updated',
-            'data' => new PackageResource($package),
-        ]);
+        return $this->success(new PackageResource($package), 'Catering package updated');
     }
 
     public function destroy(int $id, DeletePackageAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Catering package deleted',
-            'data' => null,
-        ]);
+        return $this->deleted('Catering package deleted');
     }
 }
diff --git a/app/Http/Controllers/Catering/QuoteRequestController.php b/app/Http/Controllers/Catering/QuoteRequestController.php
index 21e4fe8..63ea6c1 100644
--- a/app/Http/Controllers/Catering/QuoteRequestController.php
+++ b/app/Http/Controllers/Catering/QuoteRequestController.php
@@ -20,11 +20,7 @@ public function store(StoreQuoteRequestRequest $request, SubmitQuoteRequestActio
     {
         $quoteRequest = $action->execute(SubmitQuoteRequestDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Quote request submitted',
-            'data' => new QuoteRequestResource($quoteRequest),
-        ], 201);
+        return $this->created(new QuoteRequestResource($quoteRequest), 'Quote request submitted');
     }
 
     public function index(Request $request, QuoteRequestRepository $repository): JsonResponse
@@ -37,38 +33,18 @@ public function index(Request $request, QuoteRequestRepository $repository): Jso
 
         $result = $repository->getAll($filters);
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Quote requests retrieved',
-            'data' => [
-                'items' => QuoteRequestResource::collection($result),
-                'meta' => [
-                    'current_page' => $result->currentPage(),
-                    'last_page' => $result->lastPage(),
-                    'per_page' => $result->perPage(),
-                    'total' => $result->total(),
-                ],
-            ],
-        ]);
+        return $this->paginated(QuoteRequestResource::collection($result), $result, 'Quote requests retrieved');
     }
 
     public function show(int $id, QuoteRequestRepository $repository): JsonResponse
     {
-        return response()->json([
-            'success' => true,
-            'message' => 'Quote request retrieved',
-            'data' => new QuoteRequestResource($repository->getById($id)),
-        ]);
+        return $this->success(new QuoteRequestResource($repository->getById($id)), 'Quote request retrieved');
     }
 
     public function updateStatus(UpdateQuoteRequestStatusRequest $request, int $id, UpdateQuoteRequestStatusAction $action): JsonResponse
     {
         $quoteRequest = $action->execute($id, UpdateQuoteRequestStatusDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Quote request status updated',
-            'data' => new QuoteRequestResource($quoteRequest),
-        ]);
+        return $this->success(new QuoteRequestResource($quoteRequest), 'Quote request status updated');
     }
 }
diff --git a/app/Http/Controllers/Catering/SampleMenuController.php b/app/Http/Controllers/Catering/SampleMenuController.php
index 7aa3186..4919e73 100644
--- a/app/Http/Controllers/Catering/SampleMenuController.php
+++ b/app/Http/Controllers/Catering/SampleMenuController.php
@@ -16,43 +16,27 @@ class SampleMenuController extends Controller
 {
     public function index(GetSampleMenusAction $action): JsonResponse
     {
-        return response()->json([
-            'success' => true,
-            'message' => 'Sample menus retrieved',
-            'data' => SampleMenuResource::collection($action->execute()),
-        ]);
+        return $this->success(SampleMenuResource::collection($action->execute()), 'Sample menus retrieved');
     }
 
     public function store(StoreSampleMenuRequest $request, CreateSampleMenuAction $action): JsonResponse
     {
         $menu = $action->execute(CreateSampleMenuDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Sample menu created',
-            'data' => new SampleMenuResource($menu),
-        ], 201);
+        return $this->created(new SampleMenuResource($menu), 'Sample menu created');
     }
 
     public function update(StoreSampleMenuRequest $request, int $id, UpdateSampleMenuAction $action): JsonResponse
     {
         $menu = $action->execute($id, CreateSampleMenuDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Sample menu updated',
-            'data' => new SampleMenuResource($menu),
-        ]);
+        return $this->success(new SampleMenuResource($menu), 'Sample menu updated');
     }
 
     public function destroy(int $id, DeleteSampleMenuAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Sample menu deleted',
-            'data' => null,
-        ]);
+        return $this->deleted('Sample menu deleted');
     }
 }
diff --git a/app/Http/Controllers/Controller.php b/app/Http/Controllers/Controller.php
index 8677cd5..f87ab02 100644
--- a/app/Http/Controllers/Controller.php
+++ b/app/Http/Controllers/Controller.php
@@ -2,7 +2,9 @@
 
 namespace App\Http\Controllers;
 
+use App\Traits\ApiResponse;
+
 abstract class Controller
 {
-    //
+    use ApiResponse;
 }
diff --git a/app/Http/Controllers/DeliveryAppController.php b/app/Http/Controllers/DeliveryAppController.php
index adb8717..331269c 100644
--- a/app/Http/Controllers/DeliveryAppController.php
+++ b/app/Http/Controllers/DeliveryAppController.php
@@ -17,43 +17,27 @@ public function index(GetDeliveryAppsAction $action): JsonResponse
     {
         $activeOnly = ! auth('sanctum')->check();
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Delivery apps retrieved',
-            'data' => DeliveryAppResource::collection($action->execute($activeOnly)),
-        ]);
+        return $this->success(DeliveryAppResource::collection($action->execute($activeOnly)), 'Delivery apps retrieved');
     }
 
     public function store(StoreDeliveryAppRequest $request, CreateDeliveryAppAction $action): JsonResponse
     {
         $deliveryApp = $action->execute(CreateDeliveryAppDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Delivery app created',
-            'data' => new DeliveryAppResource($deliveryApp),
-        ], 201);
+        return $this->created(new DeliveryAppResource($deliveryApp), 'Delivery app created');
     }
 
     public function update(StoreDeliveryAppRequest $request, int $id, UpdateDeliveryAppAction $action): JsonResponse
     {
         $deliveryApp = $action->execute($id, CreateDeliveryAppDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Delivery app updated',
-            'data' => new DeliveryAppResource($deliveryApp),
-        ]);
+        return $this->success(new DeliveryAppResource($deliveryApp), 'Delivery app updated');
     }
 
     public function destroy(int $id, DeleteDeliveryAppAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Delivery app deleted',
-            'data' => null,
-        ]);
+        return $this->deleted('Delivery app deleted');
     }
 }
diff --git a/app/Http/Controllers/DishController.php b/app/Http/Controllers/DishController.php
index a8d9a22..3a2c214 100644
--- a/app/Http/Controllers/DishController.php
+++ b/app/Http/Controllers/DishController.php
@@ -29,52 +29,32 @@ public function index(Request $request, GetDishesAction $action): JsonResponse
 
         $result = $action->execute($filters);
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Dishes retrieved',
-            'data' => new DishCollection($result),
-        ]);
+        return $this->success(new DishCollection($result), 'Dishes retrieved');
     }
 
     public function show(int $id, GetDishAction $action): JsonResponse
     {
-        return response()->json([
-            'success' => true,
-            'message' => 'Dish retrieved',
-            'data' => new DishResource($action->execute($id)),
-        ]);
+        return $this->success(new DishResource($action->execute($id)), 'Dish retrieved');
     }
 
     public function store(StoreDishRequest $request, CreateDishAction $action): JsonResponse
     {
         $dish = $action->execute(CreateDishDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Dish created',
-            'data' => new DishResource($dish),
-        ], 201);
+        return $this->created(new DishResource($dish), 'Dish created');
     }
 
     public function update(UpdateDishRequest $request, int $id, UpdateDishAction $action): JsonResponse
     {
         $dish = $action->execute($id, UpdateDishDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Dish updated',
-            'data' => new DishResource($dish),
-        ]);
+        return $this->success(new DishResource($dish), 'Dish updated');
     }
 
     public function destroy(int $id, DeleteDishAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Dish deleted',
-            'data' => null,
-        ]);
+        return $this->deleted('Dish deleted');
     }
 }
diff --git a/app/Http/Controllers/MediaItemController.php b/app/Http/Controllers/MediaItemController.php
index b0e6901..6707b12 100644
--- a/app/Http/Controllers/MediaItemController.php
+++ b/app/Http/Controllers/MediaItemController.php
@@ -25,52 +25,32 @@ public function index(Request $request, GetMediaItemsAction $action): JsonRespon
             fn ($section) => $section->map(fn ($item) => new MediaItemResource($item))
         );
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Media items retrieved',
-            'data' => $resourced,
-        ]);
+        return $this->success($resourced, 'Media items retrieved');
     }
 
     public function store(UploadMediaRequest $request, UploadMediaAction $action): JsonResponse
     {
         $mediaItem = $action->execute(UploadMediaDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Media item uploaded',
-            'data' => new MediaItemResource($mediaItem),
-        ], 201);
+        return $this->created(new MediaItemResource($mediaItem), 'Media item uploaded');
     }
 
     public function show(int $id, MediaItemRepository $repository): JsonResponse
     {
-        return response()->json([
-            'success' => true,
-            'message' => 'Media item retrieved',
-            'data' => new MediaItemResource($repository->getById($id)),
-        ]);
+        return $this->success(new MediaItemResource($repository->getById($id)), 'Media item retrieved');
     }
 
     public function update(UploadMediaRequest $request, int $id, UploadMediaAction $action): JsonResponse
     {
         $mediaItem = $action->execute(UploadMediaDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Media item updated',
-            'data' => new MediaItemResource($mediaItem),
-        ]);
+        return $this->success(new MediaItemResource($mediaItem), 'Media item updated');
     }
 
     public function destroy(int $id, DeleteMediaAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Media item deleted',
-            'data' => null,
-        ]);
+        return $this->deleted('Media item deleted');
     }
 }
diff --git a/app/Http/Controllers/PageContentController.php b/app/Http/Controllers/PageContentController.php
index f8a17f5..ff4f0ad 100644
--- a/app/Http/Controllers/PageContentController.php
+++ b/app/Http/Controllers/PageContentController.php
@@ -27,41 +27,25 @@ public function index(Request $request, GetPageContentAction $action): JsonRespo
             fn ($section) => $section->map(fn ($item) => new PageContentResource($item))
         );
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Page contents retrieved',
-            'data' => $resourced,
-        ]);
+        return $this->success($resourced, 'Page contents retrieved');
     }
 
     public function show(int $id, PageContentRepository $repository): JsonResponse
     {
-        return response()->json([
-            'success' => true,
-            'message' => 'Page content retrieved',
-            'data' => new PageContentResource($repository->getById($id)),
-        ]);
+        return $this->success(new PageContentResource($repository->getById($id)), 'Page content retrieved');
     }
 
     public function update(UpdatePageContentRequest $request, int $id, UpdatePageContentAction $action): JsonResponse
     {
         $pageContent = $action->execute($id, UpdatePageContentDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Page content updated',
-            'data' => new PageContentResource($pageContent),
-        ]);
+        return $this->success(new PageContentResource($pageContent), 'Page content updated');
     }
 
     public function bulkUpdate(BulkUpdatePageContentRequest $request, BulkUpdatePageContentAction $action): JsonResponse
     {
         $action->execute(BulkUpdatePageContentDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Page contents updated',
-            'data' => null,
-        ]);
+        return $this->success(null, 'Page contents updated');
     }
 }
diff --git a/app/Http/Controllers/SettingController.php b/app/Http/Controllers/SettingController.php
index 5267a36..25a4fd8 100644
--- a/app/Http/Controllers/SettingController.php
+++ b/app/Http/Controllers/SettingController.php
@@ -18,21 +18,13 @@ public function index(Request $request, GetSettingsAction $action): JsonResponse
             $request->filled('group') ? $request->string('group')->toString() : null,
         );
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Settings retrieved',
-            'data' => $result->map(fn ($setting) => new SettingResource($setting)),
-        ]);
+        return $this->success($result->map(fn ($setting) => new SettingResource($setting)), 'Settings retrieved');
     }
 
     public function bulkUpdate(UpdateSettingRequest $request, UpdateSettingsAction $action): JsonResponse
     {
         $action->execute(UpdateSettingDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Settings updated',
-            'data' => null,
-        ]);
+        return $this->success(null, 'Settings updated');
     }
 }
diff --git a/app/Http/Controllers/TestimonialController.php b/app/Http/Controllers/TestimonialController.php
index 1eaddb8..0a99885 100644
--- a/app/Http/Controllers/TestimonialController.php
+++ b/app/Http/Controllers/TestimonialController.php
@@ -17,43 +17,27 @@ public function index(GetTestimonialsAction $action): JsonResponse
     {
         $activeOnly = ! auth('sanctum')->check();
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Testimonials retrieved',
-            'data' => TestimonialResource::collection($action->execute($activeOnly)),
-        ]);
+        return $this->success(TestimonialResource::collection($action->execute($activeOnly)), 'Testimonials retrieved');
     }
 
     public function store(StoreTestimonialRequest $request, CreateTestimonialAction $action): JsonResponse
     {
         $testimonial = $action->execute(CreateTestimonialDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Testimonial created',
-            'data' => new TestimonialResource($testimonial),
-        ], 201);
+        return $this->created(new TestimonialResource($testimonial), 'Testimonial created');
     }
 
     public function update(StoreTestimonialRequest $request, int $id, UpdateTestimonialAction $action): JsonResponse
     {
         $testimonial = $action->execute($id, CreateTestimonialDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Testimonial updated',
-            'data' => new TestimonialResource($testimonial),
-        ]);
+        return $this->success(new TestimonialResource($testimonial), 'Testimonial updated');
     }
 
     public function destroy(int $id, DeleteTestimonialAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Testimonial deleted',
-            'data' => null,
-        ]);
+        return $this->deleted('Testimonial deleted');
     }
 }
diff --git a/app/Http/Controllers/TimelineController.php b/app/Http/Controllers/TimelineController.php
index 1d9ad41..a686e45 100644
--- a/app/Http/Controllers/TimelineController.php
+++ b/app/Http/Controllers/TimelineController.php
@@ -15,43 +15,27 @@ class TimelineController extends Controller
 {
     public function index(GetTimelineAction $action): JsonResponse
     {
-        return response()->json([
-            'success' => true,
-            'message' => 'Timeline retrieved',
-            'data' => TimelineResource::collection($action->execute()),
-        ]);
+        return $this->success(TimelineResource::collection($action->execute()), 'Timeline retrieved');
     }
 
     public function store(StoreTimelineRequest $request, CreateTimelineAction $action): JsonResponse
     {
         $timeline = $action->execute(CreateTimelineDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Timeline entry created',
-            'data' => new TimelineResource($timeline),
-        ], 201);
+        return $this->created(new TimelineResource($timeline), 'Timeline entry created');
     }
 
     public function update(StoreTimelineRequest $request, int $id, UpdateTimelineAction $action): JsonResponse
     {
         $timeline = $action->execute($id, CreateTimelineDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Timeline entry updated',
-            'data' => new TimelineResource($timeline),
-        ]);
+        return $this->success(new TimelineResource($timeline), 'Timeline entry updated');
     }
 
     public function destroy(int $id, DeleteTimelineAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Timeline entry deleted',
-            'data' => null,
-        ]);
+        return $this->deleted('Timeline entry deleted');
     }
 }
diff --git a/app/Http/Controllers/UserController.php b/app/Http/Controllers/UserController.php
index 204c70b..6261341 100644
--- a/app/Http/Controllers/UserController.php
+++ b/app/Http/Controllers/UserController.php
@@ -20,60 +20,32 @@ public function index(GetUsersAction $action): JsonResponse
     {
         $result = $action->execute();
 
-        return response()->json([
-            'success' => true,
-            'message' => 'Users retrieved',
-            'data' => [
-                'items' => UserResource::collection($result),
-                'meta' => [
-                    'current_page' => $result->currentPage(),
-                    'last_page' => $result->lastPage(),
-                    'per_page' => $result->perPage(),
-                    'total' => $result->total(),
-                ],
-            ],
-        ]);
+        return $this->paginated(UserResource::collection($result), $result, 'Users retrieved');
     }
 
     public function store(StoreUserRequest $request, CreateUserAction $action): JsonResponse
     {
         $user = $action->execute(CreateUserDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'User created',
-            'data' => new UserResource($user),
-        ], 201);
+        return $this->created(new UserResource($user), 'User created');
     }
 
     public function show(int $id, UserRepository $repository): JsonResponse
     {
-        return response()->json([
-            'success' => true,
-            'message' => 'User retrieved',
-            'data' => new UserResource($repository->getById($id)),
-        ]);
+        return $this->success(new UserResource($repository->getById($id)), 'User retrieved');
     }
 
     public function update(UpdateUserRequest $request, int $id, UpdateUserAction $action): JsonResponse
     {
         $user = $action->execute($id, UpdateUserDTO::fromRequest($request));
 
-        return response()->json([
-            'success' => true,
-            'message' => 'User updated',
-            'data' => new UserResource($user),
-        ]);
+        return $this->success(new UserResource($user), 'User updated');
     }
 
     public function destroy(int $id, DeleteUserAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return response()->json([
-            'success' => true,
-            'message' => 'User deleted',
-            'data' => null,
-        ]);
+        return $this->deleted('User deleted');
     }
 }
diff --git a/app/Traits/ApiResponse.php b/app/Traits/ApiResponse.php
new file mode 100644
index 0000000..4223952
--- /dev/null
+++ b/app/Traits/ApiResponse.php
@@ -0,0 +1,69 @@
+<?php
+
+namespace App\Traits;
+
+use Illuminate\Http\JsonResponse;
+
+trait ApiResponse
+{
+    protected function success(
+        mixed $data = null,
+        string $message = 'Operation completed successfully',
+        int $status = 200
+    ): JsonResponse {
+        return response()->json([
+            'success' => true,
+            'message' => $message,
+            'data'    => $data,
+        ], $status);
+    }
+
+    protected function created(
+        mixed $data = null,
+        string $message = 'Created successfully'
+    ): JsonResponse {
+        return $this->success($data, $message, 201);
+    }
+
+    protected function deleted(
+        string $message = 'Deleted successfully'
+    ): JsonResponse {
+        return response()->json([
+            'success' => true,
+            'message' => $message,
+            'data'    => null,
+        ], 200);
+    }
+
+    protected function paginated(
+        mixed $collection,
+        mixed $paginator,
+        string $message = 'Data fetched successfully'
+    ): JsonResponse {
+        return response()->json([
+            'success' => true,
+            'message' => $message,
+            'data'    => [
+                'items' => $collection,
+                'meta'  => [
+                    'current_page' => $paginator->currentPage(),
+                    'last_page'    => $paginator->lastPage(),
+                    'per_page'     => $paginator->perPage(),
+                    'total'        => $paginator->total(),
+                ],
+            ],
+        ], 200);
+    }
+
+    protected function error(
+        string $message = 'Something went wrong',
+        int $status = 400,
+        array $errors = []
+    ): JsonResponse {
+        return response()->json([
+            'success' => false,
+            'message' => $message,
+            'errors'  => $errors,
+        ], $status);
+    }
+}
===== END change.diff =====

===== BEGIN context-bundle.md =====
# Context bundle

bundle_version 2 · budget 8000 / used 4722 tokens

## flagged · named_reference

**Subject:** App\Http\Controllers\Auth\AuthController::success
**Reason:** the region calls App\Http\Controllers\Auth\AuthController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Auth/AuthController.php` :: `App\Http\Controllers\Auth\AuthController::success` (lines 18-35)
**Tokens:** 61

```text
ASSUMPTION: success() is not declared in AuthController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\BranchController::created
**Reason:** the region calls App\Http\Controllers\BranchController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/BranchController.php` :: `App\Http\Controllers\BranchController::created` (lines 17-43)
**Tokens:** 62

```text
ASSUMPTION: created() is not declared in BranchController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\BranchController::deleted
**Reason:** the region calls App\Http\Controllers\BranchController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/BranchController.php` :: `App\Http\Controllers\BranchController::deleted` (lines 17-43)
**Tokens:** 62

```text
ASSUMPTION: deleted() is not declared in BranchController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\BranchController::success
**Reason:** the region calls App\Http\Controllers\BranchController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/BranchController.php` :: `App\Http\Controllers\BranchController::success` (lines 17-43)
**Tokens:** 62

```text
ASSUMPTION: success() is not declared in BranchController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Branch\BranchResource::collection
**Reason:** the region depends on App\Http\Resources\Branch\BranchResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/BranchController.php` :: `App\Http\Resources\Branch\BranchResource::collection` (lines 17-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\CategoryController::created
**Reason:** the region calls App\Http\Controllers\CategoryController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/CategoryController.php` :: `App\Http\Controllers\CategoryController::created` (lines 15-41)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in CategoryController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\CategoryController::deleted
**Reason:** the region calls App\Http\Controllers\CategoryController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/CategoryController.php` :: `App\Http\Controllers\CategoryController::deleted` (lines 15-41)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in CategoryController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\CategoryController::success
**Reason:** the region calls App\Http\Controllers\CategoryController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/CategoryController.php` :: `App\Http\Controllers\CategoryController::success` (lines 15-41)
**Tokens:** 62

```text
ASSUMPTION: success() is not declared in CategoryController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Category\CategoryResource::collection
**Reason:** the region depends on App\Http\Resources\Category\CategoryResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/CategoryController.php` :: `App\Http\Resources\Category\CategoryResource::collection` (lines 15-41)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\CateringPackageController::created
**Reason:** the region calls App\Http\Controllers\Catering\CateringPackageController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/CateringPackageController.php` :: `App\Http\Controllers\Catering\CateringPackageController::created` (lines 18-44)
**Tokens:** 64

```text
ASSUMPTION: created() is not declared in CateringPackageController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\CateringPackageController::deleted
**Reason:** the region calls App\Http\Controllers\Catering\CateringPackageController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/CateringPackageController.php` :: `App\Http\Controllers\Catering\CateringPackageController::deleted` (lines 18-44)
**Tokens:** 64

```text
ASSUMPTION: deleted() is not declared in CateringPackageController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\CateringPackageController::success
**Reason:** the region calls App\Http\Controllers\Catering\CateringPackageController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/CateringPackageController.php` :: `App\Http\Controllers\Catering\CateringPackageController::success` (lines 18-44)
**Tokens:** 64

```text
ASSUMPTION: success() is not declared in CateringPackageController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Catering\PackageResource::collection
**Reason:** the region depends on App\Http\Resources\Catering\PackageResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Catering/CateringPackageController.php` :: `App\Http\Resources\Catering\PackageResource::collection` (lines 18-44)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\QuoteRequestController::created
**Reason:** the region calls App\Http\Controllers\Catering\QuoteRequestController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/QuoteRequestController.php` :: `App\Http\Controllers\Catering\QuoteRequestController::created` (lines 20-26)
**Tokens:** 64

```text
ASSUMPTION: created() is not declared in QuoteRequestController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\QuoteRequestController::paginated
**Reason:** the region calls App\Http\Controllers\Catering\QuoteRequestController::paginated, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/QuoteRequestController.php` :: `App\Http\Controllers\Catering\QuoteRequestController::paginated` (lines 33-50)
**Tokens:** 64

```text
ASSUMPTION: paginated() is not declared in QuoteRequestController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:38, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\QuoteRequestController::success
**Reason:** the region calls App\Http\Controllers\Catering\QuoteRequestController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/QuoteRequestController.php` :: `App\Http\Controllers\Catering\QuoteRequestController::success` (lines 33-50)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in QuoteRequestController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Catering\QuoteRequestResource::collection
**Reason:** the region depends on App\Http\Resources\Catering\QuoteRequestResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Catering/QuoteRequestController.php` :: `App\Http\Resources\Catering\QuoteRequestResource::collection` (lines 33-50)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\SampleMenuController::created
**Reason:** the region calls App\Http\Controllers\Catering\SampleMenuController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/SampleMenuController.php` :: `App\Http\Controllers\Catering\SampleMenuController::created` (lines 16-42)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in SampleMenuController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\SampleMenuController::deleted
**Reason:** the region calls App\Http\Controllers\Catering\SampleMenuController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/SampleMenuController.php` :: `App\Http\Controllers\Catering\SampleMenuController::deleted` (lines 16-42)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in SampleMenuController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\Catering\SampleMenuController::success
**Reason:** the region calls App\Http\Controllers\Catering\SampleMenuController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/Catering/SampleMenuController.php` :: `App\Http\Controllers\Catering\SampleMenuController::success` (lines 16-42)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in SampleMenuController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Catering\SampleMenuResource::collection
**Reason:** the region depends on App\Http\Resources\Catering\SampleMenuResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Catering/SampleMenuController.php` :: `App\Http\Resources\Catering\SampleMenuResource::collection` (lines 16-42)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\DeliveryAppController::created
**Reason:** the region calls App\Http\Controllers\DeliveryAppController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/DeliveryAppController.php` :: `App\Http\Controllers\DeliveryAppController::created` (lines 17-43)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in DeliveryAppController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\DeliveryAppController::deleted
**Reason:** the region calls App\Http\Controllers\DeliveryAppController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/DeliveryAppController.php` :: `App\Http\Controllers\DeliveryAppController::deleted` (lines 17-43)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in DeliveryAppController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\DeliveryAppController::success
**Reason:** the region calls App\Http\Controllers\DeliveryAppController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/DeliveryAppController.php` :: `App\Http\Controllers\DeliveryAppController::success` (lines 17-43)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in DeliveryAppController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\DeliveryApp\DeliveryAppResource::collection
**Reason:** the region depends on App\Http\Resources\DeliveryApp\DeliveryAppResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/DeliveryAppController.php` :: `App\Http\Resources\DeliveryApp\DeliveryAppResource::collection` (lines 17-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\DishController::created
**Reason:** the region calls App\Http\Controllers\DishController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/DishController.php` :: `App\Http\Controllers\DishController::created` (lines 29-60)
**Tokens:** 62

```text
ASSUMPTION: created() is not declared in DishController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\DishController::deleted
**Reason:** the region calls App\Http\Controllers\DishController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/DishController.php` :: `App\Http\Controllers\DishController::deleted` (lines 29-60)
**Tokens:** 62

```text
ASSUMPTION: deleted() is not declared in DishController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\DishController::success
**Reason:** the region calls App\Http\Controllers\DishController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/DishController.php` :: `App\Http\Controllers\DishController::success` (lines 29-60)
**Tokens:** 61

```text
ASSUMPTION: success() is not declared in DishController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\MediaItemController::created
**Reason:** the region calls App\Http\Controllers\MediaItemController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/MediaItemController.php` :: `App\Http\Controllers\MediaItemController::created` (lines 25-56)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in MediaItemController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\MediaItemController::deleted
**Reason:** the region calls App\Http\Controllers\MediaItemController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/MediaItemController.php` :: `App\Http\Controllers\MediaItemController::deleted` (lines 25-56)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in MediaItemController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\MediaItemController::success
**Reason:** the region calls App\Http\Controllers\MediaItemController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/MediaItemController.php` :: `App\Http\Controllers\MediaItemController::success` (lines 25-56)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in MediaItemController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\PageContentController::success
**Reason:** the region calls App\Http\Controllers\PageContentController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/PageContentController.php` :: `App\Http\Controllers\PageContentController::success` (lines 27-51)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in PageContentController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\SettingController::success
**Reason:** the region calls App\Http\Controllers\SettingController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/SettingController.php` :: `App\Http\Controllers\SettingController::success` (lines 18-30)
**Tokens:** 62

```text
ASSUMPTION: success() is not declared in SettingController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\TestimonialController::created
**Reason:** the region calls App\Http\Controllers\TestimonialController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/TestimonialController.php` :: `App\Http\Controllers\TestimonialController::created` (lines 17-43)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in TestimonialController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\TestimonialController::deleted
**Reason:** the region calls App\Http\Controllers\TestimonialController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/TestimonialController.php` :: `App\Http\Controllers\TestimonialController::deleted` (lines 17-43)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in TestimonialController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\TestimonialController::success
**Reason:** the region calls App\Http\Controllers\TestimonialController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/TestimonialController.php` :: `App\Http\Controllers\TestimonialController::success` (lines 17-43)
**Tokens:** 63

```text
ASSUMPTION: success() is not declared in TestimonialController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Testimonial\TestimonialResource::collection
**Reason:** the region depends on App\Http\Resources\Testimonial\TestimonialResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/TestimonialController.php` :: `App\Http\Resources\Testimonial\TestimonialResource::collection` (lines 17-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\TimelineController::created
**Reason:** the region calls App\Http\Controllers\TimelineController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/TimelineController.php` :: `App\Http\Controllers\TimelineController::created` (lines 15-41)
**Tokens:** 63

```text
ASSUMPTION: created() is not declared in TimelineController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\TimelineController::deleted
**Reason:** the region calls App\Http\Controllers\TimelineController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/TimelineController.php` :: `App\Http\Controllers\TimelineController::deleted` (lines 15-41)
**Tokens:** 63

```text
ASSUMPTION: deleted() is not declared in TimelineController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\TimelineController::success
**Reason:** the region calls App\Http\Controllers\TimelineController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/TimelineController.php` :: `App\Http\Controllers\TimelineController::success` (lines 15-41)
**Tokens:** 62

```text
ASSUMPTION: success() is not declared in TimelineController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\Timeline\TimelineResource::collection
**Reason:** the region depends on App\Http\Resources\Timeline\TimelineResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/TimelineController.php` :: `App\Http\Resources\Timeline\TimelineResource::collection` (lines 15-41)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\UserController::created
**Reason:** the region calls App\Http\Controllers\UserController::created, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/UserController.php` :: `App\Http\Controllers\UserController::created` (lines 20-51)
**Tokens:** 62

```text
ASSUMPTION: created() is not declared in UserController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:21, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\UserController::deleted
**Reason:** the region calls App\Http\Controllers\UserController::deleted, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/UserController.php` :: `App\Http\Controllers\UserController::deleted` (lines 20-51)
**Tokens:** 62

```text
ASSUMPTION: deleted() is not declared in UserController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:28, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\UserController::paginated
**Reason:** the region calls App\Http\Controllers\UserController::paginated, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/UserController.php` :: `App\Http\Controllers\UserController::paginated` (lines 20-51)
**Tokens:** 62

```text
ASSUMPTION: paginated() is not declared in UserController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:38, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Controllers\UserController::success
**Reason:** the region calls App\Http\Controllers\UserController::success, which this file does not declare; its contract is defined in another file
**Source:** `app/Http/Controllers/UserController.php` :: `App\Http\Controllers\UserController::success` (lines 20-51)
**Tokens:** 61

```text
ASSUMPTION: success() is not declared in UserController or in its parent App\Http\Controllers\Controller; it is declared in trait App\Traits\ApiResponse at app/Traits/ApiResponse.php:9, used by that parent; body not fetched, contract unverified
```

## flagged · named_reference

**Subject:** App\Http\Resources\User\UserResource::collection
**Reason:** the region depends on App\Http\Resources\User\UserResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/UserController.php` :: `App\Http\Resources\User\UserResource::collection` (lines 20-51)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Subject:** App\Http\Resources\Auth\AuthResource
**Reason:** the region depends on App\Http\Resources\Auth\AuthResource, whose contract is defined in another file
**Source:** `app/Http/Resources/Auth/AuthResource.php` :: `toArray` (lines 10-21)
**Tokens:** 102

```php
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->resource['token'],
            'user' => [
                'id' => $this->resource['user']->id,
                'name' => $this->resource['user']->name,
                'email' => $this->resource['user']->email,
                'role' => $this->resource['user']->role,
            ],
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

**Subject:** App\Http\Resources\Catering\PackageResource
**Reason:** the region depends on App\Http\Resources\Catering\PackageResource, whose contract is defined in another file
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

**Subject:** App\Http\Resources\Catering\SampleMenuResource
**Reason:** the region depends on App\Http\Resources\Catering\SampleMenuResource, whose contract is defined in another file
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

**Subject:** App\Http\Resources\DeliveryApp\DeliveryAppResource
**Reason:** the region depends on App\Http\Resources\DeliveryApp\DeliveryAppResource, whose contract is defined in another file
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

**Subject:** App\Http\Resources\Dish\DishCollection
**Reason:** the region depends on App\Http\Resources\Dish\DishCollection, whose contract is defined in another file
**Source:** `app/Http/Resources/Dish/DishCollection.php` :: `collects` (lines 10-10)
**Tokens:** 11

```php
    public $collects = DishResource::class;
```

## fetched · named_reference

**Subject:** App\Http\Resources\Dish\DishCollection
**Reason:** the region depends on App\Http\Resources\Dish\DishCollection, whose contract is defined in another file
**Source:** `app/Http/Resources/Dish/DishCollection.php` :: `toArray` (lines 12-23)
**Tokens:** 93

```php
    public function toArray(Request $request): array
    {
        return [
            'items' => $this->collection,
            'meta' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
            ],
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

**Subject:** App\Http\Resources\Testimonial\TestimonialResource
**Reason:** the region depends on App\Http\Resources\Testimonial\TestimonialResource, whose contract is defined in another file
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

**Subject:** App\Http\Resources\Timeline\TimelineResource
**Reason:** the region depends on App\Http\Resources\Timeline\TimelineResource, whose contract is defined in another file
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

## Dropped

Nothing was dropped.
===== END context-bundle.md =====

===== BEGIN context-diagnostics.txt =====
new file: app/Traits/ApiResponse.php — own-file context is in the diff, not fetched
inherited member: App\Http\Controllers\Auth\AuthController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\BranchController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\BranchController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\BranchController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\Branch\BranchResource::collection in app/Http/Controllers/BranchController.php
inherited member: App\Http\Controllers\CategoryController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\CategoryController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\CategoryController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\Category\CategoryResource::collection in app/Http/Controllers/CategoryController.php
inherited member: App\Http\Controllers\Catering\CateringPackageController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Catering\CateringPackageController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Catering\CateringPackageController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\Catering\PackageResource::collection in app/Http/Controllers/Catering/CateringPackageController.php
inherited member: App\Http\Controllers\Catering\QuoteRequestController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Catering\QuoteRequestController::paginated declared at app/Traits/ApiResponse.php:38 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Catering\QuoteRequestController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\Catering\QuoteRequestResource::collection in app/Http/Controllers/Catering/QuoteRequestController.php
inherited member: App\Http\Controllers\Catering\SampleMenuController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Catering\SampleMenuController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\Catering\SampleMenuController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\Catering\SampleMenuResource::collection in app/Http/Controllers/Catering/SampleMenuController.php
inherited member: App\Http\Controllers\DeliveryAppController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\DeliveryAppController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\DeliveryAppController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\DeliveryApp\DeliveryAppResource::collection in app/Http/Controllers/DeliveryAppController.php
inherited member: App\Http\Controllers\DishController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\DishController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\DishController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\MediaItemController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\MediaItemController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\MediaItemController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\PageContentController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\SettingController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\TestimonialController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\TestimonialController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\TestimonialController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\Testimonial\TestimonialResource::collection in app/Http/Controllers/TestimonialController.php
inherited member: App\Http\Controllers\TimelineController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\TimelineController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\TimelineController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\Timeline\TimelineResource::collection in app/Http/Controllers/TimelineController.php
inherited member: App\Http\Controllers\UserController::paginated declared at app/Traits/ApiResponse.php:38 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\UserController::created declared at app/Traits/ApiResponse.php:21 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\UserController::success declared at app/Traits/ApiResponse.php:9 in trait App\Traits\ApiResponse; body not fetched
inherited member: App\Http\Controllers\UserController::deleted declared at app/Traits/ApiResponse.php:28 in trait App\Traits\ApiResponse; body not fetched
unresolved named_reference: App\Http\Resources\User\UserResource::collection in app/Http/Controllers/UserController.php
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
===== END context-diagnostics.txt =====
