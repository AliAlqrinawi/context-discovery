<?php

namespace App\Support;

/**
 * Dynamic dispatch, reduced to its mechanism. `create()` is declared nowhere: it exists only
 * because __callStatic forwards it. This is Eloquent's shape without Laravel installed, which is
 * what keeps the PHP-inheritance question separate from the framework question.
 */
abstract class BaseModel
{
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return (new static())->{$method}(...$arguments);
    }
}
