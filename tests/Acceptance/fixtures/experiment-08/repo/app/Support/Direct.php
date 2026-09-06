<?php

namespace App\Support;

class Direct
{
    /** Declared here. The control: this is what already works. */
    public static function run(string $input): string
    {
        return trim($input);
    }

    /** A project member whose name resembles a framework helper. Ownership, not the name, decides. */
    public static function slug(string $title): string
    {
        return strtolower(str_replace(' ', '-', $title));
    }
}
