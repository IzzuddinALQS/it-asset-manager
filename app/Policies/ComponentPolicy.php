<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Component;
use App\Models\User;

class ComponentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Component $component): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === Role::Admin;
    }

    public function update(User $user, Component $component): bool
    {
        return $user->role === Role::Admin;
    }

    public function delete(User $user, Component $component): bool
    {
        return $user->role === Role::Admin;
    }

    /**
     * Covers both installing parts into an asset and removing them again.
     */
    public function install(User $user, ?Component $component = null): bool
    {
        return $user->role->canOperate();
    }
}
