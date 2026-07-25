<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'email_notifications' => 'boolean',
            'conflict_alerts' => 'boolean',
            'payment_reminders' => 'boolean',
            'assignment_updates' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
