<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAssignment extends Model
{
    protected $guarded = [];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function translator(): BelongsTo
    {
        return $this->belongsTo(Translator::class);
    }
}
