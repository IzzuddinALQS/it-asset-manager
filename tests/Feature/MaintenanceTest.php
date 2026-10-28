<?php

use App\Enums\AlertType;
use App\Enums\AssetAction;
use App\Enums\AssetStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\Role;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Component;
use App\Models\Maintenance;
use App\Models\MaintenancePlan;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\travelTo;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function ticketPayload(Asset $asset, array $overrides = []): array
{
    return [
        'asset_id' => $asset->id,
        'type' => 'repair',
        'priority' => 'high',
        'title' => 'Layar berkedip',
        'handled_by' => 'internal',
        ...$overrides,
    ];
}

function openTicket(Asset $asset, User $actor): Maintenance
{
    actingAs($actor)->post('/maintenance', ticketPayload($asset))->assertSessionHasNoErrors();

    return $asset->maintenances()->latest('id')->firstOrFail();
}

it('sends a deployed asset to maintenance and hands it back to its holder when done', function (): void {
    $technician = userWithRole(Role::Technician);
    $asset = Asset::factory()->deployed()->create();
    $holderId = $asset->assigned_employee_id;

    $maintenance = openTicket($asset, $technician);

    expect($asset->fresh()->status)->toBe(AssetStatus::Maintenance)
        ->and($asset->fresh()->assigned_employee_id)->toBe($holderId)
        ->and($maintenance->status)->toBe(MaintenanceStatus::Reported)
        ->and($maintenance->asset_status_before)->toBe(AssetStatus::Deployed);

    actingAs($technician)->post("/maintenance/{$maintenance->id}/status", ['status' => 'in_progress'])->assertRedirect();
    actingAs($technician)
        ->post("/maintenance/{$maintenance->id}/complete", ['return_status' => 'deployed', 'actual_cost' => 450000])
        ->assertSessionHasNoErrors();

    $maintenance->refresh();

    expect($maintenance->status)->toBe(MaintenanceStatus::Done)
        ->and($maintenance->actual_cost)->toBe('450000.00')
        ->and($maintenance->completed_at)->not->toBeNull()
        ->and($asset->fresh()->status)->toBe(AssetStatus::Deployed)
        ->and($asset->fresh()->assigned_employee_id)->toBe($holderId)
        ->and($asset->logs()->pluck('action')->all())->toContain(AssetAction::MaintenanceStart, AssetAction::MaintenanceDone);
});

it('can return a formerly deployed asset to the shelf instead', function (): void {
    $technician = userWithRole(Role::Technician);
    $asset = Asset::factory()->deployed()->create();
    $maintenance = openTicket($asset, $technician);

    actingAs($technician)
        ->post("/maintenance/{$maintenance->id}/complete", ['return_status' => 'ready'])
        ->assertSessionHasNoErrors();

    expect($asset->fresh())
        ->status->toBe(AssetStatus::Ready)
        ->assigned_employee_id->toBeNull();
});

it('refuses to hand a ready asset to a holder after maintenance', function (): void {
    $technician = userWithRole(Role::Technician);
    $asset = Asset::factory()->create(['status' => AssetStatus::Ready]);
    $maintenance = openTicket($asset, $technician);

    actingAs($technician)
        ->post("/maintenance/{$maintenance->id}/complete", ['return_status' => 'deployed'])
        ->assertSessionHasErrors('return_status');

    expect($maintenance->fresh()->status)->toBe(MaintenanceStatus::Reported)
        ->and($asset->fresh()->status)->toBe(AssetStatus::Maintenance);
});

it('numbers tickets sequentially per year', function (): void {
    travelTo(now()->setDate(2026, 3, 1));
    $technician = userWithRole(Role::Technician);

    $numbers = collect(range(1, 3))
        ->map(fn (): string => openTicket(Asset::factory()->create(['status' => AssetStatus::Ready]), $technician)->ticket_number)
        ->all();

    expect($numbers)->toBe(['MT-2026-0001', 'MT-2026-0002', 'MT-2026-0003']);
});

it('rejects a second open ticket and assets that cannot enter maintenance', function (): void {
    $technician = userWithRole(Role::Technician);
    $asset = Asset::factory()->create(['status' => AssetStatus::Ready]);
    openTicket($asset, $technician);

    actingAs($technician)->post('/maintenance', ticketPayload($asset))->assertSessionHasErrors('asset_id');

    $pending = Asset::factory()->pending()->create();
    actingAs($technician)->post('/maintenance', ticketPayload($pending))->assertSessionHasErrors('asset_id');

    expect(Maintenance::count())->toBe(1)
        ->and($pending->fresh()->status)->toBe(AssetStatus::Pending);
});

it('requires a vendor when the ticket is handled by one', function (): void {
    actingAs(userWithRole(Role::Technician))
        ->post('/maintenance', ticketPayload(Asset::factory()->create(['status' => AssetStatus::Ready]), ['handled_by' => 'vendor']))
        ->assertSessionHasErrors('supplier_id');
});

it('restores the previous status when a ticket is cancelled', function (): void {
    $technician = userWithRole(Role::Technician);
    $asset = Asset::factory()->deployed()->create();
    $maintenance = openTicket($asset, $technician);

    actingAs($technician)->post("/maintenance/{$maintenance->id}/cancel")->assertRedirect();

    expect($maintenance->fresh()->status)->toBe(MaintenanceStatus::Cancelled)
        ->and($asset->fresh()->status)->toBe(AssetStatus::Deployed);

    actingAs($technician)->post("/maintenance/{$maintenance->id}/status", ['status' => 'in_progress'])->assertSessionHas('error');
    expect($maintenance->fresh()->status)->toBe(MaintenanceStatus::Cancelled);
});

it('installs spare parts used on completion and links them to the ticket', function (): void {
    $technician = userWithRole(Role::Technician);
    $asset = Asset::factory()->create(['status' => AssetStatus::Ready]);
    $component = Component::factory()->create(['qty_total' => 3]);
    $maintenance = openTicket($asset, $technician);

    actingAs($technician)
        ->post("/maintenance/{$maintenance->id}/complete", [
            'return_status' => 'ready',
            'components' => [['component_id' => $component->id, 'qty' => 2]],
        ])
        ->assertSessionHasNoErrors();

    expect($maintenance->componentInstallations()->sum('qty'))->toBe(2)
        ->and($asset->componentInstallations()->whereNull('removed_at')->count())->toBe(1);
});

it('rolls back completion when spare part stock is short', function (): void {
    $technician = userWithRole(Role::Technician);
    $asset = Asset::factory()->create(['status' => AssetStatus::Ready]);
    $component = Component::factory()->create(['qty_total' => 1]);
    $maintenance = openTicket($asset, $technician);

    actingAs($technician)
        ->post("/maintenance/{$maintenance->id}/complete", [
            'return_status' => 'ready',
            'components' => [['component_id' => $component->id, 'qty' => 5]],
        ])
        ->assertSessionHasErrors('components');

    expect($maintenance->fresh()->status)->toBe(MaintenanceStatus::Reported)
        ->and($asset->fresh()->status)->toBe(AssetStatus::Maintenance)
        ->and($maintenance->componentInstallations()->count())->toBe(0);
});

it('lets viewers see the board but not change tickets', function (): void {
    $viewer = userWithRole(Role::Viewer);
    $maintenance = Maintenance::factory()->create();

    actingAs($viewer)
        ->get('/maintenance')
        ->assertInertia(fn ($page) => $page
            ->component('Maintenance/Index')
            ->has('tickets', 1)
            ->where('can.create', false));

    actingAs($viewer)->get("/maintenance/{$maintenance->id}")->assertOk();
    actingAs($viewer)->post('/maintenance', ticketPayload(Asset::factory()->create(['status' => AssetStatus::Ready])))->assertForbidden();
    actingAs($viewer)->post("/maintenance/{$maintenance->id}/cancel")->assertForbidden();
});

it('hides cancelled tickets from the board unless asked', function (): void {
    Maintenance::factory()->create(['status' => MaintenanceStatus::Cancelled]);
    Maintenance::factory()->create(['status' => MaintenanceStatus::InProgress]);
    $viewer = userWithRole(Role::Viewer);

    actingAs($viewer)->get('/maintenance')->assertInertia(fn ($page) => $page->has('tickets', 1));
    actingAs($viewer)->get('/maintenance?show_cancelled=1')->assertInertia(fn ($page) => $page->has('tickets', 2));
});

it('flags preventive plans as due from H-7 and resolves the alert once run', function (): void {
    travelTo(now()->setDate(2026, 5, 1));
    $category = Category::factory()->create();
    $covered = Asset::factory()->create([
        'status' => AssetStatus::Ready,
        'asset_model_id' => AssetModel::factory()->for($category),
    ]);
    $deployed = Asset::factory()->deployed()->create(['asset_model_id' => $covered->asset_model_id]);
    Asset::factory()->create(['status' => AssetStatus::Ready]);
    $plan = MaintenancePlan::factory()->for($category)->create(['next_due_date' => '2026-05-20', 'interval_months' => 6]);

    artisan('maintenance:sync-alerts')->assertSuccessful();
    expect($plan->alerts()->open()->count())->toBe(0);

    travelTo(now()->setDate(2026, 5, 14));
    artisan('maintenance:sync-alerts')->assertSuccessful();
    expect($plan->alerts()->open()->where('type', AlertType::MaintenanceDue)->count())->toBe(1);

    actingAs(userWithRole(Role::Technician))->post("/maintenance/plans/{$plan->id}/run")->assertRedirect();

    expect($plan->fresh()->next_due_date->toDateString())->toBe('2026-11-20')
        ->and($plan->alerts()->open()->count())->toBe(0)
        ->and($plan->maintenances()->pluck('asset_id')->sort()->values()->all())->toBe([$covered->id, $deployed->id])
        ->and($covered->fresh()->status)->toBe(AssetStatus::Maintenance);
});

it('saves preventive plans and only lets operators manage them', function (): void {
    $category = Category::factory()->create();
    $payload = ['name' => 'Servis AC ruang server', 'category_id' => $category->id, 'interval_months' => 3, 'next_due_date' => now()->addDays(3)->toDateString()];

    actingAs(userWithRole(Role::Viewer))->post('/maintenance/plans', $payload)->assertForbidden();
    actingAs(userWithRole(Role::Technician))->post('/maintenance/plans', $payload)->assertSessionHasNoErrors();

    $plan = MaintenancePlan::query()->where('name', 'Servis AC ruang server')->firstOrFail();

    expect($plan->alerts()->open()->count())->toBe(1);

    actingAs(userWithRole(Role::Technician))
        ->put("/maintenance/plans/{$plan->id}", [...$payload, 'is_active' => false])
        ->assertSessionHasNoErrors();

    expect($plan->fresh()->is_active)->toBeFalse()
        ->and($plan->alerts()->open()->count())->toBe(0);
});

it('offers sending to maintenance from the asset page', function (): void {
    $asset = Asset::factory()->deployed()->create();

    actingAs(userWithRole(Role::Technician))
        ->get("/assets/{$asset->id}")
        ->assertInertia(fn ($page) => $page
            ->where('can.sendToMaintenance', true)
            ->where('asset.open_maintenance', null));

    $maintenance = openTicket($asset, userWithRole(Role::Technician));

    actingAs(userWithRole(Role::Technician))
        ->get("/assets/{$asset->id}")
        ->assertInertia(fn ($page) => $page
            ->where('can.sendToMaintenance', false)
            ->where('asset.open_maintenance.ticket_number', $maintenance->ticket_number));
});

it('adopts an asset checked in as broken without a ticket', function (): void {
    $technician = userWithRole(Role::Technician);
    $asset = Asset::factory()->maintenance()->create(['assigned_employee_id' => null]);

    $maintenance = openTicket($asset, $technician);

    expect($maintenance->asset_status_before)->toBe(AssetStatus::Ready)
        ->and($asset->fresh()->status)->toBe(AssetStatus::Maintenance);
});
