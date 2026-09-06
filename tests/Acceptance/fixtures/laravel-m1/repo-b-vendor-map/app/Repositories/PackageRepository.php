<?php

namespace App\Repositories;

use App\Contracts\MissingGateway;
use App\Models\Package;
use App\Services\Registry;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PackageRepository
{
    public function count(): int
    {
        return 0;
    }

    public function audit(string $action): void
    {
        Log::info('package.audit', ['action' => $action]);

        DB::transaction(function () use ($action): void {
            Log::info('package.audit.committed', ['action' => $action]);
        });
    }

    public function store(array $data): Package
    {
        return Package::create($data);
    }

    public function listing(): Collection
    {
        Package::orderBy('position')->get();

        return Package::where('is_active', true)->orderBy('position')->get();
    }

    public function withFeatures(Package $package, array $rows): Package
    {
        $package->features()->createMany($rows);

        return $package->fresh('features');
    }

    public function newQuery(): object
    {
        return Package::query();
    }

    public function normalise(array $data): array
    {
        $data['slug'] = Str::slug($data['name']);

        return Arr::only($data, ['name', 'slug', 'price']);
    }

    public function labelled(): Registry
    {
        Package::active()->get();

        return Registry::create(['catering' => 'Catering']);
    }

    public function reconcile(string $reference): void
    {
        MissingGateway::resolve($reference);

        Package::activatte($reference);
    }
}
