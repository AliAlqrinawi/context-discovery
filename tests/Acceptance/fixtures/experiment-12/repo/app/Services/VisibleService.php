<?php

namespace App\Services;

class VisibleService
{
    public function untouched(): string
    {
        return 'a';
    }

    public static function run(string $input): string
    {
        return 'visible!' . $input;
    }
}
