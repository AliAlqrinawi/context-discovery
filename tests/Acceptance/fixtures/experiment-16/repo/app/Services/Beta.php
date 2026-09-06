<?php

namespace App\Services;

final class Beta
{
    public static function run(array $rows): int
    {
        return count($rows);
    }
}
