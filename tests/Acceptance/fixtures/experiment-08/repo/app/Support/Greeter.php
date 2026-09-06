<?php

namespace App\Support;

trait Greeter
{
    public static function greet(string $name): string
    {
        return 'hello ' . $name;
    }

    public function instanceGreet(string $name): string
    {
        return self::greet($name);
    }
}
