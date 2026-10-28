<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Asset $asset): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === Role::Admin;
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->role === Role::Admin;
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->role === Role::Admin;
    }

    public function checkout(User $user, Asset $asset): bool
    {
        return $user->role->canOperate();
    }

    public function checkin(User $user, Asset $asset): bool
    {
        return $user->role->canOperate();
    }

    public function tag(User $user, Asset $asset): bool
    {
        return $user->role->canOperate();
    }

    public function retire(User $user, Asset $asset): bool
    {
        return $user->role === Role::Admin;
    }
}
