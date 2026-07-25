<?php

namespace App\Policies;

use App\Models\Transfer;
use App\Models\User;

class TransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('transfers.view');
    }

    public function view(User $user, Transfer $transfer): bool
    {
        return $user->hasPermission('transfers.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('transfers.edit');
    }

    public function update(User $user, Transfer $transfer): bool
    {
        return $user->hasPermission('transfers.edit');
    }

    public function delete(User $user, Transfer $transfer): bool
    {
        return $user->hasPermission('transfers.edit');
    }
}
