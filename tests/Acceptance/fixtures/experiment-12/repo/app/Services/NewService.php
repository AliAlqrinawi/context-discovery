<?php

namespace App\Services;

class NewService
{
    public const MODE = 'new';

    public static function run(string $input): string
    {
        return 'new:' . $input;
    }
}
