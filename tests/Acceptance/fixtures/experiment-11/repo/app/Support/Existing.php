<?php

namespace App\Support;

use App\Models\Package;

class Existing
{
    public function untouched(): string
    {
        return 'unchanged';
    }

    public function helper(Package $package): string
    {
        return $package->label();
    }

    public function extra(Package $package): string
    {
        return $this->helper($package);
    }
}
