<?php

namespace App\Services;

class HiddenService
{
    public static function run(string $input): string
    {
        return 'hidden:' . $input;
    }

    public function filler1(): string
    {
        return '1';
    }

    public function filler2(): string
    {
        return '2';
    }

    public function filler3(): string
    {
        return '3';
    }

    public function touched(): string
    {
        return 'changed';
    }
}
