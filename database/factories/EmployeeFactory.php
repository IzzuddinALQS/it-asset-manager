<?php

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'herd_id' => 'HERD-'.fake()->unique()->numerify('######'),
            'employee_number' => fake()->unique()->numerify('NIK#####'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'job_title' => fake()->jobTitle(),
            'department_id' => Department::factory(),
            'location_id' => Location::factory(),
            'manager_id' => null,
            'employment_status' => EmploymentStatus::Active,
            'status_effective_date' => null,
            'synced_at' => now(),
        ];
    }

    public function resigned(): static
    {
        return $this->state(fn () => [
            'employment_status' => EmploymentStatus::Resigned,
            'status_effective_date' => now()->subWeek()->toDateString(),
        ]);
    }
}
