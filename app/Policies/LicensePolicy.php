<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\License;
use App\Models\User;

class LicensePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, License $license): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === Role::Admin;
    }

    public function update(User $user, License $license): bool
    {
        return $user->role === Role::Admin;
    }

    public function delete(User $user, License $license): bool
    {
        return $user->role === Role::Admin;
    }

    public function assignSeat(User $user, License $license): bool
    {
        return $user->role->canOperate();
    }
}
