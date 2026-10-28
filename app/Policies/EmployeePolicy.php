<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

/**
 * Employees are synced from HERD and read-only inside this application.
 */
class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Employee $employee): bool
    {
        return true;
    }
}
