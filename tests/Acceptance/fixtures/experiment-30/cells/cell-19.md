<!-- cell-19 -->
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
index 028d0a0..563551d 100644
--- a/app/Http/Controllers/Auth/AuthController.php
+++ b/app/Http/Controllers/Auth/AuthController.php
@@ -18,18 +18,18 @@ public function login(LoginRequest $request, LoginAction $action): JsonResponse
     {
         $result = $action->execute(LoginDTO::fromRequest($request));
 
-        return $this->success(new AuthResource($result), 'Login successful');
+        return $this->success(new AuthResource($result), __('messages.login_success'));
     }
 
     public function logout(Request $request, LogoutAction $action): JsonResponse
     {
         $action->execute($request->user());
 
-        return $this->success(null, 'Logout successful');
+        return $this->success(null, __('messages.logout_success'));
     }
 
     public function me(Request $request): JsonResponse
     {
-        return $this->success(new UserResource($request->user()), 'Authenticated user');
+        return $this->success(new UserResource($request->user()), __('messages.fetched'));
     }
 }
diff --git a/app/Http/Controllers/BranchController.php b/app/Http/Controllers/BranchController.php
index a9cb02f..3961a3e 100644
--- a/app/Http/Controllers/BranchController.php
+++ b/app/Http/Controllers/BranchController.php
@@ -17,27 +17,27 @@ class BranchController extends Controller
 {
     public function index(GetBranchesAction $action): JsonResponse
     {
-        return $this->success(BranchResource::collection($action->execute()), 'Branches retrieved');
+        return $this->success(BranchResource::collection($action->execute()), __('messages.fetched'));
     }
 
     public function store(StoreBranchRequest $request, CreateBranchAction $action): JsonResponse
     {
         $branch = $action->execute(CreateBranchDTO::fromRequest($request));
 
-        return $this->created(new BranchResource($branch), 'Branch created');
+        return $this->created(new BranchResource($branch), __('messages.created'));
     }
 
     public function update(UpdateBranchRequest $request, int $id, UpdateBranchAction $action): JsonResponse
     {
         $branch = $action->execute($id, UpdateBranchDTO::fromRequest($request));
 
-        return $this->success(new BranchResource($branch), 'Branch updated');
+        return $this->success(new BranchResource($branch), __('messages.updated'));
     }
 
     public function destroy(int $id, DeleteBranchAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return $this->deleted('Branch deleted');
+        return $this->deleted(__('messages.deleted'));
     }
 }
diff --git a/app/Http/Controllers/CategoryController.php b/app/Http/Controllers/CategoryController.php
index 2a886d6..bf1c2f7 100644
--- a/app/Http/Controllers/CategoryController.php
+++ b/app/Http/Controllers/CategoryController.php
@@ -15,27 +15,27 @@ public function __construct(
 
     public function index(): JsonResponse
     {
-        return $this->success(CategoryResource::collection($this->repository->getAll()), 'Categories retrieved');
+        return $this->success(CategoryResource::collection($this->repository->getAll()), __('messages.fetched'));
     }
 
     public function store(Request $request): JsonResponse
     {
         $category = $this->repository->create($request->only(['name_ar', 'name_en', 'slug', 'order']));
 
-        return $this->created(new CategoryResource($category), 'Category created');
+        return $this->created(new CategoryResource($category), __('messages.created'));
     }
 
     public function update(Request $request, int $id): JsonResponse
     {
         $category = $this->repository->update($id, $request->only(['name_ar', 'name_en', 'slug', 'order']));
 
-        return $this->success(new CategoryResource($category), 'Category updated');
+        return $this->success(new CategoryResource($category), __('messages.updated'));
     }
 
     public function destroy(int $id): JsonResponse
     {
         $this->repository->delete($id);
 
-        return $this->deleted('Category deleted');
+        return $this->deleted(__('messages.deleted'));
     }
 }
diff --git a/app/Http/Controllers/Catering/CateringPackageController.php b/app/Http/Controllers/Catering/CateringPackageController.php
index 4fdd1b8..83dfaca 100644
--- a/app/Http/Controllers/Catering/CateringPackageController.php
+++ b/app/Http/Controllers/Catering/CateringPackageController.php
@@ -18,27 +18,27 @@ class CateringPackageController extends Controller
 {
     public function index(GetPackagesAction $action): JsonResponse
     {
-        return $this->success(PackageResource::collection($action->execute()), 'Catering packages retrieved');
+        return $this->success(PackageResource::collection($action->execute()), __('messages.fetched'));
     }
 
     public function store(StorePackageRequest $request, CreatePackageAction $action): JsonResponse
     {
         $package = $action->execute(CreatePackageDTO::fromRequest($request));
 
-        return $this->created(new PackageResource($package), 'Catering package created');
+        return $this->created(new PackageResource($package), __('messages.created'));
     }
 
     public function update(UpdatePackageRequest $request, int $id, UpdatePackageAction $action): JsonResponse
     {
         $package = $action->execute($id, UpdatePackageDTO::fromRequest($request));
 
-        return $this->success(new PackageResource($package), 'Catering package updated');
+        return $this->success(new PackageResource($package), __('messages.updated'));
     }
 
     public function destroy(int $id, DeletePackageAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return $this->deleted('Catering package deleted');
+        return $this->deleted(__('messages.deleted'));
     }
 }
diff --git a/app/Http/Controllers/Catering/QuoteRequestController.php b/app/Http/Controllers/Catering/QuoteRequestController.php
index 63ea6c1..e9aa8e2 100644
--- a/app/Http/Controllers/Catering/QuoteRequestController.php
+++ b/app/Http/Controllers/Catering/QuoteRequestController.php
@@ -20,7 +20,7 @@ public function store(StoreQuoteRequestRequest $request, SubmitQuoteRequestActio
     {
         $quoteRequest = $action->execute(SubmitQuoteRequestDTO::fromRequest($request));
 
-        return $this->created(new QuoteRequestResource($quoteRequest), 'Quote request submitted');
+        return $this->created(new QuoteRequestResource($quoteRequest), __('messages.quote_submitted'));
     }
 
     public function index(Request $request, QuoteRequestRepository $repository): JsonResponse
@@ -33,18 +33,18 @@ public function index(Request $request, QuoteRequestRepository $repository): Jso
 
         $result = $repository->getAll($filters);
 
-        return $this->paginated(QuoteRequestResource::collection($result), $result, 'Quote requests retrieved');
+        return $this->paginated(QuoteRequestResource::collection($result), $result, __('messages.fetched'));
     }
 
     public function show(int $id, QuoteRequestRepository $repository): JsonResponse
     {
-        return $this->success(new QuoteRequestResource($repository->getById($id)), 'Quote request retrieved');
+        return $this->success(new QuoteRequestResource($repository->getById($id)), __('messages.fetched'));
     }
 
     public function updateStatus(UpdateQuoteRequestStatusRequest $request, int $id, UpdateQuoteRequestStatusAction $action): JsonResponse
     {
         $quoteRequest = $action->execute($id, UpdateQuoteRequestStatusDTO::fromRequest($request));
 
-        return $this->success(new QuoteRequestResource($quoteRequest), 'Quote request status updated');
+        return $this->success(new QuoteRequestResource($quoteRequest), __('messages.updated'));
     }
 }
diff --git a/app/Http/Controllers/Catering/SampleMenuController.php b/app/Http/Controllers/Catering/SampleMenuController.php
index 4919e73..f5b1fb0 100644
--- a/app/Http/Controllers/Catering/SampleMenuController.php
+++ b/app/Http/Controllers/Catering/SampleMenuController.php
@@ -16,27 +16,27 @@ class SampleMenuController extends Controller
 {
     public function index(GetSampleMenusAction $action): JsonResponse
     {
-        return $this->success(SampleMenuResource::collection($action->execute()), 'Sample menus retrieved');
+        return $this->success(SampleMenuResource::collection($action->execute()), __('messages.fetched'));
     }
 
     public function store(StoreSampleMenuRequest $request, CreateSampleMenuAction $action): JsonResponse
     {
         $menu = $action->execute(CreateSampleMenuDTO::fromRequest($request));
 
-        return $this->created(new SampleMenuResource($menu), 'Sample menu created');
+        return $this->created(new SampleMenuResource($menu), __('messages.created'));
     }
 
     public function update(StoreSampleMenuRequest $request, int $id, UpdateSampleMenuAction $action): JsonResponse
     {
         $menu = $action->execute($id, CreateSampleMenuDTO::fromRequest($request));
 
-        return $this->success(new SampleMenuResource($menu), 'Sample menu updated');
+        return $this->success(new SampleMenuResource($menu), __('messages.updated'));
     }
 
     public function destroy(int $id, DeleteSampleMenuAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return $this->deleted('Sample menu deleted');
+        return $this->deleted(__('messages.deleted'));
     }
 }
diff --git a/app/Http/Controllers/DeliveryAppController.php b/app/Http/Controllers/DeliveryAppController.php
index 331269c..c2fca72 100644
--- a/app/Http/Controllers/DeliveryAppController.php
+++ b/app/Http/Controllers/DeliveryAppController.php
@@ -17,27 +17,27 @@ public function index(GetDeliveryAppsAction $action): JsonResponse
     {
         $activeOnly = ! auth('sanctum')->check();
 
-        return $this->success(DeliveryAppResource::collection($action->execute($activeOnly)), 'Delivery apps retrieved');
+        return $this->success(DeliveryAppResource::collection($action->execute($activeOnly)), __('messages.fetched'));
     }
 
     public function store(StoreDeliveryAppRequest $request, CreateDeliveryAppAction $action): JsonResponse
     {
         $deliveryApp = $action->execute(CreateDeliveryAppDTO::fromRequest($request));
 
-        return $this->created(new DeliveryAppResource($deliveryApp), 'Delivery app created');
+        return $this->created(new DeliveryAppResource($deliveryApp), __('messages.created'));
     }
 
     public function update(StoreDeliveryAppRequest $request, int $id, UpdateDeliveryAppAction $action): JsonResponse
     {
         $deliveryApp = $action->execute($id, CreateDeliveryAppDTO::fromRequest($request));
 
-        return $this->success(new DeliveryAppResource($deliveryApp), 'Delivery app updated');
+        return $this->success(new DeliveryAppResource($deliveryApp), __('messages.updated'));
     }
 
     public function destroy(int $id, DeleteDeliveryAppAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return $this->deleted('Delivery app deleted');
+        return $this->deleted(__('messages.deleted'));
     }
 }
diff --git a/app/Http/Controllers/DishController.php b/app/Http/Controllers/DishController.php
index 3a2c214..c497398 100644
--- a/app/Http/Controllers/DishController.php
+++ b/app/Http/Controllers/DishController.php
@@ -29,32 +29,32 @@ public function index(Request $request, GetDishesAction $action): JsonResponse
 
         $result = $action->execute($filters);
 
-        return $this->success(new DishCollection($result), 'Dishes retrieved');
+        return $this->success(new DishCollection($result), __('messages.fetched'));
     }
 
     public function show(int $id, GetDishAction $action): JsonResponse
     {
-        return $this->success(new DishResource($action->execute($id)), 'Dish retrieved');
+        return $this->success(new DishResource($action->execute($id)), __('messages.fetched'));
     }
 
     public function store(StoreDishRequest $request, CreateDishAction $action): JsonResponse
     {
         $dish = $action->execute(CreateDishDTO::fromRequest($request));
 
-        return $this->created(new DishResource($dish), 'Dish created');
+        return $this->created(new DishResource($dish), __('messages.created'));
     }
 
     public function update(UpdateDishRequest $request, int $id, UpdateDishAction $action): JsonResponse
     {
         $dish = $action->execute($id, UpdateDishDTO::fromRequest($request));
 
-        return $this->success(new DishResource($dish), 'Dish updated');
+        return $this->success(new DishResource($dish), __('messages.updated'));
     }
 
     public function destroy(int $id, DeleteDishAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return $this->deleted('Dish deleted');
+        return $this->deleted(__('messages.deleted'));
     }
 }
diff --git a/app/Http/Controllers/MediaItemController.php b/app/Http/Controllers/MediaItemController.php
index 6707b12..cf6933f 100644
--- a/app/Http/Controllers/MediaItemController.php
+++ b/app/Http/Controllers/MediaItemController.php
@@ -25,32 +25,32 @@ public function index(Request $request, GetMediaItemsAction $action): JsonRespon
             fn ($section) => $section->map(fn ($item) => new MediaItemResource($item))
         );
 
-        return $this->success($resourced, 'Media items retrieved');
+        return $this->success($resourced, __('messages.fetched'));
     }
 
     public function store(UploadMediaRequest $request, UploadMediaAction $action): JsonResponse
     {
         $mediaItem = $action->execute(UploadMediaDTO::fromRequest($request));
 
-        return $this->created(new MediaItemResource($mediaItem), 'Media item uploaded');
+        return $this->created(new MediaItemResource($mediaItem), __('messages.uploaded'));
     }
 
     public function show(int $id, MediaItemRepository $repository): JsonResponse
     {
-        return $this->success(new MediaItemResource($repository->getById($id)), 'Media item retrieved');
+        return $this->success(new MediaItemResource($repository->getById($id)), __('messages.fetched'));
     }
 
     public function update(UploadMediaRequest $request, int $id, UploadMediaAction $action): JsonResponse
     {
         $mediaItem = $action->execute(UploadMediaDTO::fromRequest($request));
 
-        return $this->success(new MediaItemResource($mediaItem), 'Media item updated');
+        return $this->success(new MediaItemResource($mediaItem), __('messages.updated'));
     }
 
     public function destroy(int $id, DeleteMediaAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return $this->deleted('Media item deleted');
+        return $this->deleted(__('messages.deleted'));
     }
 }
diff --git a/app/Http/Controllers/PageContentController.php b/app/Http/Controllers/PageContentController.php
index ff4f0ad..d0fdfc3 100644
--- a/app/Http/Controllers/PageContentController.php
+++ b/app/Http/Controllers/PageContentController.php
@@ -27,25 +27,25 @@ public function index(Request $request, GetPageContentAction $action): JsonRespo
             fn ($section) => $section->map(fn ($item) => new PageContentResource($item))
         );
 
-        return $this->success($resourced, 'Page contents retrieved');
+        return $this->success($resourced, __('messages.fetched'));
     }
 
     public function show(int $id, PageContentRepository $repository): JsonResponse
     {
-        return $this->success(new PageContentResource($repository->getById($id)), 'Page content retrieved');
+        return $this->success(new PageContentResource($repository->getById($id)), __('messages.fetched'));
     }
 
     public function update(UpdatePageContentRequest $request, int $id, UpdatePageContentAction $action): JsonResponse
     {
         $pageContent = $action->execute($id, UpdatePageContentDTO::fromRequest($request));
 
-        return $this->success(new PageContentResource($pageContent), 'Page content updated');
+        return $this->success(new PageContentResource($pageContent), __('messages.updated'));
     }
 
     public function bulkUpdate(BulkUpdatePageContentRequest $request, BulkUpdatePageContentAction $action): JsonResponse
     {
         $action->execute(BulkUpdatePageContentDTO::fromRequest($request));
 
-        return $this->success(null, 'Page contents updated');
+        return $this->success(null, __('messages.updated'));
     }
 }
diff --git a/app/Http/Controllers/SettingController.php b/app/Http/Controllers/SettingController.php
index 25a4fd8..76677c5 100644
--- a/app/Http/Controllers/SettingController.php
+++ b/app/Http/Controllers/SettingController.php
@@ -18,13 +18,13 @@ public function index(Request $request, GetSettingsAction $action): JsonResponse
             $request->filled('group') ? $request->string('group')->toString() : null,
         );
 
-        return $this->success($result->map(fn ($setting) => new SettingResource($setting)), 'Settings retrieved');
+        return $this->success($result->map(fn ($setting) => new SettingResource($setting)), __('messages.fetched'));
     }
 
     public function bulkUpdate(UpdateSettingRequest $request, UpdateSettingsAction $action): JsonResponse
     {
         $action->execute(UpdateSettingDTO::fromRequest($request));
 
-        return $this->success(null, 'Settings updated');
+        return $this->success(null, __('messages.updated'));
     }
 }
diff --git a/app/Http/Controllers/TestimonialController.php b/app/Http/Controllers/TestimonialController.php
index 0a99885..9eed09a 100644
--- a/app/Http/Controllers/TestimonialController.php
+++ b/app/Http/Controllers/TestimonialController.php
@@ -17,27 +17,27 @@ public function index(GetTestimonialsAction $action): JsonResponse
     {
         $activeOnly = ! auth('sanctum')->check();
 
-        return $this->success(TestimonialResource::collection($action->execute($activeOnly)), 'Testimonials retrieved');
+        return $this->success(TestimonialResource::collection($action->execute($activeOnly)), __('messages.fetched'));
     }
 
     public function store(StoreTestimonialRequest $request, CreateTestimonialAction $action): JsonResponse
     {
         $testimonial = $action->execute(CreateTestimonialDTO::fromRequest($request));
 
-        return $this->created(new TestimonialResource($testimonial), 'Testimonial created');
+        return $this->created(new TestimonialResource($testimonial), __('messages.created'));
     }
 
     public function update(StoreTestimonialRequest $request, int $id, UpdateTestimonialAction $action): JsonResponse
     {
         $testimonial = $action->execute($id, CreateTestimonialDTO::fromRequest($request));
 
-        return $this->success(new TestimonialResource($testimonial), 'Testimonial updated');
+        return $this->success(new TestimonialResource($testimonial), __('messages.updated'));
     }
 
     public function destroy(int $id, DeleteTestimonialAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return $this->deleted('Testimonial deleted');
+        return $this->deleted(__('messages.deleted'));
     }
 }
diff --git a/app/Http/Controllers/TimelineController.php b/app/Http/Controllers/TimelineController.php
index a686e45..69cede4 100644
--- a/app/Http/Controllers/TimelineController.php
+++ b/app/Http/Controllers/TimelineController.php
@@ -15,27 +15,27 @@ class TimelineController extends Controller
 {
     public function index(GetTimelineAction $action): JsonResponse
     {
-        return $this->success(TimelineResource::collection($action->execute()), 'Timeline retrieved');
+        return $this->success(TimelineResource::collection($action->execute()), __('messages.fetched'));
     }
 
     public function store(StoreTimelineRequest $request, CreateTimelineAction $action): JsonResponse
     {
         $timeline = $action->execute(CreateTimelineDTO::fromRequest($request));
 
-        return $this->created(new TimelineResource($timeline), 'Timeline entry created');
+        return $this->created(new TimelineResource($timeline), __('messages.created'));
     }
 
     public function update(StoreTimelineRequest $request, int $id, UpdateTimelineAction $action): JsonResponse
     {
         $timeline = $action->execute($id, CreateTimelineDTO::fromRequest($request));
 
-        return $this->success(new TimelineResource($timeline), 'Timeline entry updated');
+        return $this->success(new TimelineResource($timeline), __('messages.updated'));
     }
 
     public function destroy(int $id, DeleteTimelineAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return $this->deleted('Timeline entry deleted');
+        return $this->deleted(__('messages.deleted'));
     }
 }
diff --git a/app/Http/Controllers/UserController.php b/app/Http/Controllers/UserController.php
index 6261341..bd04933 100644
--- a/app/Http/Controllers/UserController.php
+++ b/app/Http/Controllers/UserController.php
@@ -20,32 +20,32 @@ public function index(GetUsersAction $action): JsonResponse
     {
         $result = $action->execute();
 
-        return $this->paginated(UserResource::collection($result), $result, 'Users retrieved');
+        return $this->paginated(UserResource::collection($result), $result, __('messages.fetched'));
     }
 
     public function store(StoreUserRequest $request, CreateUserAction $action): JsonResponse
     {
         $user = $action->execute(CreateUserDTO::fromRequest($request));
 
-        return $this->created(new UserResource($user), 'User created');
+        return $this->created(new UserResource($user), __('messages.created'));
     }
 
     public function show(int $id, UserRepository $repository): JsonResponse
     {
-        return $this->success(new UserResource($repository->getById($id)), 'User retrieved');
+        return $this->success(new UserResource($repository->getById($id)), __('messages.fetched'));
     }
 
     public function update(UpdateUserRequest $request, int $id, UpdateUserAction $action): JsonResponse
     {
         $user = $action->execute($id, UpdateUserDTO::fromRequest($request));
 
-        return $this->success(new UserResource($user), 'User updated');
+        return $this->success(new UserResource($user), __('messages.updated'));
     }
 
     public function destroy(int $id, DeleteUserAction $action): JsonResponse
     {
         $action->execute($id);
 
-        return $this->deleted('User deleted');
+        return $this->deleted(__('messages.deleted'));
     }
 }
diff --git a/config/app.php b/config/app.php
index 423eed5..e045a58 100644
--- a/config/app.php
+++ b/config/app.php
@@ -78,11 +78,11 @@
     |
     */
 
-    'locale' => env('APP_LOCALE', 'en'),
+    'locale' => env('APP_LOCALE', 'ar'),
 
     'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
 
-    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),
+    'faker_locale' => env('APP_FAKER_LOCALE', 'ar_SA'),
 
     /*
     |--------------------------------------------------------------------------
diff --git a/lang/ar/messages.php b/lang/ar/messages.php
new file mode 100644
index 0000000..cbdaefa
--- /dev/null
+++ b/lang/ar/messages.php
@@ -0,0 +1,17 @@
+<?php
+
+return [
+    'created'           => 'تم الإنشاء بنجاح.',
+    'updated'           => 'تم التحديث بنجاح.',
+    'deleted'           => 'تم الحذف بنجاح.',
+    'fetched'           => 'تم جلب البيانات بنجاح.',
+    'login_success'     => 'تم تسجيل الدخول بنجاح.',
+    'logout_success'    => 'تم تسجيل الخروج بنجاح.',
+    'unauthorized'      => 'غير مصرح لك بهذا الإجراء.',
+    'invalid_api_key'   => 'مفتاح API غير صحيح.',
+    'not_found'         => 'العنصر غير موجود.',
+    'validation_failed' => 'فشل التحقق من البيانات.',
+    'something_wrong'   => 'حدث خطأ ما، يرجى المحاولة لاحقاً.',
+    'uploaded'          => 'تم رفع الملف بنجاح.',
+    'quote_submitted'   => 'تم استلام طلبكم، سنتواصل معكم قريباً.',
+];
diff --git a/lang/ar/validation.php b/lang/ar/validation.php
index bc54b7f..0cb1356 100644
--- a/lang/ar/validation.php
+++ b/lang/ar/validation.php
@@ -1,91 +1,83 @@
 <?php
 
 return [
-    'required' => 'حقل :attribute مطلوب.',
-    'email' => 'حقل :attribute يجب أن يكون بريدًا إلكترونيًا صالحًا.',
-    'string' => 'حقل :attribute يجب أن يكون نصًا.',
-    'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
-    'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
-    'boolean' => 'حقل :attribute يجب أن يكون صحيحًا أو خاطئًا.',
-    'array' => 'حقل :attribute يجب أن يكون مصفوفة.',
-    'url' => 'حقل :attribute يجب أن يكون رابطًا صالحًا.',
-    'date' => 'حقل :attribute يجب أن يكون تاريخًا صالحًا.',
-    'confirmed' => 'تأكيد حقل :attribute غير مطابق.',
-    'file' => 'حقل :attribute يجب أن يكون ملفًا.',
-
-    'min' => [
-        'numeric' => 'يجب ألا تقل قيمة :attribute عن :min.',
-        'file' => 'يجب ألا يقل حجم :attribute عن :min كيلوبايت.',
-        'string' => 'يجب ألا يقل عدد أحرف :attribute عن :min.',
-        'array' => 'يجب أن يحتوي :attribute على :min عناصر على الأقل.',
+    'required'  => 'حقل :attribute مطلوب.',
+    'string'    => 'حقل :attribute يجب أن يكون نصاً.',
+    'email'     => 'حقل :attribute يجب أن يكون بريداً إلكترونياً صحيحاً.',
+    'min'       => [
+        'string'  => 'حقل :attribute يجب أن لا يقل عن :min حروف.',
+        'numeric' => 'حقل :attribute يجب أن لا يقل عن :min.',
+        'file'    => 'حجم :attribute يجب أن لا يقل عن :min كيلوبايت.',
     ],
-
-    'max' => [
-        'numeric' => 'يجب ألا تزيد قيمة :attribute عن :max.',
-        'file' => 'يجب ألا يزيد حجم :attribute عن :max كيلوبايت.',
-        'string' => 'يجب ألا يزيد عدد أحرف :attribute عن :max.',
-        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عناصر.',
+    'max'       => [
+        'string'  => 'حقل :attribute يجب أن لا يتجاوز :max حروف.',
+        'numeric' => 'حقل :attribute يجب أن لا يتجاوز :max.',
+        'file'    => 'حجم :attribute يجب أن لا يتجاوز :max كيلوبايت.',
+    ],
+    'unique'    => 'قيمة :attribute مستخدمة مسبقاً.',
+    'exists'    => 'قيمة :attribute غير صحيحة.',
+    'boolean'   => 'حقل :attribute يجب أن يكون صح أو خطأ.',
+    'integer'   => 'حقل :attribute يجب أن يكون رقماً صحيحاً.',
+    'numeric'   => 'حقل :attribute يجب أن يكون رقماً.',
+    'in'        => 'قيمة :attribute غير مقبولة.',
+    'url'       => 'حقل :attribute يجب أن يكون رابطاً صحيحاً.',
+    'date'      => 'حقل :attribute يجب أن يكون تاريخاً صحيحاً.',
+    'after'     => 'حقل :attribute يجب أن يكون تاريخاً بعد :date.',
+    'confirmed' => 'حقل :attribute غير متطابق.',
+    'mimes'     => 'حقل :attribute يجب أن يكون ملفاً من نوع: :values.',
+    'file'      => 'حقل :attribute يجب أن يكون ملفاً.',
+    'nullable'  => '',
+    'array'     => 'حقل :attribute يجب أن يكون قائمة.',
+    'between'   => [
+        'numeric' => 'حقل :attribute يجب أن يكون بين :min و :max.',
+        'file'    => 'حجم :attribute يجب أن يكون بين :min و :max كيلوبايت.',
+        'string'  => 'حقل :attribute يجب أن يكون بين :min و :max حرف.',
     ],
-
-    'exists' => 'القيمة المحددة لحقل :attribute غير موجودة.',
-    'unique' => 'قيمة حقل :attribute مُستخدمة من قبل.',
-    'in' => 'القيمة المحددة لحقل :attribute غير صالحة.',
-    'mimes' => 'يجب أن يكون :attribute ملفًا من نوع: :values.',
-    'after' => 'يجب أن يكون :attribute تاريخًا بعد :date.',
-
     'attributes' => [
-        'email' => 'البريد الإلكتروني',
-        'password' => 'كلمة المرور',
-        'name' => 'الاسم',
-        'name_ar' => 'الاسم بالعربي',
-        'name_en' => 'الاسم بالإنجليزي',
-        'description_ar' => 'الوصف بالعربي',
-        'description_en' => 'الوصف بالإنجليزي',
-        'value_ar' => 'القيمة بالعربي',
-        'value_en' => 'القيمة بالإنجليزي',
-        'quote_ar' => 'الاقتباس بالعربي',
-        'quote_en' => 'الاقتباس بالإنجليزي',
-        'title_ar' => 'العنوان بالعربي',
-        'title_en' => 'العنوان بالإنجليزي',
-        'location_ar' => 'الموقع بالعربي',
-        'location_en' => 'الموقع بالإنجليزي',
-        'label_ar' => 'التسمية بالعربي',
-        'label_en' => 'التسمية بالإنجليزي',
-        'alt_ar' => 'النص البديل بالعربي',
-        'alt_en' => 'النص البديل بالإنجليزي',
-        'feature_ar' => 'الميزة بالعربي',
-        'feature_en' => 'الميزة بالإنجليزي',
-        'price' => 'السعر',
-        'price_starting_from' => 'السعر ابتداءً من',
-        'category_id' => 'التصنيف',
-        'image' => 'الصورة',
-        'logo' => 'الشعار',
-        'order' => 'الترتيب',
-        'city' => 'المدينة',
-        'phone' => 'رقم الهاتف',
-        'opening_time' => 'وقت الفتح',
-        'closing_time' => 'وقت الإغلاق',
-        'google_maps_url' => 'رابط خرائط جوجل',
-        'order_url' => 'رابط الطلب',
-        'status' => 'الحالة',
-        'role' => 'الصلاحية',
-        'event_date' => 'تاريخ المناسبة',
-        'guests_count' => 'عدد الضيوف',
-        'branch' => 'الفرع',
-        'event_type' => 'نوع المناسبة',
-        'budget_range' => 'نطاق الميزانية',
-        'notes' => 'ملاحظات',
-        'is_active' => 'الحالة',
-        'is_featured' => 'مميز',
-        'is_signature' => 'طبق مميز',
-        'settings' => 'الإعدادات',
-        'items' => 'العناصر',
-        'features' => 'الميزات',
-        'dishes' => 'الأطباق',
-        'page' => 'الصفحة',
-        'section' => 'القسم',
-        'key' => 'المفتاح',
-        'slug' => 'المعرّف',
-        'year' => 'السنة',
+        'name'              => 'الاسم',
+        'name_ar'           => 'الاسم بالعربية',
+        'name_en'           => 'الاسم بالإنجليزية',
+        'email'             => 'البريد الإلكتروني',
+        'password'          => 'كلمة المرور',
+        'phone'             => 'رقم الجوال',
+        'price'             => 'السعر',
+        'description_ar'    => 'الوصف بالعربية',
+        'description_en'    => 'الوصف بالإنجليزية',
+        'category_id'       => 'التصنيف',
+        'image'             => 'الصورة',
+        'event_date'        => 'تاريخ المناسبة',
+        'guests_count'      => 'عدد الضيوف',
+        'branch'            => 'الفرع',
+        'event_type'        => 'نوع المناسبة',
+        'budget_range'      => 'الميزانية التقريبية',
+        'notes'             => 'ملاحظات',
+        'value_ar'          => 'القيمة بالعربية',
+        'value_en'          => 'القيمة بالإنجليزية',
+        'title_ar'          => 'العنوان بالعربية',
+        'title_en'          => 'العنوان بالإنجليزية',
+        'description'       => 'الوصف',
+        'location_ar'       => 'الموقع بالعربية',
+        'location_en'       => 'الموقع بالإنجليزية',
+        'opening_time'      => 'وقت الفتح',
+        'closing_time'      => 'وقت الإغلاق',
+        'google_maps_url'   => 'رابط الخريطة',
+        'order_url'         => 'رابط الطلب',
+        'quote_ar'          => 'الرأي بالعربية',
+        'quote_en'          => 'الرأي بالإنجليزية',
+        'year'              => 'السنة',
+        'order'             => 'الترتيب',
+        'role'              => 'الدور',
+        'status'            => 'الحالة',
+        'slug'              => 'المعرّف',
+        'tag_en'            => 'التصنيف',
+        'city'              => 'المدينة',
+        'alt_ar'            => 'النص البديل بالعربية',
+        'alt_en'            => 'النص البديل بالإنجليزية',
+        'page'              => 'الصفحة',
+        'section'           => 'القسم',
+        'key'               => 'المفتاح',
+        'features'          => 'المميزات',
+        'dishes'            => 'الأطباق',
+        'items'             => 'العناصر',
     ],
 ];
diff --git a/lang/en/messages.php b/lang/en/messages.php
new file mode 100644
index 0000000..bdd55f2
--- /dev/null
+++ b/lang/en/messages.php
@@ -0,0 +1,17 @@
+<?php
+
+return [
+    'created'           => 'Created successfully.',
+    'updated'           => 'Updated successfully.',
+    'deleted'           => 'Deleted successfully.',
+    'fetched'           => 'Data fetched successfully.',
+    'login_success'     => 'Login successful.',
+    'logout_success'    => 'Logged out successfully.',
+    'unauthorized'      => 'Unauthorized.',
+    'invalid_api_key'   => 'Invalid API key.',
+    'not_found'         => 'Resource not found.',
+    'validation_failed' => 'Validation failed.',
+    'something_wrong'   => 'Something went wrong. Please try again.',
+    'uploaded'          => 'File uploaded successfully.',
+    'quote_submitted'   => 'Your request has been received. We will contact you soon.',
+];
diff --git a/lang/en/validation.php b/lang/en/validation.php
new file mode 100644
index 0000000..01acf9e
--- /dev/null
+++ b/lang/en/validation.php
@@ -0,0 +1,81 @@
+<?php
+
+return [
+    'required'  => 'The :attribute field is required.',
+    'string'    => 'The :attribute field must be a string.',
+    'email'     => 'The :attribute field must be a valid email address.',
+    'min'       => [
+        'string'  => 'The :attribute field must be at least :min characters.',
+        'numeric' => 'The :attribute field must be at least :min.',
+        'file'    => 'The :attribute file must be at least :min kilobytes.',
+    ],
+    'max'       => [
+        'string'  => 'The :attribute field must not exceed :max characters.',
+        'numeric' => 'The :attribute field must not exceed :max.',
+        'file'    => 'The :attribute file must not exceed :max kilobytes.',
+    ],
+    'unique'    => 'The :attribute has already been taken.',
+    'exists'    => 'The selected :attribute is invalid.',
+    'boolean'   => 'The :attribute field must be true or false.',
+    'integer'   => 'The :attribute field must be an integer.',
+    'numeric'   => 'The :attribute field must be a number.',
+    'in'        => 'The selected :attribute is invalid.',
+    'url'       => 'The :attribute field must be a valid URL.',
+    'date'      => 'The :attribute field must be a valid date.',
+    'after'     => 'The :attribute field must be a date after :date.',
+    'confirmed' => 'The :attribute confirmation does not match.',
+    'mimes'     => 'The :attribute must be a file of type: :values.',
+    'file'      => 'The :attribute must be a file.',
+    'array'     => 'The :attribute must be an array.',
+    'between'   => [
+        'numeric' => 'The :attribute must be between :min and :max.',
+        'file'    => 'The :attribute must be between :min and :max kilobytes.',
+        'string'  => 'The :attribute must be between :min and :max characters.',
+    ],
+    'attributes' => [
+        'name'              => 'name',
+        'name_ar'           => 'Arabic name',
+        'name_en'           => 'English name',
+        'email'             => 'email',
+        'password'          => 'password',
+        'phone'             => 'phone number',
+        'price'             => 'price',
+        'description_ar'    => 'Arabic description',
+        'description_en'    => 'English description',
+        'category_id'       => 'category',
+        'image'             => 'image',
+        'event_date'        => 'event date',
+        'guests_count'      => 'number of guests',
+        'branch'            => 'branch',
+        'event_type'        => 'event type',
+        'budget_range'      => 'budget range',
+        'notes'             => 'notes',
+        'value_ar'          => 'Arabic value',
+        'value_en'          => 'English value',
+        'title_ar'          => 'Arabic title',
+        'title_en'          => 'English title',
+        'location_ar'       => 'Arabic location',
+        'location_en'       => 'English location',
+        'opening_time'      => 'opening time',
+        'closing_time'      => 'closing time',
+        'google_maps_url'   => 'Google Maps URL',
+        'order_url'         => 'order URL',
+        'quote_ar'          => 'Arabic quote',
+        'quote_en'          => 'English quote',
+        'year'              => 'year',
+        'order'             => 'order',
+        'role'              => 'role',
+        'status'            => 'status',
+        'slug'              => 'slug',
+        'tag_en'            => 'tag',
+        'city'              => 'city',
+        'alt_ar'            => 'Arabic alt text',
+        'alt_en'            => 'English alt text',
+        'page'              => 'page',
+        'section'           => 'section',
+        'key'               => 'key',
+        'features'          => 'features',
+        'dishes'            => 'dishes',
+        'items'             => 'items',
+    ],
+];
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
new file: lang/ar/messages.php — own-file context is in the diff, not fetched
new file: lang/en/messages.php — own-file context is in the diff, not fetched
new file: lang/en/validation.php — own-file context is in the diff, not fetched
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
===== END context-diagnostics.txt =====
