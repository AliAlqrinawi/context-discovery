<?php

namespace App\Services;

final class OrderCalculator
{
    public static function total(array $lines): int
    {
        return array_sum($lines);
    }

    public static function subtotal(array $lines): int
    {
        return array_sum($lines) - 1;
    }
}
