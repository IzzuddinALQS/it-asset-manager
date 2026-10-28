<?php

namespace Database\Factories;

use App\Models\HerdSyncRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HerdSyncRun>
 */
class HerdSyncRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
            'status' => 'success',
            'created_count' => 0,
            'updated_count' => 0,
            'offboarding_count' => 0,
            'error_message' => null,
            'triggered_by' => null,
        ];
    }
}
