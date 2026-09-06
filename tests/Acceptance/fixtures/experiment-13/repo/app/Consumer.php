<?php

namespace App;

use App\Contracts\MissingGateway;
use App\Models\Package;
use App\Models\Setting;
use App\Models\Widget;
use App\Services\Registry;
use Illuminate\Support\Facades\Log;

class Consumer
{
    public function existing(): string
    {
        return 'unchanged';
    }

    /**
     * H · docblock noise: Package::where() and Illuminate\Support\Facades\Log::info() are prose.
     *
     * @see Package::create()
     */
    public function exercise(array $data, Widget $widget): ?Widget
    {
        // H · comment noise: Package::create() and Setting::updateOrCreate() are named here only.
        $names = ['Package::where', 'Package::create', 'Illuminate\\Support\\Facades\\Log', Package::class];

        Package::where('slug', $data['slug']);                  // A · static call only
        Package::create($data);                                 // B · create
        Setting::updateOrCreate(['key' => 'k'], $data);         // B · updateOrCreate, second model
        Package::query();                                       // C · query

        Registry::create($names);                               // E · project class, same member name
        MissingGateway::resolve('x');                           // F · unknown class
        Log::info('done');                                      // G · framework facade

        return new Widget();                                    // D · existing form, different model
    }
}
