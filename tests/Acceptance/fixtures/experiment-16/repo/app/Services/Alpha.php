<?php

namespace App\Services;

final class Alpha
{
    public static function run(array $rows): int
    {
        return count($rows);
    }
}
