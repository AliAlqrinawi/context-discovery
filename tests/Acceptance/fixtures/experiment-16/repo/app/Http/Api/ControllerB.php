<?php

namespace App\Http\Api;

use App\Http\ControllerA;
use App\Models\Order;
use App\Services\Beta;
use App\Services\Calc;
use Illuminate\Support\Facades\Log;

class ControllerB
{
    public function untouched(): string
    {
        return 'unchanged';
    }

    public function report(array $lines): int
    {
        $a = Calc::total($lines);         // R1 · SECOND production origin, same path+member
        $b = Calc::subtotal($lines);      // R4 · same path, DIFFERENT member
        Beta::run($lines);                // R7 · body byte-identical to Alpha::run
        Order::create(['sku' => 'y']);    // R10 · M14 shape, second origin
        Log::info('reported');            // R9 · framework-known, second origin
        ControllerA::helper($a);          // R6 · the SAME span ControllerA reaches as a sibling

        return $a + $b;
    }
}
