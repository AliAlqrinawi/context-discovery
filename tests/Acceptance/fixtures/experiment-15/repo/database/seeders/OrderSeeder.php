<?php

namespace Database\Seeders;

use App\Models\Order;

class OrderSeeder
{
    public function existing(): string
    {
        return 'unchanged';
    }

    public function run(): void
    {
        Order::firstOrCreate(['sku' => 'SEED'], ['total' => 0]);  // S1 · autoload (production) origin
    }
}
