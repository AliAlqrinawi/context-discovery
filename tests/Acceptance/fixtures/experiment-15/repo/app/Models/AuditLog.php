<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['action', 'actor_id'];

    protected $casts = ['created_at' => 'datetime'];
}
