<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Accessory;
use App\Models\User;

class AccessoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Accessory $accessory): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === Role::Admin;
    }

    public function update(User $user, Accessory $accessory): bool
    {
        return $user->role === Role::Admin;
    }

    public function delete(User $user, Accessory $accessory): bool
    {
        return $user->role === Role::Admin;
    }

    /**
     * Covers both lending accessories and taking them back.
     */
    public function checkout(User $user, Accessory $accessory): bool
    {
        return $user->role->canOperate();
    }
}
