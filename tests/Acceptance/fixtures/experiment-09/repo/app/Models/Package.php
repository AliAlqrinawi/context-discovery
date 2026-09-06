<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = ['name', 'is_active'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
