<?php

namespace Database\Factories;

use App\Enums\AssetAction;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetLog>
 */
class AssetLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'action' => AssetAction::Updated,
            'from_status' => null,
            'to_status' => null,
            'employee_id' => null,
            'location_id' => null,
            'note' => null,
            'meta' => null,
            'performed_by' => null,
            'created_at' => now(),
        ];
    }

    public function checkout(): static
    {
        return $this->state(fn () => [
            'action' => AssetAction::Checkout,
            'from_status' => AssetStatus::Ready,
            'to_status' => AssetStatus::Deployed,
        ]);
    }
}
