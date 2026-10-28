<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Component;
use App\Models\ComponentInstallation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComponentInstallation>
 */
class ComponentInstallationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'component_id' => Component::factory(),
            'asset_id' => Asset::factory(),
            'qty' => 1,
            'is_factory' => false,
            'maintenance_id' => null,
            'installed_at' => now(),
            'installed_by' => null,
            'removed_at' => null,
            'removed_by' => null,
        ];
    }
}
