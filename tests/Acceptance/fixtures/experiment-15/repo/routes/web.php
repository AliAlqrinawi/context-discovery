<?php

use App\Models\Order;

// R1 · a file no PSR-4 section maps at all.
$count = Order::whereActive()->count();
