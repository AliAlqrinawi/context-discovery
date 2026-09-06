# Context bundle

bundle_version 1 · budget 8000 / used 1508 tokens

## fetched · same_file_reference

**Reason:** the region calls the sibling member addressOperations, whose contract the diff does not show
**Source:** `app/Services/OrderService.php` :: `addressOperations` (lines 63-77)
**Tokens:** 109

```php
    private function addressOperations(&$request)
    {
        $city = $this->addressRepo->getCity($request['address_id']);
        if($city)
        {
            if($city->order_limit > $request['sub_total'])
            {
                throw ValidationException::withMessages([__("The minimum order in your area is").$city->order_limit]);

            }
            $request['delivery'] = $city->delivery_cost;

        }

    }
```

## fetched · same_file_reference

**Reason:** the region calls the sibling member itemsOperations, whose contract the diff does not show
**Source:** `app/Services/OrderService.php` :: `itemsOperations` (lines 45-61)
**Tokens:** 156

```php
    private function itemsOperations(&$request)
    {
        $request['sub_total'] = 0;
        foreach($request['items'] as $item)
        {
           $product = $this->productRepo->find($item['id']);
           if($product)
           {
             if( ($product->order_limit) and ($product->order_limit < $item['quantity']))
             {
                throw ValidationException::withMessages([__("The allowed number of orders for the product is").$product->order_limit.__("pieces per order")]);
             }
             
            $request['sub_total'] += $product->getPrice();
           }
        }
    }
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Api/AddressController.php` (lines 16-16)
**Tokens:** 16

```php
    public function __construct(AddressService $addressService)
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Api/AuthController.php` (lines 17-17)
**Tokens:** 15

```php
    public function __construct(AuthService $authService)
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Api/BannerController.php` (lines 15-15)
**Tokens:** 16

```php
    public function __construct(BannerService $bannerService )
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Api/CategoryController.php` (lines 14-14)
**Tokens:** 17

```php
    public function __construct(CategoryService $categoryService )
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Api/ContactController.php` (lines 14-14)
**Tokens:** 16

```php
    public function __construct(ContactService $contactService)
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Api/CountryController.php` (lines 14-14)
**Tokens:** 16

```php
    public function __construct(CountryService $countryService)
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Api/CouponController.php` (lines 14-14)
**Tokens:** 16

```php
    public function __construct(CouponService $couponService)
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Api/FavoriteController.php` (lines 17-17)
**Tokens:** 17

```php
    public function __construct(FavoriteService $favoriteService)
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Api/GovernorateController.php` (lines 14-14)
**Tokens:** 18

```php
    public function __construct(GovernorateService $governorateService)
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Api/OrderController.php` (lines 14-14)
**Tokens:** 15

```php
    public function __construct(OrderService $orderService )
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Api/ProductController.php` (lines 14-14)
**Tokens:** 16

```php
    public function __construct(ProductService $productService)
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Http/Controllers/Api/SettingController.php` (lines 16-16)
**Tokens:** 25

```php
    public function __construct(SettingService $settingService /*,BannerService $bannerService ,*/)
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Repositories/AddressRepository.php` (lines 12-12)
**Tokens:** 12

```php
    public function __construct(Address $model)
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Repositories/AddressRepository.php` (lines 14-14)
**Tokens:** 9

```php
        parent::__construct($model);
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Repositories/BannerRepository.php` (lines 14-14)
**Tokens:** 12

```php
    public function __construct(Banner $model)
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Repositories/BannerRepository.php` (lines 16-16)
**Tokens:** 9

```php
        parent::__construct($model);
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Repositories/BaseRepository.php` (lines 19-19)
**Tokens:** 12

```php
    public function __construct(Model $model)
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Repositories/CategoryRepository.php` (lines 12-12)
**Tokens:** 12

```php
    public function __construct(Category $model)
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Repositories/CategoryRepository.php` (lines 14-14)
**Tokens:** 9

```php
        parent::__construct($model);
```

## fetched · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Repositories/CityRepository.php` (lines 13-13)
**Tokens:** 11

```php
    public function __construct(City $model)
```

## flagged · changed_signature

**Reason:** the signature of __construct changed; its call sites are not shown by the diff
**Source:** `app/Services/OrderService.php` :: `__construct` (lines 3-24)
**Tokens:** 21

```text
ASSUMPTION: additional call sites exist beyond the search bound; not all verified
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Requests\Api\OrderRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Api/OrderRequest.php` :: `authorize` (lines 10-16)
**Tokens:** 38

```php
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Requests\Api\OrderRequest, whose contract is defined in another file
**Source:** `app/Http/Requests/Api/OrderRequest.php` :: `rules` (lines 18-35)
**Tokens:** 176

```php
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'address_id'=>['required','exists:addresses,id'],
            'code'=>['nullable','exists:coupons,code'],
            'payment_method'=>['required','string'],
            'items'=>['required','array'],
            'items.*.id'=>['required',Rule::exists('products','id')->where(function($q){
              $q->where('is_active',1);
            })],
            'items.*.quantity'=>['required','integer','min:1',new QuantityCheckRule()]
        ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\OrderResource, whose contract is defined in another file
**Source:** `app/Http/Resources/OrderResource.php` :: `toArray` (lines 10-29)
**Tokens:** 174

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
            'code'=>$this->code,
            'sub_total'=>$this->sub_total,
            'discount'=>$this->discount,
            'delivery'=>$this->delivery,
            'grand_total'=>$this->grand_total,
            'payment_method'=>$this->payment_method,
            'address'=>$this->relationLoaded("address") ? new AddressResource($this->address) : null,
            'items'=>$this->relationLoaded("items") ?  OrderItemResource::collection($this->items) : [],

          ];
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\AddressRepository, whose contract is defined in another file
**Source:** `app/Repositories/AddressRepository.php` :: `__construct` (lines 8-15)
**Tokens:** 44

```php
/**
     * AddressRepository constructor.
     * @param Address $model
     */
    public function __construct(Address $model)
    {
        parent::__construct($model);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\AddressRepository, whose contract is defined in another file
**Source:** `app/Repositories/AddressRepository.php` :: `checkHasDefaultAddress` (lines 28-31)
**Tokens:** 34

```php
    public function checkHasDefaultAddress()
    {
        return auth()->user()->addresses()->where('is_default',true)->exists();
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\AddressRepository, whose contract is defined in another file
**Source:** `app/Repositories/AddressRepository.php` :: `index` (lines 16-19)
**Tokens:** 34

```php
    public function index()
    {
        return auth()->user()->addresses()->with('governorate.country','city')->latest()->get();
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\AddressRepository, whose contract is defined in another file
**Source:** `app/Repositories/AddressRepository.php` :: `updateDefaultAddress` (lines 21-26)
**Tokens:** 58

```php
    public function updateDefaultAddress(Address $model)
    {
        auth()->user()->addresses()->where('is_default',true)->update(['is_default'=>false]);
        $model->update(['is_default'=>true]);
        return true;
    }
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Database\Eloquent\Model::find, whose contract is defined in another file
**Source:** `app/Repositories/BaseRepository.php` :: `Illuminate\Database\Eloquent\Model::find` (lines 46-52)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\OrderRepository, whose contract is defined in another file
**Source:** `app/Repositories/OrderRepository.php` :: `__construct` (lines 8-15)
**Tokens:** 44

```php
    /**
     * OrderRepository constructor.
     * @param Order $model
     */
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\OrderRepository, whose contract is defined in another file
**Source:** `app/Repositories/OrderRepository.php` :: `index` (lines 17-20)
**Tokens:** 33

```php
    public function index()
    {
        return auth()->user()->orders()->with('address','items.product')->latest()->get();
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\ProductRepository, whose contract is defined in another file
**Source:** `app/Repositories/ProductRepository.php` :: `__construct` (lines 8-15)
**Tokens:** 44

```php
/**
     * ProductRepository constructor.
     * @param Product $model
     */
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Repositories\ProductRepository, whose contract is defined in another file
**Source:** `app/Repositories/ProductRepository.php` :: `index` (lines 16-28)
**Tokens:** 134

```php
    public function index($request)
    {
        $query = $this->model->with('category');
        $query->when(isset($request['category_id']), function ($q) use ($request) {
            $q->category($request['category_id']);
        })->when(isset($request['type']), function ($q) use ($request) {
            $q->type($request['type']);
        })->when(isset($request['name']), function ($q) use ($request) {
            $q->search($request['name']);
        })->active()->inStock(0);

        return $query->latest()->get();
    }
```

## flagged · named_reference

**Reason:** the region depends on App\Repositories\OrderRepository::create, whose contract is defined in another file
**Source:** `app/Services/OrderService.php` :: `App\Repositories\OrderRepository::create` (lines 28-79)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on App\Repositories\ProductRepository::find, whose contract is defined in another file
**Source:** `app/Services/OrderService.php` :: `App\Repositories\ProductRepository::find` (lines 28-79)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\DB::beginTransaction, whose contract is defined in another file
**Source:** `app/Services/OrderService.php` :: `Illuminate\Support\Facades\DB::beginTransaction` (lines 28-79)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\DB::commit, whose contract is defined in another file
**Source:** `app/Services/OrderService.php` :: `Illuminate\Support\Facades\DB::commit` (lines 28-79)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Validation\ValidationException::withMessages, whose contract is defined in another file
**Source:** `app/Services/OrderService.php` :: `Illuminate\Validation\ValidationException::withMessages` (lines 28-79)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## Dropped

Nothing was dropped.
