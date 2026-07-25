<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Vehicle extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_service_at' => 'date', 'next_service_at' => 'date'];
    }

    public function activities(): HasManyThrough
    {
        return $this->hasManyThrough(Activity::class, ActivityAssignment::class);
    }
}
