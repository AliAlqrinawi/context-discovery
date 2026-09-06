# Context bundle

bundle_version 1 · budget 8000 / used 124 tokens

## fetched · same_file_reference

**Reason:** the region calls the sibling member restockItems, whose contract the diff does not show
**Source:** `app/Repositories/OrderRepository.php` :: `restockItems` (lines 56-61)
**Tokens:** 50

```php
    public function restockItems($model)
    {
        foreach ($model->items()->with('product')->get() as $item) {
            $item->product->increment('quantity', $item->quantity);
        }
    }
```

## fetched · named_reference

**Reason:** the region depends on App\Enum\OrderStatusEnum::CANCELLED, whose contract is defined in another file
**Source:** `app/Enum/OrderStatusEnum.php` :: `CANCELLED` (lines 11-11)
**Tokens:** 8

```php
  const CANCELLED = 'cancelled';
```

## fetched · named_reference

**Reason:** the region depends on App\Models\Order::orderIds, whose contract is defined in another file
**Source:** `app/Models/Order.php` :: `scopeOrderIds` (lines 157-160)
**Tokens:** 26

```php
   public function scopeOrderIds($query,$value)
   {
       return $query->whereIn('id',$value);
   }
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\DB::beginTransaction, whose contract is defined in another file
**Source:** `app/Repositories/OrderRepository.php` :: `Illuminate\Support\Facades\DB::beginTransaction` (lines 105-120)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## flagged · named_reference

**Reason:** the region depends on Illuminate\Support\Facades\DB::commit, whose contract is defined in another file
**Source:** `app/Repositories/OrderRepository.php` :: `Illuminate\Support\Facades\DB::commit` (lines 105-120)
**Tokens:** 20

```text
ASSUMPTION: named reference could not be resolved on disk; contract unverified
```

## Dropped

Nothing was dropped.
