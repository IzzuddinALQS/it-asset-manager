<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'role' => Role::Viewer,
            'last_login_at' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => Role::Admin]);
    }

    public function technician(): static
    {
        return $this->state(fn () => ['role' => Role::Technician]);
    }
}
