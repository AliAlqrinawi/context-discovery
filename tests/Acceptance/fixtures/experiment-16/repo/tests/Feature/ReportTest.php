<?php

namespace Tests\Feature;

use App\Services\Calc;

class ReportTest
{
    public function untouched(): string
    {
        return 'unchanged';
    }

    public function test_it_totals(): void
    {
        Calc::total([1, 2]);   // R2 · a THIRD origin, declared dev by composer
    }
}
