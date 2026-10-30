<?php

use App\Enums\CategoryType;
use App\Enums\MaintenanceStatus;
use App\Enums\Role;
use App\Http\Controllers\Api\EmployeeSearchController;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Component;
use App\Models\Employee;
use App\Models\Maintenance;
use App\Models\Supplier;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it('finds active employees by name or number and leaves out resigned ones', function (): void {
    $match = Employee::factory()->create(['name' => 'Helmi Yusuf', 'employee_number' => 'EMP-9001']);
    Employee::factory()->resigned()->create(['name' => 'Helmi Lama']);
    Employee::factory()->create(['name' => 'Andi Saputra']);

    actingAs(userWithRole(Role::Technician))
        ->getJson('/api/employees/search?q=helmi')
        ->assertOk()
        ->assertExactJson(['data' => [[
            'value' => (string) $match->id,
            'label' => 'Helmi Yusuf',
            'hint' => collect(['EMP-9001', $match->department?->name])->filter()->implode(' · '),
        ]]]);

    actingAs(userWithRole(Role::Technician))
        ->getJson('/api/employees/search?q=9001')
        ->assertJsonPath('data.0.value', (string) $match->id);
});

it('caps employee results so large directories stay fast', function (): void {
    Employee::factory()->count(EmployeeSearchController::LIMIT + 5)->create();

    actingAs(userWithRole(Role::Admin))
        ->getJson('/api/employees/search')
        ->assertJsonCount(EmployeeSearchController::LIMIT, 'data');
});

it('can skip employees that already have an account', function (): void {
    $withAccount = User::factory()->create(['role' => Role::Viewer])->employee;
    $withoutAccount = Employee::factory()->create();

    $ids = actingAs(userWithRole(Role::Admin))
        ->getJson('/api/employees/search?without_user=1&limit=100')
        ->json('data.*.value');

    expect($ids)->toContain((string) $withoutAccount->id)
        ->not->toContain((string) $withAccount->id);
});

it('finds assets by tag and leaves out retired ones', function (): void {
    $match = Asset::factory()->create(['asset_tag' => 'LT-77001']);
    Asset::factory()->retired()->create(['asset_tag' => 'LT-77002']);

    actingAs(userWithRole(Role::Technician))
        ->getJson('/api/assets/search?q=77')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', (string) $match->id);
});

it('requires a signed-in user', function (): void {
    getJson('/api/employees/search')->assertUnauthorized();
    getJson('/api/assets/search')->assertUnauthorized();
});

it('searches master data by name and resolves a saved value by id', function (): void {
    $vendor = Supplier::factory()->create(['name' => 'Servis Jaya', 'is_service_vendor' => true]);
    Supplier::factory()->create(['name' => 'Toko Biasa', 'is_service_vendor' => false]);

    actingAs(userWithRole(Role::Technician))
        ->getJson('/api/options/suppliers?q=jaya')
        ->assertExactJson(['data' => [['value' => (string) $vendor->id, 'label' => 'Servis Jaya', 'hint' => 'Vendor servis']]]);

    actingAs(userWithRole(Role::Technician))
        ->getJson("/api/options/suppliers?id={$vendor->id}")
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.label', 'Servis Jaya');
});

it('filters categories by type', function (): void {
    $assetCategory = Category::factory()->create(['type' => CategoryType::Asset, 'name' => 'Tablet']);
    Category::factory()->create(['type' => CategoryType::Accessory, 'name' => 'Tablet Case']);

    actingAs(userWithRole(Role::Admin))
        ->getJson('/api/options/categories?category_type=asset&q=tablet')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', (string) $assetCategory->id);
});

it('reports available stock with each component', function (): void {
    $component = Component::factory()->create(['name' => 'RAM 16GB', 'qty_total' => 4]);

    actingAs(userWithRole(Role::Technician))
        ->getJson('/api/options/components?q=ram')
        ->assertJsonPath('data.0.value', (string) $component->id)
        ->assertJsonPath('data.0.meta.qty_available', 4);
});

it('only offers assets without an open ticket when picking for maintenance', function (): void {
    $free = Asset::factory()->create(['asset_tag' => 'MT-0001']);
    $busy = Asset::factory()->create(['asset_tag' => 'MT-0002']);
    Maintenance::factory()->for($busy)->create(['status' => MaintenanceStatus::InProgress]);

    actingAs(userWithRole(Role::Technician))
        ->getJson('/api/assets/search?for=maintenance&q=MT-')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', (string) $free->id);
});

it('rejects unknown option sources', function (): void {
    actingAs(userWithRole(Role::Admin))->getJson('/api/options/users')->assertNotFound();
});
