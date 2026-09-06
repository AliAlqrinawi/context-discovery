<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    protected $fillable = ['label'];

    public function label(): string
    {
        return (string) $this->label;
    }
}
