<?php

namespace App\Services;

/**
 * Docblock noise: NewService::run() and Untouched::go() are prose here.
 *
 * @see HiddenService::run()
 */
class Noisy
{
    public static function shout(): string
    {
        // Comment noise: VisibleService::run() and Untouched::go() are named here only.
        $names = ['NewService::run', 'App\\Services\\Untouched::go', Noisy::class];

        return implode(',', $names);
    }
}
