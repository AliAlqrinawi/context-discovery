# Responses

## H01

- **Q1** (YES / NO / CANNOT_TELL): NO
- **Q2**: -
- **Q3**: Whether existing application behavior or consumers require the dish descriptions to remain non-null.
- **Q4** (HIGH / MEDIUM / LOW): MEDIUM
- **Q5**: "description_ar' => ['nullable', 'string']"

---

## H02

- **Q1** (YES / NO / CANNOT_TELL): YES
- **Q2**: The status filter uses the request key `stauts`, while the form repopulates the field from `status`, so the selected status is lost when the filter form is rendered again.
- **Q3**: Whether preserving the selected filter value after submission is required by the dashboard UX.
- **Q4** (HIGH / MEDIUM / LOW): MEDIUM
- **Q5**: "Form::select('stauts', \App\Models\User::UserStatus(), request('status'), ["

---

## H03

- **Q1** (YES / NO / CANNOT_TELL): YES
- **Q2**: The new endpoint reads `product_ids` without validating that the parameter exists, so a request without it can fail instead of returning a normal validation response.
- **Q3**: The intended API contract for `/active-products` and whether `product_ids` is guaranteed to be supplied by every caller.
- **Q4** (HIGH / MEDIUM / LOW): HIGH
- **Q5**: "$productIds = explode(',',$request['product_ids']);"

---

## H04

- **Q1** (YES / NO / CANNOT_TELL): NO
- **Q2**: -
- **Q3**: Whether every existing caller of `GetBranchesAction::execute()` remains compatible with the new optional parameter.
- **Q4** (HIGH / MEDIUM / LOW): MEDIUM
- **Q5**: "$key = \"branches_{$locale}_{$filter}\";"

---

## H05

- **Q1** (YES / NO / CANNOT_TELL): YES
- **Q2**: `getImageAttribute()` checks whether the value is truthy before checking whether it is a URL, so a non-empty URL is passed to `getimg()` instead of being returned directly.
- **Q3**: The expected behavior of `getimg()` for absolute URLs and whether existing user image values include such URLs.
- **Q4** (HIGH / MEDIUM / LOW): HIGH
- **Q5**: "if ($value) return getimg($value); elseif (filter_var($value, FILTER_VALIDATE_URL)) {"

---

## H06

- **Q1** (YES / NO / CANNOT_TELL): CANNOT_TELL
- **Q2**: -
- **Q3**: Whether profile endpoints are intended to be available to every authenticated admin user and whether the inherited route middleware is the complete authorization boundary.
- **Q4** (HIGH / MEDIUM / LOW): LOW
- **Q5**: "Route::prefix('profile')->controller(ProfileController::class)->group(function () {"

---

## H07

- **Q1** (YES / NO / CANNOT_TELL): CANNOT_TELL
- **Q2**: -
- **Q3**: Whether stock has already been decremented for NEW orders and therefore whether excluding NEW orders from restocking is correct.
- **Q4** (HIGH / MEDIUM / LOW): LOW
- **Q5**: "foreach($orders->notNewStatus()->with('items')->get() as $order)"

---

## H08

- **Q1** (YES / NO / CANNOT_TELL): NO
- **Q2**: -
- **Q3**: The externally documented response contract for all affected endpoints, including whether clients depend on any subtle differences between the old responses and the centralized helpers.
- **Q4** (HIGH / MEDIUM / LOW): MEDIUM
- **Q5**: "return $this->success(new AuthResource($result), 'Login successful');"

---

## H09

- **Q1** (YES / NO / CANNOT_TELL): CANNOT_TELL
- **Q2**: -
- **Q3**: The complete route/middleware configuration needed to verify that the moved admin endpoints retain exactly the intended authentication boundary.
- **Q4** (HIGH / MEDIUM / LOW): LOW
- **Q5**: "class BranchController extends \App\Http\Controllers\BranchController {}"

---

## H10

- **Q1** (YES / NO / CANNOT_TELL): YES
- **Q2**: The subtotal adds each product's price once and ignores the requested quantity, so ordering multiple units of a product produces an understated subtotal.
- **Q3**: The intended pricing rule for quantity and whether any later calculation corrects the subtotal.
- **Q4** (HIGH / MEDIUM / LOW): HIGH
- **Q5**: "$request['sub_total'] += $product->getPrice();"

---

## H11

- **Q1** (YES / NO / CANNOT_TELL): YES
- **Q2**: The project declares PHP `^8.2` while the newly added QR-code dependency requires PHP `^8.4`, making the declared platform requirement incompatible with the dependency.
- **Q3**: The deployment PHP version and Composer platform configuration.
- **Q4** (HIGH / MEDIUM / LOW): HIGH
- **Q5**: "\"php\": \"^8.2\"" and "\"php\": \"^8.4\""

---

## H12

- **Q1** (YES / NO / CANNOT_TELL): CANNOT_TELL
- **Q2**: -
- **Q3**: The database schema, model relationships, and required seeding order for all newly introduced seed data.
- **Q4** (HIGH / MEDIUM / LOW): LOW
- **Q5**: "$this->call(["

---

## H13

- **Q1** (YES / NO / CANNOT_TELL): CANNOT_TELL
- **Q2**: -
- **Q3**: The actual API response contract and whether the generic `paginated()` helper is byte-for-byte compatible with the deleted `DishCollection` behavior.
- **Q4** (HIGH / MEDIUM / LOW): MEDIUM
- **Q5**: "return $this->paginated(DishResource::collection($result), $result, __('messages.fetched'));"

---

## H14

- **Q1** (YES / NO / CANNOT_TELL): CANNOT_TELL
- **Q2**: -
- **Q3**: The product requirements or API contract defining the intended default page sizes for dishes, quote requests, and users.
- **Q4** (HIGH / MEDIUM / LOW): LOW
- **Q5**: "return $query->paginate($filters['per_page'] ?? 10);"