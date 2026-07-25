<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewPlanner();
    }

    public function view(User $user, Booking $booking): bool
    {
        if (! $user->canViewPlanner()) {
            return false;
        }

        return $user->hasPermission('users.manage')
            || ($user->record_scope === 'own' && $booking->created_by === $user->id)
            || ($user->record_scope === 'assigned' && $booking->visible_to_user_id === $user->id)
            || ($user->record_scope === 'workspace' && ($booking->visibility === 'workspace' || $booking->visible_to_user_id === $user->id));
    }

    public function create(User $user): bool
    {
        return $user->canEditPlanner();
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->hasPermission('planner.edit');
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->hasPermission('planner.edit');
    }

    public function comment(User $user, Booking $booking): bool
    {
        return $user->canCommentPlanner();
    }
}
