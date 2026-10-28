<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'herd_id' => 'DEPT-'.fake()->unique()->numerify('####'),
            'name' => fake()->unique()->words(2, true),
            'parent_id' => null,
            'is_active' => true,
            'synced_at' => now(),
        ];
    }
}
