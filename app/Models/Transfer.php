<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $table = 'activities';

    protected $fillable = [
        'booking_id',
        'title',
        'type',
        'status',
        'starts_at',
        'ends_at',
        'start_location_id',
        'end_location_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeTransfers($query)
    {
        return $query->where('type', 'transfer');
    }
}
