<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Component;
use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Component>
 */
class ComponentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['RAM DDR4 8 GB', 'SSD NVMe 512 GB', 'Baterai Laptop', 'Power Supply 500W', 'Keyboard Mechanical']).' '.fake()->unique()->numerify('##'),
            'spec' => fake()->words(3, true),
            'category_id' => Category::factory()->component(),
            'manufacturer_id' => Manufacturer::factory(),
            'qty_total' => fake()->numberBetween(5, 40),
            'min_qty' => 3,
            'unit_cost' => fake()->numberBetween(200_000, 2_500_000),
            'location_id' => null,
        ];
    }
}
