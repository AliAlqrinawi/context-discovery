<?php

namespace App;

use Acme\Lib\Thing;
use App\Foo\Bar\Deep;
use App\Ghost\Missing;
use App\Models\Package;
use App\Support\Helper;
use Illuminate\Support\Facades\Log;
use MissingNamespace\Ghost;

class Consumer
{
    public function existing(): string
    {
        return 'unchanged';
    }

    public function exercise(string $input): array
    {
        return [
            'prefix_no_file'   => Missing::create($input),   // M10.1  App\ is mapped; the file is not there
            'no_prefix'        => Ghost::create($input),     // M10.2  nothing maps MissingNamespace\
            'both_present'     => Helper::run($input),       // M10.3  mapped and present
            'other_prefix'     => Thing::make($input),       // M10.4  Acme\Lib\ is mapped; the file is not there
            'nested'           => Deep::go($input),          // M10.5  nested under a mapped prefix
            'member_missing'   => Package::activatte($input),// M10.6  class present, member absent
            'framework'        => Log::info($input),         // M10.8  framework-known
        ];
    }
}
