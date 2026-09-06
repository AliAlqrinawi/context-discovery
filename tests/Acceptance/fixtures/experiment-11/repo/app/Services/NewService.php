<?php

namespace App\Services;

use App\Models\Package;
use App\Support\Existing;
use Illuminate\Support\Facades\Log;

/**
 * A brand-new file. Everything below is visible in the diff that creates it, so nothing here is
 * off-diff context — but Package, Existing and Log are.
 *
 * Docblock noise: Package::create() and Log::warning() are mentioned here only as prose.
 */
class NewService
{
    private const LABEL = 'menu';

    public function store(array $data): void
    {
        // Comment noise: Existing::helper() and Package::where() are named here only.
        Log::info('storing', ['label' => self::LABEL]);

        Package::create($data);
        Package::where('slug', $data['slug'])->first();

        $this->audit($data);
    }

    private function audit(array $data): void
    {
        $names = ['Package::create', 'Illuminate\\Support\\Facades\\Log::info', Package::class];

        Log::info('audited', ['names' => $names, 'count' => count($data)]);
    }
}
