<?php

use App\Enums\EmploymentStatus;
use App\Enums\Role;
use App\Models\AccessoryCheckout;
use App\Models\Asset;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LicenseSeat;

use function Pest\Laravel\actingAs;

it('lists employees with what they currently hold', function (): void {
    $employee = Employee::factory()->create(['name' => 'Ani Wijaya']);
    Asset::factory()->deployed()->create(['assigned_employee_id' => $employee->id]);
    AccessoryCheckout::factory()->for($employee)->create();
    AccessoryCheckout::factory()->for($employee)->create(['checked_in_at' => now()]);
    LicenseSeat::factory()->for($employee)->create();

    actingAs(userWithRole(Role::Viewer))
        ->get('/directory?search=ani')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Directory/Index')
            ->has('employees.data', 1)
            ->where('employees.data.0.assets_count', 1)
            ->where('employees.data.0.accessories_count', 1)
            ->where('employees.data.0.licenses_count', 1));
});

it('filters the directory by status and department', function (): void {
    $finance = Department::factory()->create();
    Employee::factory()->create(['department_id' => $finance->id]);
    Employee::factory()->create(['department_id' => $finance->id, 'employment_status' => EmploymentStatus::Resigned]);
    Employee::factory()->create();

    actingAs(userWithRole(Role::Viewer))
        ->get("/directory?status=active&department_id={$finance->id}")
        ->assertInertia(fn ($page) => $page->has('employees.data', 1));
});

it('shows an employee with their assets, accessories and licenses', function (): void {
    $employee = Employee::factory()->create();
    $asset = Asset::factory()->deployed()->create(['assigned_employee_id' => $employee->id]);
    AccessoryCheckout::factory()->for($employee)->create(['qty' => 2]);
    LicenseSeat::factory()->for($employee)->create(['released_at' => now()]);

    actingAs(userWithRole(Role::Viewer))
        ->get("/directory/{$employee->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Directory/Show')
            ->where('employee.id', $employee->id)
            ->where('assets.0.asset_tag', $asset->asset_tag)
            ->where('accessories.0.qty', 2)
            ->has('licenses', 0));
});

it('offers no way to change directory data', function (): void {
    $employee = Employee::factory()->create();

    actingAs(userWithRole(Role::Admin))->put("/directory/{$employee->id}", ['name' => 'X'])->assertMethodNotAllowed();
});
