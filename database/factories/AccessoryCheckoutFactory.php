<?php

namespace Database\Factories;

use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessoryCheckout>
 */
class AccessoryCheckoutFactory extends Factory
{
    public function definition(): array
    {
        return [
            'accessory_id' => Accessory::factory(),
            'employee_id' => Employee::factory(),
            'qty' => 1,
            'checked_out_at' => now(),
            'checked_out_by' => null,
            'checked_in_at' => null,
            'checked_in_by' => null,
            'note' => null,
        ];
    }
}
