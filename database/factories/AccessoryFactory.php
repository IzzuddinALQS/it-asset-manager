<?php

namespace Database\Factories;

use App\Models\Accessory;
use App\Models\Category;
use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Accessory>
 */
class AccessoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Mouse Wireless', 'Keyboard USB', 'Headset', 'Docking Station', 'Kabel HDMI', 'Webcam']).' '.fake()->unique()->numerify('##'),
            'model' => strtoupper(fake()->bothify('ACC-####')),
            'category_id' => Category::factory()->accessory(),
            'manufacturer_id' => Manufacturer::factory(),
            'qty_total' => fake()->numberBetween(5, 50),
            'min_qty' => 5,
            'unit_cost' => fake()->numberBetween(80_000, 1_200_000),
            'location_id' => null,
        ];
    }
}
