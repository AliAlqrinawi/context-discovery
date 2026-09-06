# Context bundle

bundle_version 1 · budget 8000 / used 1603 tokens

## fetched · same_file_symbol_absence

**Reason:** the region uses OrderController, which the file's use block does not import
**Source:** `routes/api.php` (lines 4-5)
**Tokens:** 17

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
```

## fetched · named_reference

**Reason:** the region depends on App\Enum\OrderStatusEnum::CANCELLED, whose contract is defined in another file
**Source:** `app/Enum/OrderStatusEnum.php` :: `CANCELLED` (lines 11-11)
**Tokens:** 8

```php
  const CANCELLED = 'cancelled';
```

## fetched · named_reference

**Reason:** the region depends on App\Enum\OrderStatusEnum::PENDING, whose contract is defined in another file
**Source:** `app/Enum/OrderStatusEnum.php` :: `PENDING` (lines 8-8)
**Tokens:** 7

```php
  const PENDING = 'pending';
```

## fetched · named_reference

**Reason:** the region depends on App\Http\Resources\OrderResource, whose contract is defined in another file
**Source:** `app/Http/Resources/OrderResource.php` :: `toArray` (lines 10-30)
**Tokens:** 186

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
            'payment_url' => $this->payment_url,
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

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `address` (lines 36-39)
**Tokens:** 22

```php
   public function address()
   {
      return $this->belongsTo(Address::class);
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `deliveryType` (lines 40-43)
**Tokens:** 24

```php
   public function deliveryType()
   {
      return $this->belongsTo(DeliveryType::class);
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `driver` (lines 48-51)
**Tokens:** 21

```php
   public function driver()
   {
      return $this->belongsTo(Driver::class);
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `fillable` (lines 15-34)
**Tokens:** 97

```php
   protected $fillable = [
      'user_id',
      'address_id',
      'coupon_id',
      'sub_total',
      'delivery',
      'discount',
      'grand_total',
      'code',
      'status',
      'payment_method',
      'payment_status',
      'notes',
      'device_type',
      'use_wallet',
      'wallet_credit',
      'payment_id',
      'driver_id',
      'delivery_type_id'
   ];
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `items` (lines 56-59)
**Tokens:** 21

```php
   public function items()
   {
      return $this->hasMany(OrderItem::class);
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `orderStatus` (lines 200-209)
**Tokens:** 126

```php
   public static function orderStatus()
   {
      return [
         OrderStatusEnum::NEW => self::orderStatusName(OrderStatusEnum::NEW),
         OrderStatusEnum::PENDING => self::orderStatusName(OrderStatusEnum::PENDING),
         OrderStatusEnum::SHIPPING => self::orderStatusName(OrderStatusEnum::SHIPPING),
         OrderStatusEnum::COMPLETE => self::orderStatusName(OrderStatusEnum::COMPLETE),
         OrderStatusEnum::CANCELLED => self::orderStatusName(OrderStatusEnum::CANCELLED)
      ];
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `orderStatusName` (lines 210-230)
**Tokens:** 150

```php
   public static function orderStatusName($name)
   {
      switch ($name) {
         case OrderStatusEnum::NEW:
            return __('request accept');
            break;
         case OrderStatusEnum::PENDING:
            return __('order processing');
            break;
         case OrderStatusEnum::SHIPPING:
            return __('shipped');
            break;
         case  OrderStatusEnum::COMPLETE:
            return __('receipt confirmed');
            break;
         case OrderStatusEnum::CANCELLED:
            return __('order canceled');
            break;
          
      }
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `payment` (lines 52-55)
**Tokens:** 22

```php
   public function payment()
   {
      return $this->belongsTo(Payment::class);
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `paymentStatus` (lines 193-199)
**Tokens:** 58

```php
   public static function paymentStatus()
   {
      return [
         OrderPaymentStatusEnum::PAID => __(OrderPaymentStatusEnum::PAID),
         OrderPaymentStatusEnum::UN_PAID => __(OrderPaymentStatusEnum::UN_PAID)
      ];
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `scopeFilter` (lines 63-74)
**Tokens:** 75

```php
   public function scopeFilter(Builder $builder)
   {
      $builder
         ->filterByStatus()
         ->filterByPaymentStatus()
         ->filterByPaymentId()
         ->filterById()
         ->filterByName()
         ->filterByPhone()
         ->filterByCountry()
         ->filterByDate();
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `scopeFilterByCountry` (lines 134-145)
**Tokens:** 120

```php
   public function scopeFilterByCountry(Builder $builder)
   {
      $country = request('country');

      if ($country) {
         $builder->whereHas('user.country', function ($query) use ($country) {
            $query->where(function ($q) use ($country) {
               $q->whereRaw('lower(JSON_EXTRACT(name, "$.en")) LIKE "%' . strtolower($country) . '%" OR lower(JSON_EXTRACT(name, "$.ar")) LIKE "%' . strtolower($country) . '%"');
            });
         });
      }
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `scopeFilterByDate` (lines 147-155)
**Tokens:** 69

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

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `scopeFilterById` (lines 103-110)
**Tokens:** 38

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

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `scopeFilterByName` (lines 112-121)
**Tokens:** 65

```php
   public function scopeFilterByName(Builder $builder)
   {
      $name = request('name');

      if ($name) {
         $builder->whereHas('user', function ($q) use ($name) {
            $q->where('name', 'like', '%' . $name . '%');
         });
      }
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `scopeFilterByPaymentId` (lines 94-101)
**Tokens:** 49

```php
   public function scopeFilterByPaymentId(Builder $builder)
   {
      $paymentId = request('payment_id');

      if ($paymentId) {
         $builder->where('payment_id', $paymentId);
      }
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `scopeFilterByPaymentStatus` (lines 85-92)
**Tokens:** 55

```php
   public function scopeFilterByPaymentStatus(Builder $builder)
   {
      $paymentStatus = request('payment_status');

      if ($paymentStatus) {
         $builder->where('payment_status', $paymentStatus);
      }
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `scopeFilterByPhone` (lines 123-132)
**Tokens:** 62

```php
   public function scopeFilterByPhone(Builder $builder)
   {
      $phone = request('phone');

      if ($phone) {
         $builder->whereHas('user', function ($q) use ($phone) {
            $q->where('phone', $phone);
         });
      }
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `scopeFilterByStatus` (lines 76-83)
**Tokens:** 44

```php
   public function scopeFilterByStatus(Builder $builder)
   {
      $status = request('stauts');

      if ($status) {
         $builder->where('status', $status);
      }
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `scopeNewAndPendingStatus` (lines 182-185)
**Tokens:** 38

```php
   public function scopeNewAndPendingStatus($query)
   {
      return $query->whereIn('status',[OrderStatusEnum::NEW , OrderStatusEnum::PENDING]);
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `scopeNewOrders` (lines 162-165)
**Tokens:** 28

```php
   public function scopeNewOrders($query)
   {
      return $query->where('status',OrderStatusEnum::NEW);
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `scopeOrderIds` (lines 157-160)
**Tokens:** 26

```php
   public function scopeOrderIds($query,$value)
   {
       return $query->whereIn('id',$value);
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `scopePendingOrders` (lines 167-170)
**Tokens:** 30

```php
   public function scopePendingOrders($query)
   {
      return $query->where('status',OrderStatusEnum::PENDING);
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `scopeValidOrder` (lines 172-180)
**Tokens:** 85

```php
   public function scopeValidOrder($query)
   {
      return $query->where('status','!=',OrderStatusEnum::CANCELLED)->where(function($q){
         $q->where(function($q){
           $q->where('payment_method',OrderPaymentMethodEnum::CASH)
              ->orWhere('payment_status',OrderPaymentStatusEnum::PAID);
          });
      });
   }
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `user` (lines 44-47)
**Tokens:** 20

```php
   public function user()
   {
      return $this->belongsTo(User::class);
   }
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Validation\ValidationException::withMessages, whose contract is defined in another file
**Source:** `app/Services/OrderService.php` :: `Illuminate\Validation\ValidationException::withMessages` (lines 148-161)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\Route::PUT, whose contract is defined in another file
**Source:** `routes/api.php` :: `Illuminate\Support\Facades\Route::PUT` (lines 28-34)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## Dropped

Nothing was dropped.
