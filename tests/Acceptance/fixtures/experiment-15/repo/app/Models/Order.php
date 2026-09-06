<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['sku', 'total', 'status'];

    protected $casts = ['total' => 'decimal:2'];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
