<?php

namespace App\Support;

class Helper
{
    /** Declared here. The control for "the member really is in this file". */
    public static function run(string $input): string
    {
        return trim($input);
    }

    /** A project member whose name is one of Laravel's most recognisable. Ownership decides. */
    public static function slug(string $title): string
    {
        return strtolower(str_replace(' ', '-', $title));
    }
}
