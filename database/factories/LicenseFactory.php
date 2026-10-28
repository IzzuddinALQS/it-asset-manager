<?php

namespace Database\Factories;

use App\Enums\LicenseType;
use App\Models\Category;
use App\Models\License;
use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<License>
 */
class LicenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'manufacturer_id' => Manufacturer::factory(),
            'category_id' => Category::factory()->license(),
            'license_type' => LicenseType::Subscription,
            'product_key' => strtoupper(fake()->bothify('XXXX-XXXX-XXXX-####')),
            'seats_total' => fake()->numberBetween(5, 50),
            'supplier_id' => null,
            'purchase_order' => fake()->bothify('PO-2026-####'),
            'purchase_date' => now()->subMonths(6)->toDateString(),
            'annual_cost' => fake()->numberBetween(2_000_000, 50_000_000),
            'expires_at' => now()->addMonths(fake()->numberBetween(1, 18))->toDateString(),
            'notes' => null,
        ];
    }
}
