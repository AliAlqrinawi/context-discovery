# Context bundle

bundle_version 1 · budget 8000 / used 1387 tokens

## fetched · same_file_symbol_absence

**Reason:** the region uses ProductController, which the file's use block does not import
**Source:** `routes/api.php` (lines 4-5)
**Tokens:** 17

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
```

## flagged · named_reference

**Reason:** the region depends on App\Http\Resources\ProductResource::collection, whose contract is defined in another file
**Source:** `app/Http/Controllers/Api/ProductController.php` :: `App\Http\Resources\ProductResource::collection` (lines 30-40)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Http\Request, whose contract is defined in another file
**Source:** `app/Http/Controllers/Api/ProductController.php` :: `Illuminate\Http\Request` (lines 30-40)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\ProductResource, whose contract is defined in another file
**Source:** `app/Http/Resources/ProductResource.php` :: `toArray` (lines 10-29)
**Tokens:** 194

```php
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
          'id'=>$this->id,
          'name'=>$this->name,
          'image'=>$this->image,
          'price'=>$this->price,
          'offer_price'=>$this->offer_price,
          'quantity'=>$this->quantity,
          'order_limit'=>$this->order_limit,
          'is_favorite' =>  auth('sanctum')->check() && auth('sanctum')->user()->favorites->contains($this->id) ?? false,
          'images'=>$this->relationLoaded("media") ?  FileResource::collection($this->media) : [],
          'category'=>$this->relationLoaded("category") ?  new CategoryResource($this->category) : null
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `category` (lines 29-32)
**Tokens:** 23

```php
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `fillable` (lines 20-22)
**Tokens:** 56

```php
    protected $fillable = [
        'name', 'image', 'category_id', 'price', 'offer_price', 'quantity', 'weight', 'sort_order', 'barcode', 'sku', 'show_in', 'description', 'keywords', 'is_active', 'is_international'
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `getPrice` (lines 24-27)
**Tokens:** 24

```php
    public function getPrice()
    {
        return $this->offer_price ?? $this->price;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `orderItems` (lines 34-37)
**Tokens:** 24

```php
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeCategory` (lines 40-43)
**Tokens:** 29

```php
    public function scopeCategory($query, $value)
    {
        return $query->where('category_id', $value);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilter` (lines 87-98)
**Tokens:** 75

```php
    public function scopeFilter(Builder $builder)
    {
       
        $builder
            ->filterByStatus()
            ->filterById()
            ->filterByName()
            ->filterByCategory()
            ->filterBySku()
            ->filterByQuantity()
            ->filterByDate();
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilterByCategory` (lines 130-141)
**Tokens:** 128

```php
    public function scopeFilterByCategory(Builder $builder)
    {
        $category = request('category');

        if ($category) {
            $builder->whereHas('category', function ($query) use ($category) {
                $query->where(function ($q) use ($category) {
                    $q->whereRaw('lower(JSON_EXTRACT(name, "$.en")) LIKE "%' . strtolower($category) . '%" OR lower(JSON_EXTRACT(name, "$.ar")) LIKE "%' . strtolower($category) . '%"');
                });
            });
        }
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilterByDate` (lines 162-170)
**Tokens:** 73

```php
    public function scopeFilterByDate(Builder $builder)
    {
        $dateFrom = request('date_from');
        $dateTo = request('date_to');

        if ($dateFrom && $dateTo) {
            $builder->where('created_at', '>=', $dateFrom)->where('created_at', '<=', $dateTo);
        }
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilterById` (lines 110-117)
**Tokens:** 41

```php
    public function scopeFilterById(Builder $builder)
    {
        $id = request('id');

        if ($id) {
            $builder->where('id', $id);
        }
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilterByName` (lines 119-128)
**Tokens:** 97

```php
    public function scopeFilterByName(Builder $builder)
    {
        $name = request('name');

        if ($name) {
            return $builder->where(function ($q) use ($name) {
                $q->whereRaw('lower(JSON_EXTRACT(name, "$.en")) LIKE "%' . strtolower($name) . '%" OR lower(JSON_EXTRACT(name, "$.ar")) LIKE "%' . strtolower($name) . '%"');
            });
        }
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilterByQuantity` (lines 152-160)
**Tokens:** 81

```php
    public function scopeFilterByQuantity(Builder $builder)
    {
        $quantityFrom = request('quantity_from');
        $quantityTo = request('quantity_to');

        if ($quantityFrom && $quantityTo) {
            $builder->where('quantity', '>=', $quantityFrom)->where('quantity', '<=', $quantityTo);
        }
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilterBySku` (lines 143-150)
**Tokens:** 43

```php
    public function scopeFilterBySku(Builder $builder)
    {
        $sku = request('sku');

        if ($sku) {
            $builder->where('sku', $sku);
        }
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilterByStatus` (lines 100-108)
**Tokens:** 56

```php
    public function scopeFilterByStatus(Builder $builder)
    {
       
        $status = request('status');

        if (isset($status) && $status !== '') {
            $builder->where('is_active', $status);
        }
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeInStock` (lines 51-54)
**Tokens:** 29

```php
    public function scopeInStock($query, $value)
    {
        return $query->where('quantity', '>', $value);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeInactive` (lines 73-76)
**Tokens:** 25

```php
    public function scopeInactive($query)
    {
        return $query->where('is_active', 0);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeProductIds` (lines 68-71)
**Tokens:** 28

```php
    public function scopeProductIds($query, $value)
    {
        return $query->whereIn('id', $value);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeProductsType` (lines 78-85)
**Tokens:** 88

```php
    public function scopeProductsType($query, $value)
    {
        if (in_array($value, [LocalizationTypeEnum::LOCAL, LocalizationTypeEnum::INTERNATIONAL])) {
            $type = $value == LocalizationTypeEnum::LOCAL ? 0 : 1;
            return $query->where('is_international', $type);
        }
        return $query->where('show_in', $value);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeSearch` (lines 60-67)
**Tokens:** 133

```php
    public function scopeSearch($query, $value)
    {
        return $query->where(function ($q) use ($value) {
            $q->whereRaw('lower(JSON_EXTRACT(name, "$.en")) LIKE "%' . strtolower($value) . '%" OR lower(JSON_EXTRACT(name, "$.ar")) LIKE "%' . strtolower($value) . '%"');
        })->orWhere(function ($q) use ($value) {
            $q->whereRaw('lower(JSON_EXTRACT(keywords, "$.en")) LIKE "%' . strtolower($value) . '%" OR lower(JSON_EXTRACT(keywords, "$.ar")) LIKE "%' . strtolower($value) . '%"');
        });
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeType` (lines 55-59)
**Tokens:** 27

```php
    public function scopeType($query, $value)
    {

        return $query->where('show_in', $value);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `translatable` (lines 19-19)
**Tokens:** 16

```php
    public $translatable = ['name', 'keywords', 'description'];
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Product::with, whose contract is defined in another file
**Source:** `app/Repositories/ProductRepository.php` :: `App\Models\Product::with` (lines 34-43)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::get, whose contract is defined in another file
**Source:** `routes/api.php` :: `Illuminate\Support\Facades\Route::get` (lines 48-54)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## Dropped

Nothing was dropped.
