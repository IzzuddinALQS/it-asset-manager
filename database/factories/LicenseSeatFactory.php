<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\License;
use App\Models\LicenseSeat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LicenseSeat>
 */
class LicenseSeatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'employee_id' => Employee::factory(),
            'asset_id' => null,
            'assigned_at' => now(),
            'assigned_by' => null,
            'released_at' => null,
            'released_by' => null,
        ];
    }
}
