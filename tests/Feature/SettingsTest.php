<?php

use App\Enums\CategoryType;
use App\Enums\EmploymentStatus;
use App\Enums\MasterDataType;
use App\Enums\Role;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Manufacturer;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('shows every user their own profile', function (Role $role): void {
    $user = User::factory()->create(['role' => $role]);
    Asset::factory()->deployed()->create(['assigned_employee_id' => $user->employee_id]);

    actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Profile')
            ->where('profile.role_label', $role->label())
            ->where('profile.holdings.assets', 1));
})->with(Role::cases());

it('gives an active employee access with a role', function (): void {
    $employee = Employee::factory()->create();

    actingAs(userWithRole(Role::Admin))
        ->post('/settings/users', ['employee_id' => $employee->id, 'role' => 'technician'])
        ->assertSessionHasNoErrors();

    expect($employee->user()->sole()->role)->toBe(Role::Technician);
});

it('refuses access for inactive employees and employees who already have it', function (): void {
    $admin = userWithRole(Role::Admin);
    $resigned = Employee::factory()->create(['employment_status' => EmploymentStatus::Resigned]);

    actingAs($admin)
        ->post('/settings/users', ['employee_id' => $resigned->id, 'role' => 'viewer'])
        ->assertSessionHasErrors(['employee_id' => 'Hanya karyawan aktif yang bisa diberi akses.']);

    actingAs($admin)
        ->post('/settings/users', ['employee_id' => $admin->employee_id, 'role' => 'viewer'])
        ->assertSessionHasErrors(['employee_id' => 'Karyawan ini sudah punya akses.']);
});

it('changes and revokes other users but never the admin themself', function (): void {
    $admin = userWithRole(Role::Admin);
    $viewer = User::factory()->create(['role' => Role::Viewer]);

    actingAs($admin)->put("/settings/users/{$viewer->id}", ['role' => 'admin'])->assertSessionHas('success');
    expect($viewer->fresh()->role)->toBe(Role::Admin);

    actingAs($admin)->put("/settings/users/{$admin->id}", ['role' => 'viewer'])->assertSessionHas('error');
    actingAs($admin)->delete("/settings/users/{$admin->id}")->assertSessionHas('error');
    expect($admin->fresh()->role)->toBe(Role::Admin);

    actingAs($admin)->delete("/settings/users/{$viewer->id}")->assertSessionHas('success');
    expect($viewer->fresh())->toBeNull();
});

it('lists each type of master data', function (MasterDataType $type): void {
    actingAs(userWithRole(Role::Admin))
        ->get("/settings/master/{$type->value}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/MasterData')
            ->where('type', $type->value)
            ->has('fields'));
})->with(MasterDataType::cases());

it('creates, updates and deletes master data', function (): void {
    $admin = userWithRole(Role::Admin);
    $category = Category::factory()->create(['type' => CategoryType::Asset]);

    actingAs($admin)
        ->post('/settings/master/models', ['name' => 'Latitude 5450', 'category_id' => $category->id, 'depreciation_months' => 36])
        ->assertSessionHasNoErrors();

    $model = AssetModel::query()->where('name', 'Latitude 5450')->sole();

    actingAs($admin)
        ->put("/settings/master/models/{$model->id}", ['name' => 'Latitude 5460', 'category_id' => $category->id])
        ->assertSessionHasNoErrors();
    expect($model->fresh()->name)->toBe('Latitude 5460');

    actingAs($admin)
        ->post('/settings/master/categories', ['name' => 'Monitor', 'type' => 'asset', 'tag_prefix' => 'mn'])
        ->assertSessionHasNoErrors();
    expect(Category::query()->where('name', 'Monitor')->sole()->tag_prefix)->toBe('MN');

    $manufacturer = Manufacturer::factory()->create();
    actingAs($admin)->delete("/settings/master/manufacturers/{$manufacturer->id}")->assertSessionHas('success');
    expect($manufacturer->fresh())->toBeNull();
});

it('rejects duplicate names and models outside asset categories', function (): void {
    $admin = userWithRole(Role::Admin);
    Manufacturer::factory()->create(['name' => 'Dell']);

    actingAs($admin)->post('/settings/master/manufacturers', ['name' => 'Dell'])->assertSessionHasErrors('name');

    actingAs($admin)
        ->post('/settings/master/models', ['name' => 'X', 'category_id' => Category::factory()->accessory()->create()->id])
        ->assertSessionHasErrors('category_id');
});

it('keeps master data that is still in use', function (): void {
    $model = AssetModel::factory()->create();
    Asset::factory()->for($model)->create();

    actingAs(userWithRole(Role::Admin))
        ->delete("/settings/master/categories/{$model->category_id}")
        ->assertSessionHas('error');

    expect(Category::query()->find($model->category_id))->not->toBeNull();
});

it('limits user and master data management to admins', function (Role $role): void {
    $user = userWithRole($role);

    actingAs($user)->get('/settings/master/categories')->assertForbidden();
    actingAs($user)->post('/settings/master/manufacturers', ['name' => 'Lenovo'])->assertForbidden();
    actingAs($user)->post('/settings/users', ['employee_id' => Employee::factory()->create()->id, 'role' => 'admin'])->assertForbidden();
})->with([Role::Technician, Role::Viewer]);

it('returns not found for an unknown master data type', function (): void {
    actingAs(userWithRole(Role::Admin))->get('/settings/master/widgets')->assertNotFound();
});
