<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('vehicles.view');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermission('vehicles.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('vehicles.edit');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermission('vehicles.edit');
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermission('vehicles.edit');
    }

    public function restore(User $user, Vehicle $vehicle): bool
    {
        return $user->canManageUsers();
    }

    public function forceDelete(User $user, Vehicle $vehicle): bool
    {
        return $user->canManageUsers();
    }
}
