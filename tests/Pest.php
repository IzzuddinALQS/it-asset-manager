<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/**
 * The test's user for a role, created on first use and reused afterwards.
 */
function userWithRole(Role $role): User
{
    return User::query()->where('role', $role)->first() ?? User::factory()->create(['role' => $role]);
}
