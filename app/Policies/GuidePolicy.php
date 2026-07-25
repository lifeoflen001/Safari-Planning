<?php

namespace App\Policies;

use App\Models\Guide;
use App\Models\User;

class GuidePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('guides.view');
    }

    public function view(User $user, Guide $guide): bool
    {
        return $user->hasPermission('guides.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('guides.edit');
    }

    public function update(User $user, Guide $guide): bool
    {
        return $user->hasPermission('guides.edit');
    }

    public function delete(User $user, Guide $guide): bool
    {
        return $user->hasPermission('guides.edit');
    }

    public function restore(User $user, Guide $guide): bool
    {
        return $user->canManageUsers();
    }

    public function forceDelete(User $user, Guide $guide): bool
    {
        return $user->canManageUsers();
    }
}
