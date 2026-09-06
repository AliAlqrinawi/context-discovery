<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\Order;
use App\Services\OrderCalculator;

class OrderTest
{
    public function existing(): string
    {
        return 'unchanged';
    }

    /**
     * N1 · docblock noise in a TEST file: Order::create() and AuditLog::create() are prose.
     */
    public function test_it_stores_an_order(): void
    {
        // N1 · comment noise in a test: AuditLog::create() is named here only.
        $labels = ['AuditLog::create', 'App\\Models\\Order::create', AuditLog::class];

        OrderCalculator::total([1, 2]);                       // T1a · same member as P1
        OrderCalculator::subtotal([1, 2]);                    // T1b · test-only resolving member
        Order::create(['sku' => 'A', 'total' => 1]);          // T2 · class also used from production
        AuditLog::create(['action' => 'stored']);             // T3 · class used ONLY from a test
        Coupon::create(['code' => 'X']);                      // V1 · created file, from a test

        $this->assertSame([], $labels);
    }

    private function assertSame(array $a, array $b): void
    {
    }
}
