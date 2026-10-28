<?php

namespace Database\Factories;

use App\Enums\HandledBy;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Models\Asset;
use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Maintenance>
 */
class MaintenanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_number' => 'MT-2026-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'asset_id' => Asset::factory(),
            'plan_id' => null,
            'type' => MaintenanceType::Repair,
            'status' => MaintenanceStatus::Reported,
            'priority' => MaintenancePriority::Medium,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'handled_by' => HandledBy::Internal,
            'supplier_id' => null,
            'reported_by_employee_id' => null,
            'estimated_cost' => fake()->numberBetween(100_000, 3_000_000),
            'actual_cost' => null,
            'start_date' => now()->toDateString(),
            'target_date' => now()->addWeek()->toDateString(),
            'completed_at' => null,
            'asset_status_before' => null,
            'created_by' => null,
        ];
    }
}
