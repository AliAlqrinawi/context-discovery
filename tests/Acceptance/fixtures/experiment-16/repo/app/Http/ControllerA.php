<?php

namespace App\Http;

use App\Models\Order;
use App\Services\Alpha;
use App\Services\Calc;
use App\Services\Formatter;
use Illuminate\Support\Facades\Log;

class ControllerA
{
    public function untouched(): string
    {
        return 'unchanged';
    }

    public function helper(int $n): int
    {
        return $n + 1;
    }

    public function store(array $lines): string
    {
        $a = Calc::total($lines);        // R1/R3 · first request
        $b = Calc::total($lines);        // R3 · SECOND request, same changed file
        $c = Formatter::total($a + $b);  // R5 · same member NAME, different path
        Alpha::run($lines);              // R7 · body byte-identical to Beta::run
        Order::create(['sku' => 'x']);   // R10 · M14 shape, first origin
        Log::info('stored');             // R9 · framework-known, first origin
        $this->helper($a);               // R6 · same-file sibling, ALSO named from ControllerB
        Auditor::note($c);               // R8 · NOT imported — an absence, so the `use` BLOCK is sliced

        return $c;
    }
}
