<?php

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Enums\AssetAction;
use App\Enums\Role;
use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\AccessoryStock;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentStock;
use App\Models\Employee;

use function Pest\Laravel\actingAs;

function componentAvailable(Component $component): int
{
    return ComponentStock::query()->findOrFail($component->id)->qty_available;
}

function accessoryAvailable(Accessory $accessory): int
{
    return AccessoryStock::query()->findOrFail($accessory->id)->qty_available;
}

it('lowers stock by two when two RAM sticks are installed and restores it on removal', function (): void {
    $ram = Component::factory()->create(['name' => 'RAM DDR4 8 GB', 'qty_total' => 10, 'min_qty' => 2]);
    $asset = Asset::factory()->create();

    actingAs(userWithRole(Role::Technician))
        ->post("/assets/{$asset->id}/components", ['component_id' => $ram->id, 'qty' => 2])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(componentAvailable($ram))->toBe(8)
        ->and($asset->logs()->where('action', AssetAction::ComponentInstalled)->exists())->toBeTrue();

    $installation = ComponentInstallation::query()->sole();

    actingAs(userWithRole(Role::Technician))
        ->post("/component-installations/{$installation->id}/remove")
        ->assertRedirect();

    expect(componentAvailable($ram))->toBe(10)
        ->and($installation->fresh()->removed_at)->not->toBeNull()
        ->and($asset->logs()->where('action', AssetAction::ComponentRemoved)->exists())->toBeTrue();
});

it('rejects installing more components than are available', function (): void {
    $ssd = Component::factory()->create(['qty_total' => 1]);
    $asset = Asset::factory()->create();

    actingAs(userWithRole(Role::Technician))
        ->post("/assets/{$asset->id}/components", ['component_id' => $ssd->id, 'qty' => 2])
        ->assertSessionHasErrors('qty');

    expect(ComponentInstallation::query()->count())->toBe(0);
});

it('does not take factory-fitted components out of stock', function (): void {
    $battery = Component::factory()->create(['qty_total' => 0, 'min_qty' => 0]);
    $asset = Asset::factory()->create();

    actingAs(userWithRole(Role::Technician))
        ->post("/assets/{$asset->id}/components", ['component_id' => $battery->id, 'qty' => 1, 'is_factory' => true])
        ->assertSessionHasNoErrors();

    expect(componentAvailable($battery))->toBe(0)
        ->and(ComponentInstallation::query()->sole()->is_factory)->toBeTrue();
});

it('refuses to install components into a retired asset', function (): void {
    $component = Component::factory()->create(['qty_total' => 5]);
    $asset = Asset::factory()->retired()->create();

    actingAs(userWithRole(Role::Admin))
        ->post("/assets/{$asset->id}/components", ['component_id' => $component->id, 'qty' => 1])
        ->assertSessionHas('error');

    expect(ComponentInstallation::query()->count())->toBe(0);
});

it('rejects an accessory checkout that exceeds the stock', function (): void {
    $mouse = Accessory::factory()->create(['qty_total' => 3]);
    $employee = Employee::factory()->create();

    actingAs(userWithRole(Role::Technician))
        ->post("/inventory/accessories/{$mouse->id}/checkout", ['employee_id' => $employee->id, 'qty' => 4])
        ->assertSessionHasErrors('qty');

    expect(AccessoryCheckout::query()->count())->toBe(0)
        ->and(accessoryAvailable($mouse))->toBe(3);
});

it('checks accessories out and supports partial returns', function (): void {
    $headset = Accessory::factory()->create(['qty_total' => 10, 'min_qty' => 1]);
    $employee = Employee::factory()->create();

    actingAs(userWithRole(Role::Technician))
        ->post("/inventory/accessories/{$headset->id}/checkout", ['employee_id' => $employee->id, 'qty' => 5])
        ->assertSessionHasNoErrors();

    expect(accessoryAvailable($headset))->toBe(5);

    $checkout = AccessoryCheckout::query()->sole();

    actingAs(userWithRole(Role::Technician))
        ->post("/accessory-checkouts/{$checkout->id}/checkin", ['qty' => 2])
        ->assertSessionHasNoErrors();

    expect(accessoryAvailable($headset))->toBe(7)
        ->and($checkout->fresh()->qty)->toBe(3)
        ->and($checkout->fresh()->isActive())->toBeTrue()
        ->and(AccessoryCheckout::query()->whereNotNull('checked_in_at')->sole()->qty)->toBe(2);

    actingAs(userWithRole(Role::Technician))
        ->post("/accessory-checkouts/{$checkout->id}/checkin", ['qty' => 4])
        ->assertSessionHasErrors('qty');

    actingAs(userWithRole(Role::Technician))
        ->post("/accessory-checkouts/{$checkout->id}/checkin", ['qty' => 3])
        ->assertSessionHasNoErrors();

    expect(accessoryAvailable($headset))->toBe(10)
        ->and($checkout->fresh()->isActive())->toBeFalse();
});

it('opens a low stock alert at the minimum and resolves it once stock recovers', function (): void {
    $cable = Accessory::factory()->create(['qty_total' => 5, 'min_qty' => 2]);
    $employee = Employee::factory()->create();
    $technician = userWithRole(Role::Technician);

    actingAs($technician)->post("/inventory/accessories/{$cable->id}/checkout", ['employee_id' => $employee->id, 'qty' => 2]);
    expect($cable->alerts()->count())->toBe(0);

    actingAs($technician)->post("/inventory/accessories/{$cable->id}/checkout", ['employee_id' => $employee->id, 'qty' => 1]);

    $alert = Alert::query()->open()->sole();
    expect($alert->type)->toBe(AlertType::LowStock)
        ->and($alert->subject_type)->toBe('accessory')
        ->and($alert->severity)->toBe(AlertSeverity::Warning)
        ->and($alert->message)->toContain('tersisa 2 unit');

    actingAs($technician)->post("/inventory/accessories/{$cable->id}/checkout", ['employee_id' => $employee->id, 'qty' => 2]);
    expect(Alert::query()->open()->sole()->severity)->toBe(AlertSeverity::Critical)
        ->and(Alert::query()->count())->toBe(1);

    $checkout = AccessoryCheckout::query()->oldest('id')->firstOrFail();
    actingAs($technician)->post("/accessory-checkouts/{$checkout->id}/checkin", ['qty' => 2]);

    expect(accessoryAvailable($cable))->toBe(2)
        ->and(Alert::query()->open()->count())->toBe(1);

    $checkout = AccessoryCheckout::query()->whereNull('checked_in_at')->oldest('id')->firstOrFail();
    actingAs($technician)->post("/accessory-checkouts/{$checkout->id}/checkin", ['qty' => 1]);

    expect(Alert::query()->open()->count())->toBe(0)
        ->and($alert->fresh()->resolved_at)->not->toBeNull();
});

it('raises a low stock alert when installing components drains the stock', function (): void {
    $ram = Component::factory()->create(['qty_total' => 4, 'min_qty' => 2]);

    actingAs(userWithRole(Role::Technician))
        ->post('/assets/'.Asset::factory()->create()->id.'/components', ['component_id' => $ram->id, 'qty' => 2]);

    expect($ram->alerts()->open()->where('type', AlertType::LowStock)->count())->toBe(1);
});

it('lets admins create and edit components and syncs the alert with the new minimum', function (): void {
    $category = Category::factory()->component()->create();

    actingAs(userWithRole(Role::Admin))
        ->post('/inventory/components', [
            'name' => 'SSD NVMe 1 TB',
            'category_id' => $category->id,
            'qty_total' => 3,
            'min_qty' => 5,
        ])
        ->assertRedirect('/inventory')
        ->assertSessionHasNoErrors();

    $component = Component::query()->sole();
    expect($component->alerts()->open()->count())->toBe(1);

    actingAs(userWithRole(Role::Admin))
        ->put("/inventory/components/{$component->id}", [
            'name' => 'SSD NVMe 1 TB',
            'category_id' => $category->id,
            'qty_total' => 10,
            'min_qty' => 5,
        ])
        ->assertSessionHasNoErrors();

    expect($component->fresh()->qty_total)->toBe(10)
        ->and($component->alerts()->open()->count())->toBe(0);
});

it('does not let the total drop below the units already installed', function (): void {
    $component = Component::factory()->create(['qty_total' => 5]);
    ComponentInstallation::factory()->create(['component_id' => $component->id, 'qty' => 3, 'is_factory' => false, 'removed_at' => null]);

    actingAs(userWithRole(Role::Admin))
        ->put("/inventory/components/{$component->id}", [
            'name' => $component->name,
            'category_id' => $component->category_id,
            'qty_total' => 2,
            'min_qty' => 0,
        ])
        ->assertSessionHasErrors('qty_total');
});

it('rejects a category of the wrong type', function (): void {
    actingAs(userWithRole(Role::Admin))
        ->post('/inventory/accessories', [
            'name' => 'Webcam',
            'category_id' => Category::factory()->component()->create()->id,
            'qty_total' => 3,
            'min_qty' => 1,
        ])
        ->assertSessionHasErrors('category_id');
});

it('enforces role permissions on inventory actions', function (): void {
    $component = Component::factory()->create(['qty_total' => 5]);
    $accessory = Accessory::factory()->create(['qty_total' => 5]);
    $asset = Asset::factory()->create();
    $employee = Employee::factory()->create();

    actingAs(userWithRole(Role::Viewer))
        ->post("/assets/{$asset->id}/components", ['component_id' => $component->id, 'qty' => 1])
        ->assertForbidden();
    actingAs(userWithRole(Role::Viewer))
        ->post("/inventory/accessories/{$accessory->id}/checkout", ['employee_id' => $employee->id, 'qty' => 1])
        ->assertForbidden();
    actingAs(userWithRole(Role::Technician))->get('/inventory/components/create')->assertForbidden();
    actingAs(userWithRole(Role::Technician))->get("/inventory/accessories/{$accessory->id}/edit")->assertForbidden();
});

it('lists stock per tab with role-based abilities', function (Role $role, bool $canCreate): void {
    Component::factory()->create(['name' => 'RAM DDR5 16 GB', 'qty_total' => 6, 'min_qty' => 2]);
    Accessory::factory()->create(['name' => 'Mouse Wireless', 'qty_total' => 4, 'min_qty' => 5]);

    actingAs(userWithRole($role))
        ->get('/inventory')
        ->assertInertia(fn ($page) => $page
            ->component('Inventory/Index')
            ->where('tab', 'components')
            ->where('items.data.0.name', 'RAM DDR5 16 GB')
            ->where('items.data.0.qty_available', 6)
            ->where('lowStockCounts.accessories', 1)
            ->where('can.createComponent', $canCreate));

    actingAs(userWithRole($role))
        ->get('/inventory?tab=accessories&low_stock=1')
        ->assertInertia(fn ($page) => $page
            ->where('tab', 'accessories')
            ->has('items.data', 1)
            ->where('items.data.0.name', 'Mouse Wireless'));
})->with([
    'admin' => [Role::Admin, true],
    'technician' => [Role::Technician, false],
    'viewer' => [Role::Viewer, false],
]);

it('shows accessory loans and the install ability on the asset page', function (): void {
    $accessory = Accessory::factory()->create(['qty_total' => 5]);
    AccessoryCheckout::factory()->create(['accessory_id' => $accessory->id, 'qty' => 2, 'checked_in_at' => null]);

    actingAs(userWithRole(Role::Technician))
        ->get("/inventory/accessories/{$accessory->id}")
        ->assertInertia(fn ($page) => $page
            ->component('Inventory/AccessoryShow')
            ->where('accessory.qty_available', 3)
            ->where('can.checkout', true)
            ->where('can.update', false));

    actingAs(userWithRole(Role::Technician))
        ->get('/assets/'.Asset::factory()->create()->id)
        ->assertInertia(fn ($page) => $page->where('can.installComponent', true));
});
