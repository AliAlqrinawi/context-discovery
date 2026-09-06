# Context bundle

bundle_version 1 · budget 8000 / used 1645 tokens

## flagged · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Exports/ProductsExport.php` :: `App\Models\Product::select` (lines 26-32)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Product::updateOrCreate, whose contract is defined in another file
**Source:** `app/Imports/ProductsImport.php` :: `App\Models\Product::updateOrCreate` (lines 27-40)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Models\Product::where, whose contract is defined in another file
**Source:** `app/Imports/ProductsImport.php` :: `App\Models\Product::where` (lines 18-24)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `categories` (lines 71-74)
**Tokens:** 25

```php
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `category` (lines 67-70)
**Tokens:** 23

```php
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `fillable` (lines 22-40)
**Tokens:** 94

```php
    protected $fillable = [
        'name',
        'image',
        'category_id',
        'price',
        'offer_price',
        'quantity',
        'weight',
        'sort_order',
        'barcode',
        'sku',
        'show_in',
        'description',
        'keywords',
        'is_active',
        'is_international',
        'position',
        'location'
    ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `getMainPrice` (lines 58-65)
**Tokens:** 60

```php
    public function getMainPrice()
    {
        $price =  $this->price;
        if (request()->is_international && request()->coin_price && $price) {
            return $price * request()->coin_price;
        }
        return $price;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `getOfferPrice` (lines 50-57)
**Tokens:** 67

```php
    public function getOfferPrice()
    {
        $offerPrice =  $this->offer_price;
        if (request()->is_international && request()->coin_price && $offerPrice) {
            return $offerPrice * request()->coin_price;
        }
        return $offerPrice;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `getPrice` (lines 42-49)
**Tokens:** 62

```php
    public function getPrice()
    {
        $price =  $this->offer_price ?? $this->price;
        if (request()->is_international && request()->coin_price) {
            return $price * request()->coin_price;
        }
        return $price;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `orderItems` (lines 76-79)
**Tokens:** 24

```php
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeActive` (lines 121-124)
**Tokens:** 25

```php
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeActiveCategory` (lines 88-93)
**Tokens:** 45

```php
    public function scopeActiveCategory($query)
    {
        return $query->whereHas('categories', function ($q) {
            return $q->where('is_active', 1);
        });
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeCategory` (lines 82-87)
**Tokens:** 51

```php
    public function scopeCategory($query, $value)
    {
        return $query->whereHas('categories', function ($q) use ($value) {
            return $q->where('categories.id', $value);
        });
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilter` (lines 145-156)
**Tokens:** 73

```php
    public function scopeFilter(Builder $builder)
    {

        $builder
            ->filterByStatus()
            ->filterById()
            ->filterByName()
            ->filterByCategories()
            ->filterBySku()
            ->filterByQuantity()
            ->filterByDate();
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilterByCategories` (lines 201-210)
**Tokens:** 79

```php
    public function scopeFilterByCategories(Builder $builder)
    {
        $categories = request('categories');

        if ($categories) {
            $builder->whereHas('categories', function ($query) use ($categories) {
                $query->where('categories.id', $categories);
            });
        }
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilterByCategory` (lines 188-199)
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

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilterByDate` (lines 231-239)
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

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilterById` (lines 168-175)
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

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilterByName` (lines 177-186)
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

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilterByQuantity` (lines 221-229)
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

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilterBySku` (lines 212-219)
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

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeFilterByStatus` (lines 158-166)
**Tokens:** 55

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

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeInStock` (lines 94-97)
**Tokens:** 29

```php
    public function scopeInStock($query, $value)
    {
        return $query->where('quantity', '>', $value);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeInactive` (lines 116-119)
**Tokens:** 25

```php
    public function scopeInactive($query)
    {
        return $query->where('is_active', 0);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeInternational` (lines 131-134)
**Tokens:** 28

```php
    public function scopeInternational($query)
    {
        return $query->where('is_international', 1);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeNotInternational` (lines 126-129)
**Tokens:** 29

```php
    public function scopeNotInternational($query)
    {
        return $query->where('is_international', 0);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeOrderByPosition` (lines 241-244)
**Tokens:** 36

```php
    public function scopeOrderByPosition(Builder $builder)
    {
        $builder->orderBy(DB::raw('ISNULL(position), position'), 'asc');
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeProductIds` (lines 111-114)
**Tokens:** 28

```php
    public function scopeProductIds($query, $value)
    {
        return $query->whereIn('id', $value);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeProductsType` (lines 136-143)
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

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeSearch` (lines 103-110)
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

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `scopeType` (lines 98-102)
**Tokens:** 27

```php
    public function scopeType($query, $value)
    {

        return $query->where('show_in', $value);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Product::select, whose contract is defined in another file
**Source:** `app/Models/Product.php` :: `translatable` (lines 21-21)
**Tokens:** 16

```php
    public $translatable = ['name', 'keywords', 'description'];
```

## Dropped

Nothing was dropped.
