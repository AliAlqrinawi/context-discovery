<?php

namespace Tests\Support;

use App\Models\Order;

trait CreatesOrders
{
    public function existing(): string
    {
        return 'unchanged';
    }

    public function makeOrder(): Order
    {
        return Order::updateOrCreate(['sku' => 'A'], ['total' => 1]);  // H1 · test-support origin
    }
}
