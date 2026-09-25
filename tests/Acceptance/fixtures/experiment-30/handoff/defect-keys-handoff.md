# Review handoff · four commits

You are being asked to act as a careful, experienced code reviewer for four commits from one
Laravel application (PHP 8.x, Laravel 12). For each commit you are given **its unified diff and
nothing else**: no repository, no file access, no later history, no tests you can run. Work from
the diff alone. Do not search the web for the project. Do not assume what later commits did.

The task is the one a senior reviewer faces at the moment a pull request lands: **is there a
correctness defect in this change, and what would you need to open in the repository to be
sure?**

## What to produce

One JSON object per commit, in the template below, pasted back as a single JSON array of four
objects. Every field is required. Write the `hedges` honestly: where you are unsure, say so and
say why, rather than rounding to YES or NO.

### Definitions

- **`defect_present`** — `YES` if the change, as written, contains a correctness defect: code
  that will behave wrongly at runtime, silently lose or corrupt data, expose the wrong thing, or
  break an existing behaviour the change did not mean to break. `NO` if you find none. There is
  no `CANNOT_TELL` here: if you cannot decide, answer with your best judgement and put the doubt
  in `hedges` and `strength`.
- **`strength`** — how certain the defect is *from the code itself*: `STRONG` (the mechanism is
  definite from what is written; any competent reviewer shown it would agree), `MODERATE` (very
  likely, but it depends on one fact outside the diff that you would have to confirm), `WEAK`
  (arguable; a reasonable reviewer might call it a style or design concern instead). For a `NO`,
  `strength` says how sure you are that there is none.
- **`defect`** — one paragraph naming the **mechanism** — what goes wrong, under what input or
  state, and where in the diff — not a category. If there are two independent defects, describe
  both and say that either counts. `-` for a `NO`.
- **`visible_in_diff`** — `FULLY` (everything needed to see the defect is in the diff), `PARTLY`
  (the diff shows the shape but a fact outside it is needed to be sure — say which), or `NO`
  (the defect can only be seen with something the diff does not contain).
- **`required_context`** — the list of things **not in the diff** that a reviewer would need to
  open to be sure of their answer, as `path/to/File.php::member` (or `path/to/File.php` for a
  whole file, or a config key, or a database fact), most important first. Empty list `[]` if the
  diff alone is sufficient. Be specific: name the file and member you would actually open, not
  "the model".
- **`inherited_member_dependence`** — `YES` or `NO`, with a one-sentence `why`: does the defect
  you named (or, for a `NO`, the question you most needed answered) turn on the behaviour or
  parameter contract of a **method the changed file calls on `$this->` but does not itself
  declare** — that is, a method it inherits from a parent class or a trait? If there is no such
  call in the changed lines, answer `NO` and say so.
- **`hedges`** — a list of every place your answer rests on something you assumed, could not
  see, or would want to verify. Empty only if you are certain.

### Template

```json
{
  "id": "T1",
  "commit": "9b8f9c6",
  "subject": "<the commit subject, copied>",
  "written": "<today's date>",
  "author": "second author, fresh session, from the diff alone",
  "defect_present": "YES | NO",
  "strength": "STRONG | MODERATE | WEAK",
  "defect": "<one paragraph naming the mechanism, or ->",
  "visible_in_diff": "FULLY | PARTLY | NO",
  "required_context": ["path/to/File.php::member", "..."],
  "inherited_member_dependence": { "answer": "YES | NO", "why": "<one sentence>" },
  "hedges": ["...", "..."]
}
```

### Rules

1. Read every hunk of every file before answering. The lang and config files count as much as
   the PHP classes.
2. Do not infer from the commit message what the code does; read the code. The message is given
   because a reviewer would see it.
3. Do not propose fixes. Do not grade style. The question is defect or no defect, and what a
   reviewer would need.
4. If a commit has more than one plausible defect, name the most consequential in `defect` and
   the rest in `hedges`.
5. Your answers will be fixed once returned; you will not be asked to revise them.

---

## T1 · commit `9b8f9c6`

**Subject:** feat: add bilingual lang files (ar/en) for validation and messages

**Size:** 19 files changed, 248 insertions(+), 141 deletions(-)

```diff
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
```

---

## T2 · commit `ee5a2e6`

**Subject:** feat(personalities): add Personality entity for the story page's "faces" section

**Size:** 21 files changed, 510 insertions(+), 4 deletions(-)

```diff
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
```

---

## T4 · commit `f3a7fcd`

**Subject:** feat: add restaurant name and hours to settings

**Size:** 2 files changed, 3 insertions(+), 1 deletion(-)

```diff
diff --git a/database/seeders/SettingSeeder.php b/database/seeders/SettingSeeder.php
index b9b23e0..85d0338 100644
--- a/database/seeders/SettingSeeder.php
+++ b/database/seeders/SettingSeeder.php
@@ -10,6 +10,8 @@ class SettingSeeder extends Seeder
     public function run(): void
     {
         $settings = [
+            ['key' => 'restaurant_name_ar', 'value' => 'أبو السيد', 'type' => 'text', 'group' => 'general', 'label_ar' => 'اسم المطعم (عربي)', 'label_en' => 'Restaurant Name (Arabic)'],
+            ['key' => 'restaurant_name_en', 'value' => 'Abou El Sid', 'type' => 'text', 'group' => 'general', 'label_ar' => 'اسم المطعم (إنجليزي)', 'label_en' => 'Restaurant Name (English)'],
             ['key' => 'whatsapp_number', 'value' => '+966551718800', 'type' => 'phone', 'group' => 'contact', 'label_ar' => 'رقم واتساب', 'label_en' => 'WhatsApp Number'],
             ['key' => 'phone_jeddah', 'value' => '0551718800', 'type' => 'phone', 'group' => 'contact', 'label_ar' => 'هاتف جدة', 'label_en' => 'Jeddah Phone'],
             ['key' => 'phone_riyadh', 'value' => '0581041912', 'type' => 'phone', 'group' => 'contact', 'label_ar' => 'هاتف الرياض', 'label_en' => 'Riyadh Phone'],
diff --git a/tests/Feature/RepositoriesTest.php b/tests/Feature/RepositoriesTest.php
index ab0f9e2..ab89f4a 100644
--- a/tests/Feature/RepositoriesTest.php
+++ b/tests/Feature/RepositoriesTest.php
@@ -114,7 +114,7 @@ public function test_setting_repository_get_all_keyed_by_key(): void
 
         $result = $repo->getAll();
 
-        $this->assertCount(17, $result);
+        $this->assertCount(19, $result);
         $this->assertTrue($result->has('whatsapp_number'));
     }
 
```

---

## T5 · commit `2996b89`

**Subject:** refactor: clean controller structure — Admin/ and Public/ only

**Size:** 29 files changed, 604 insertions(+), 665 deletions(-)

```diff
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
```

---
