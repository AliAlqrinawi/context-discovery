<?php

namespace App\Services;

final class Formatter
{
    public static function total(int $cents): string
    {
        return number_format($cents / 100, 2);
    }
}
