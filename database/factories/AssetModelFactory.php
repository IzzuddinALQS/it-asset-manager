<?php

namespace Database\Factories;

use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetModel>
 */
class AssetModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'model_number' => strtoupper(fake()->bothify('??-####')),
            'manufacturer_id' => Manufacturer::factory(),
            'category_id' => Category::factory(),
            'depreciation_months' => 48,
        ];
    }
}
