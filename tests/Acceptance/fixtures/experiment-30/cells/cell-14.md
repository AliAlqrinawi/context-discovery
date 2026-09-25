<!-- cell-14 -->
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
diff --git a/app/Http/Controllers/Admin/ProfileController.php b/app/Http/Controllers/Admin/ProfileController.php
new file mode 100644
index 0000000..8b39355
--- /dev/null
+++ b/app/Http/Controllers/Admin/ProfileController.php
@@ -0,0 +1,55 @@
+<?php
+
+namespace App\Http\Controllers\Admin;
+
+use App\Http\Controllers\Controller;
+use App\Http\Requests\Profile\UpdatePasswordRequest;
+use App\Http\Requests\Profile\UpdateProfileRequest;
+use App\Http\Resources\User\UserResource;
+use Illuminate\Http\JsonResponse;
+use Illuminate\Http\Request;
+use Illuminate\Support\Facades\Hash;
+
+class ProfileController extends Controller
+{
+    public function show(Request $request): JsonResponse
+    {
+        return $this->success(
+            new UserResource($request->user()),
+            __('messages.fetched')
+        );
+    }
+
+    public function update(UpdateProfileRequest $request): JsonResponse
+    {
+        $user = $request->user();
+        $user->update([
+            'name' => $request->name,
+            'email' => $request->email,
+        ]);
+
+        return $this->success(
+            new UserResource($user->fresh()),
+            __('messages.profile_updated')
+        );
+    }
+
+    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
+    {
+        $user = $request->user();
+
+        if (! Hash::check($request->current_password, $user->password)) {
+            return $this->error(
+                __('messages.invalid_current_password'),
+                422,
+                ['current_password' => [__('messages.invalid_current_password')]]
+            );
+        }
+
+        $user->update([
+            'password' => Hash::make($request->password),
+        ]);
+
+        return $this->success(null, __('messages.password_updated'));
+    }
+}
diff --git a/app/Http/Requests/Profile/UpdatePasswordRequest.php b/app/Http/Requests/Profile/UpdatePasswordRequest.php
new file mode 100644
index 0000000..99a364f
--- /dev/null
+++ b/app/Http/Requests/Profile/UpdatePasswordRequest.php
@@ -0,0 +1,22 @@
+<?php
+
+namespace App\Http\Requests\Profile;
+
+use App\Http\Requests\BaseFormRequest;
+
+class UpdatePasswordRequest extends BaseFormRequest
+{
+    public function authorize(): bool
+    {
+        return true;
+    }
+
+    public function rules(): array
+    {
+        return [
+            'current_password' => ['required', 'string'],
+            'password' => ['required', 'string', 'min:8', 'confirmed'],
+            'password_confirmation' => ['required', 'string'],
+        ];
+    }
+}
diff --git a/app/Http/Requests/Profile/UpdateProfileRequest.php b/app/Http/Requests/Profile/UpdateProfileRequest.php
new file mode 100644
index 0000000..ada08fd
--- /dev/null
+++ b/app/Http/Requests/Profile/UpdateProfileRequest.php
@@ -0,0 +1,22 @@
+<?php
+
+namespace App\Http\Requests\Profile;
+
+use App\Http\Requests\BaseFormRequest;
+use Illuminate\Validation\Rule;
+
+class UpdateProfileRequest extends BaseFormRequest
+{
+    public function authorize(): bool
+    {
+        return true;
+    }
+
+    public function rules(): array
+    {
+        return [
+            'name' => ['required', 'string', 'max:200'],
+            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->user()->id)],
+        ];
+    }
+}
diff --git a/lang/ar/messages.php b/lang/ar/messages.php
index cbdaefa..7f3a08a 100644
--- a/lang/ar/messages.php
+++ b/lang/ar/messages.php
@@ -14,4 +14,7 @@
     'something_wrong'   => 'حدث خطأ ما، يرجى المحاولة لاحقاً.',
     'uploaded'          => 'تم رفع الملف بنجاح.',
     'quote_submitted'   => 'تم استلام طلبكم، سنتواصل معكم قريباً.',
+    'invalid_current_password' => 'كلمة المرور الحالية غير صحيحة.',
+    'password_updated'         => 'تم تحديث كلمة المرور بنجاح.',
+    'profile_updated'          => 'تم تحديث الملف الشخصي بنجاح.',
 ];
diff --git a/lang/en/messages.php b/lang/en/messages.php
index bdd55f2..903d8fa 100644
--- a/lang/en/messages.php
+++ b/lang/en/messages.php
@@ -14,4 +14,7 @@
     'something_wrong'   => 'Something went wrong. Please try again.',
     'uploaded'          => 'File uploaded successfully.',
     'quote_submitted'   => 'Your request has been received. We will contact you soon.',
+    'invalid_current_password' => 'The current password is incorrect.',
+    'password_updated'         => 'Password updated successfully.',
+    'profile_updated'          => 'Profile updated successfully.',
 ];
diff --git a/routes/admin.php b/routes/admin.php
index 728f0e8..ba67cc1 100644
--- a/routes/admin.php
+++ b/routes/admin.php
@@ -10,6 +10,7 @@
 use App\Http\Controllers\Admin\DishController;
 use App\Http\Controllers\Admin\MediaItemController;
 use App\Http\Controllers\Admin\PageContentController;
+use App\Http\Controllers\Admin\ProfileController;
 use App\Http\Controllers\Admin\SettingController;
 use App\Http\Controllers\Admin\TestimonialController;
 use App\Http\Controllers\Admin\TimelineController;
@@ -31,6 +32,13 @@
             Route::get('/me',      'me');
         });
 
+        // ── Profile ───────────────────────────────────────
+        Route::prefix('profile')->controller(ProfileController::class)->group(function () {
+            Route::get('/', 'show');
+            Route::put('/', 'update');
+            Route::put('/password', 'updatePassword');
+        });
+
         // ── Page Contents ─────────────────────────────────
         Route::prefix('page-contents')->controller(PageContentController::class)->group(function () {
             Route::get('/',       'index');
diff --git a/tests/Feature/ProfileTest.php b/tests/Feature/ProfileTest.php
new file mode 100644
index 0000000..16a2470
--- /dev/null
+++ b/tests/Feature/ProfileTest.php
@@ -0,0 +1,130 @@
+<?php
+
+namespace Tests\Feature;
+
+use App\Models\User;
+use Illuminate\Foundation\Testing\RefreshDatabase;
+use Illuminate\Support\Facades\Hash;
+use Tests\TestCase;
+
+class ProfileTest extends TestCase
+{
+    use RefreshDatabase;
+
+    protected function setUp(): void
+    {
+        parent::setUp();
+
+        $this->seed();
+    }
+
+    private function token(): string
+    {
+        $user = User::where('email', 'admin@abouelsid.com')->first();
+
+        return $user->createToken('test-token')->plainTextToken;
+    }
+
+    public function test_get_profile_authenticated_returns_200_with_user_data(): void
+    {
+        $response = $this->getJson('/api/v1/admin/profile', [
+            'Authorization' => "Bearer {$this->token()}",
+        ]);
+
+        $response->assertStatus(200)
+            ->assertJson(['success' => true])
+            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role']])
+            ->assertJsonPath('data.email', 'admin@abouelsid.com');
+    }
+
+    public function test_get_profile_unauthenticated_returns_401(): void
+    {
+        $response = $this->getJson('/api/v1/admin/profile');
+
+        $response->assertStatus(401)
+            ->assertJson(['success' => false]);
+    }
+
+    public function test_update_profile_with_valid_data_updates_name_and_email(): void
+    {
+        $response = $this->putJson('/api/v1/admin/profile', [
+            'name' => 'Updated Name',
+            'email' => 'updated@abouelsid.com',
+        ], [
+            'Authorization' => "Bearer {$this->token()}",
+        ]);
+
+        $response->assertStatus(200)
+            ->assertJson(['success' => true])
+            ->assertJsonPath('data.name', 'Updated Name')
+            ->assertJsonPath('data.email', 'updated@abouelsid.com');
+
+        $this->assertDatabaseHas('users', [
+            'email' => 'updated@abouelsid.com',
+            'name' => 'Updated Name',
+        ]);
+    }
+
+    public function test_update_profile_with_duplicate_email_returns_422(): void
+    {
+        User::factory()->create(['email' => 'taken@abouelsid.com']);
+
+        $response = $this->putJson('/api/v1/admin/profile', [
+            'name' => 'Admin',
+            'email' => 'taken@abouelsid.com',
+        ], [
+            'Authorization' => "Bearer {$this->token()}",
+        ]);
+
+        $response->assertStatus(422)
+            ->assertJson(['success' => false])
+            ->assertJsonValidationErrors(['email']);
+    }
+
+    public function test_update_password_with_correct_current_password_returns_200(): void
+    {
+        $response = $this->putJson('/api/v1/admin/profile/password', [
+            'current_password' => 'password',
+            'password' => 'newpassword123',
+            'password_confirmation' => 'newpassword123',
+        ], [
+            'Authorization' => "Bearer {$this->token()}",
+        ]);
+
+        $response->assertStatus(200)
+            ->assertJson(['success' => true]);
+
+        $user = User::where('email', 'admin@abouelsid.com')->first();
+        $this->assertTrue(Hash::check('newpassword123', $user->password));
+    }
+
+    public function test_update_password_with_wrong_current_password_returns_422(): void
+    {
+        $response = $this->putJson('/api/v1/admin/profile/password', [
+            'current_password' => 'wrong-password',
+            'password' => 'newpassword123',
+            'password_confirmation' => 'newpassword123',
+        ], [
+            'Authorization' => "Bearer {$this->token()}",
+        ]);
+
+        $response->assertStatus(422)
+            ->assertJson(['success' => false])
+            ->assertJsonValidationErrors(['current_password']);
+    }
+
+    public function test_update_password_with_mismatched_confirmation_returns_422(): void
+    {
+        $response = $this->putJson('/api/v1/admin/profile/password', [
+            'current_password' => 'password',
+            'password' => 'newpassword123',
+            'password_confirmation' => 'does-not-match',
+        ], [
+            'Authorization' => "Bearer {$this->token()}",
+        ]);
+
+        $response->assertStatus(422)
+            ->assertJson(['success' => false])
+            ->assertJsonValidationErrors(['password']);
+    }
+}
===== END change.diff =====

===== BEGIN context-bundle.md =====
# Context bundle

bundle_version 2 · budget 8000 / used 299 tokens

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

**Subject:** App\Models\User::where
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

**Subject:** App\Models\User::where
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

**Subject:** App\Models\User::where
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

**Subject:** App\Models\User::factory
**Reason:** the region depends on App\Models\User::factory, whose contract is defined in another file
**Source:** `tests/Feature/ProfileTest.php` :: `App\Models\User::factory` (lines 1-130)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Subject:** App\Models\User::where
**Reason:** the region depends on App\Models\User::where, whose contract is defined in another file
**Source:** `tests/Feature/ProfileTest.php` :: `App\Models\User::where` (lines 1-130)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · unverifiable_premise

**Subject:** surrounding-transaction
**Reason:** the region performs several persistence writes; whether a transaction wraps them is decided by the caller, which the diff does not show
**Source:** `app/Http/Controllers/Admin/ProfileController.php` (lines 1-55)
**Tokens:** 19

```text
ASSUMPTION: this code assumes a surrounding transaction; caller not checked
```

## Dropped

Nothing was dropped.
===== END context-bundle.md =====

===== BEGIN context-diagnostics.txt =====
new file: app/Http/Controllers/Admin/ProfileController.php — own-file context is in the diff, not fetched
new file: app/Http/Requests/Profile/UpdatePasswordRequest.php — own-file context is in the diff, not fetched
new file: app/Http/Requests/Profile/UpdateProfileRequest.php — own-file context is in the diff, not fetched
new file: tests/Feature/ProfileTest.php — own-file context is in the diff, not fetched
framework reference: Illuminate\Support\Facades\Hash::check declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Hash.php:11 (@method static bool check(string $value, string $hashedValue, array $options = []))
framework reference: Illuminate\Support\Facades\Hash::make declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Hash.php:10 (@method static string make(string $value, array $options = []))
dependency class: Illuminate\Http\Request provided by vendor/laravel/framework/src/Illuminate/Http/Request.php; surface not fetched
dependency class: Illuminate\Http\JsonResponse provided by vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php; surface not fetched
already in the diff: App\Http\Requests\Profile\UpdateProfileRequest declared in app/Http/Requests/Profile/UpdateProfileRequest.php; not fetched again
already in the diff: App\Http\Requests\Profile\UpdatePasswordRequest declared in app/Http/Requests/Profile/UpdatePasswordRequest.php; not fetched again
dependency member: Illuminate\Validation\Rule::unique declared at vendor/laravel/framework/src/Illuminate/Validation/Rule.php:94; source not fetched
framework reference: Illuminate\Support\Facades\Route::prefix declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:100 (@method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix))
framework reference: Illuminate\Support\Facades\Route::get declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:6 (@method static \Illuminate\Routing\Route get(string $uri, array|string|callable|null $action = null))
framework reference: Illuminate\Support\Facades\Route::put declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Route.php:8 (@method static \Illuminate\Routing\Route put(string $uri, array|string|callable|null $action = null))
unresolved named_reference: App\Models\User::where in tests/Feature/ProfileTest.php
unresolved named_reference: App\Models\User::factory in tests/Feature/ProfileTest.php
framework reference: Illuminate\Support\Facades\Hash::check declared at vendor/laravel/framework/src/Illuminate/Support/Facades/Hash.php:11 (@method static bool check(string $value, string $hashedValue, array $options = []))
===== END context-diagnostics.txt =====
