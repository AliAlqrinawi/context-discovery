<?php

namespace App;

use App\Ghost\Missing;
use App\Models\Package;
use App\Support\Helper;
use Illuminate\Support\Facades\Log;

class Consumer
{
    public function existing(): string
    {
        return 'unchanged';
    }

    /**
     * Docblock control: mentions Package::create() and Log::info() as prose only.
     *
     * @see Helper::slug()
     */
    public function exercise(string $input): array
    {
        // Comment control: Package::create() and Missing::create() are named here only.
        return [
            'declared'        => Helper::run($input),           // OQ6.1
            'unknown_member'  => Helper::nope($input),          // OQ6.2
            'eloquent'        => Package::create(['name' => $input]), // OQ6.3
            'typo'            => Package::activatte($input),    // OQ6.4
            'unknown_class'   => Missing::create($input),       // OQ6.5
            'lookalike'       => Helper::slug($input),          // OQ6.6
            'facade'          => Log::info($input),             // OQ6.7
            'model_unknown'   => Package::totallyUnknownThing($input), // OQ6.8
            'capitalisation'  => Helper::Run($input),           // control
            'string_literal'  => 'Package::create',             // control
            'class_constant'  => Package::class,                // control
        ];
    }
}
