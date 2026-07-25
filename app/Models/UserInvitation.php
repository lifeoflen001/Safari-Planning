<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvitation extends Model
{
    protected $fillable = [
        'user_id', 'invited_by', 'email', 'token_hash', 'expires_at', 'email_sent_at',
        'email_delivery_status', 'email_delivery_error', 'invitation_opened_at',
        'invitation_accepted_at', 'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime', 'email_sent_at' => 'datetime',
            'invitation_opened_at' => 'datetime', 'invitation_accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function inviter(): BelongsTo { return $this->belongsTo(User::class, 'invited_by'); }

    public function isValid(): bool
    {
        return ! $this->revoked_at && ! $this->invitation_accepted_at && $this->expires_at?->isFuture();
    }
}
