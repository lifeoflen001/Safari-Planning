<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Guide extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['languages' => 'array', 'license_expiry' => 'date'];
    }

    public function activities(): HasManyThrough
    {
        return $this->hasManyThrough(Activity::class, ActivityAssignment::class);
    }
}
