<?php

namespace Database\Factories;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\Alert;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => AlertType::WarrantyExpiring,
            'severity' => AlertSeverity::Warning,
            'subject_type' => 'asset',
            'subject_id' => Asset::factory(),
            'message' => fake()->sentence(),
            'due_date' => now()->addDays(14)->toDateString(),
            'resolved_at' => null,
            'resolved_by' => null,
        ];
    }
}
