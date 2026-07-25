<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translator extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['languages' => 'array'];
    }
}
