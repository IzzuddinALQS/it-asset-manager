<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Maintenance;
use App\Models\User;

class MaintenancePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Maintenance $maintenance): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role->canOperate();
    }

    public function update(User $user, Maintenance $maintenance): bool
    {
        return $user->role->canOperate();
    }

    public function delete(User $user, Maintenance $maintenance): bool
    {
        return $user->role === Role::Admin;
    }
}
