<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Services\OrderCalculator;

class OrderController
{
    public function existing(): string
    {
        return 'unchanged';
    }

    /**
     * N1 · docblock noise: Order::create() and AuditLog::create() are prose here.
     *
     * @see AuditLog::create()
     */
    public function store(array $data): array
    {
        // N1 · comment noise: AuditLog::create() is named here only.
        $labels = ['Order::create', 'App\\Models\\AuditLog::create', Order::class];

        $total = OrderCalculator::total($data['lines']);     // P1 · resolving member
        $order = Order::create($data + ['total' => $total]); // P2 · unresolved member
        $coupon = Coupon::create(['code' => 'X']);           // V1 · declared in a file this diff creates

        return [$order, $coupon, $labels];
    }
}
