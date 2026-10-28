<?php

namespace App\Policies;

use App\Models\MaintenancePlan;
use App\Models\User;

class MaintenancePlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role->canOperate();
    }

    public function update(User $user, MaintenancePlan $plan): bool
    {
        return $user->role->canOperate();
    }

    /**
     * Opening preventive tickets for every covered asset.
     */
    public function run(User $user, MaintenancePlan $plan): bool
    {
        return $user->role->canOperate();
    }
}
