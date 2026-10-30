<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Enums\RetireReason;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asset_tag' => 'LT-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'serial_number' => strtoupper(fake()->unique()->bothify('SN-####-????')),
            'imei' => fake()->unique()->imei(),
            'phone_number' => '08'.fake()->unique()->numerify('##########'),
            'asset_model_id' => AssetModel::factory(),
            'status' => AssetStatus::Ready,
            'assigned_employee_id' => null,
            'assigned_location_id' => null,
            'assigned_at' => null,
            'expected_return_date' => null,
            'location_id' => null,
            'supplier_id' => null,
            'purchase_order' => fake()->bothify('PO-2026-####'),
            'purchase_date' => now()->subMonths(fake()->numberBetween(1, 40))->toDateString(),
            'purchase_cost' => fake()->numberBetween(5_000_000, 30_000_000),
            'warranty_until' => now()->addMonths(fake()->numberBetween(-6, 24))->toDateString(),
            'last_audited_at' => null,
            'notes' => null,
            'retired_at' => null,
            'retire_reason' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => AssetStatus::Pending, 'asset_tag' => 'TMP-'.fake()->unique()->numerify('#####')]);
    }

    public function deployed(): static
    {
        return $this->state(fn () => [
            'status' => AssetStatus::Deployed,
            'assigned_employee_id' => Employee::factory(),
            'assigned_at' => now()->subDays(fake()->numberBetween(1, 200)),
            'expected_return_date' => null,
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn () => ['status' => AssetStatus::Maintenance]);
    }

    public function retired(): static
    {
        return $this->state(fn () => [
            'status' => AssetStatus::Retired,
            'retired_at' => now()->subMonth(),
            'retire_reason' => RetireReason::EndOfLife,
        ]);
    }
}
