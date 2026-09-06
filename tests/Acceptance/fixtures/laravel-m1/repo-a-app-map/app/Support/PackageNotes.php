<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Documentation-only mentions of framework members. Nothing here calls them.
 */
final class PackageNotes
{
    /**
     * Historically this ran Log::info() on every write and used Package::create() directly.
     *
     * @see Log::warning()
     *
     * @return list<string>
     */
    public function notes(): array
    {
        // Package::create() was replaced by the repository, and Log::info() moved to audit().
        return [
            'Log::info',
            'Package::create',
            'Illuminate\\Support\\Facades\\Cache::lock',
        ];
    }

    public function channel(): string
    {
        return Log::class;
    }
}
