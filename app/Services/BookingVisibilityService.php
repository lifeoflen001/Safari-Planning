<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BookingVisibilityService
{
    public function apply(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('users.manage')) {
            return $query;
        }

        $query->where(function (Builder $visibility) use ($user): void {
            if ($user->record_scope === 'own') {
                $visibility->where('created_by', $user->id);
            } elseif ($user->record_scope === 'assigned') {
                $visibility->where('visible_to_user_id', $user->id);
            } else {
                $visibility->where('visibility', 'workspace')
                    ->orWhere('visible_to_user_id', $user->id);
            }
        });

        if ($user->company_id !== null) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }
}
