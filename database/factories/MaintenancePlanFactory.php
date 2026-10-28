<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\MaintenancePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenancePlan>
 */
class MaintenancePlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'category_id' => Category::factory(),
            'location_id' => null,
            'interval_months' => fake()->randomElement([3, 6, 12]),
            'next_due_date' => now()->addDays(fake()->numberBetween(3, 60))->toDateString(),
            'pic' => fake()->name(),
            'supplier_id' => null,
            'is_active' => true,
        ];
    }
}
