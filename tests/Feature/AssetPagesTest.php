<?php

use App\Enums\AssetAction;
use App\Enums\AssetStatus;
use App\Enums\Role;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

describe('index', function (): void {
    it('lists assets with status counts', function (): void {
        Asset::factory()->count(3)->create();
        Asset::factory()->deployed()->count(2)->create();

        actingAs(userWithRole(Role::Viewer))->get('/assets')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assets/Index')
                ->has('assets.data', 5)
                ->where('statusCounts.ready', 3)
                ->where('statusCounts.deployed', 2)
                ->where('statusCounts.pending', 0));
    });

    it('filters by status, category, location and search', function (): void {
        $laptops = Category::factory()->create(['name' => 'Laptop']);
        $office = Location::factory()->create();
        $holder = Employee::factory()->create(['name' => 'Rina Kartika']);

        $match = Asset::factory()->for(AssetModel::factory()->for($laptops))->create([
            'status' => AssetStatus::Deployed,
            'assigned_employee_id' => $holder->id,
            'location_id' => $office->id,
        ]);
        Asset::factory()->for(AssetModel::factory()->for($laptops))->create(['location_id' => $office->id]);
        Asset::factory()->deployed()->create();

        $query = http_build_query([
            'status' => 'deployed',
            'category_id' => $laptops->id,
            'location_id' => $office->id,
            'search' => 'rina',
        ]);

        actingAs(userWithRole(Role::Viewer))->get("/assets?{$query}")
            ->assertInertia(fn (Assert $page) => $page
                ->has('assets.data', 1)
                ->where('assets.data.0.id', $match->id)
                ->where('assets.data.0.holder', 'Rina Kartika')
                ->where('filters.search', 'rina'));
    });

    it('sorts on whitelisted columns only', function (): void {
        Asset::factory()->create(['asset_tag' => 'LT-00002']);
        Asset::factory()->create(['asset_tag' => 'LT-00001']);

        actingAs(userWithRole(Role::Viewer))->get('/assets?sort=asset_tag&direction=desc')
            ->assertInertia(fn (Assert $page) => $page->where('assets.data.0.asset_tag', 'LT-00002'));

        actingAs(userWithRole(Role::Viewer))->get('/assets?sort=password&direction=desc')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.sort', 'asset_tag')
                ->where('assets.data.0.asset_tag', 'LT-00002'));
    });

    it('paginates fifteen per page', function (): void {
        Asset::factory()->count(16)->create();

        actingAs(userWithRole(Role::Viewer))->get('/assets?page=2')
            ->assertInertia(fn (Assert $page) => $page
                ->has('assets.data', 1)
                ->where('assets.total', 16));
    });
});

describe('create and update', function (): void {
    it('lets only admins open the create form and store assets', function (Role $role, int $status): void {
        $user = User::factory()->create(['role' => $role]);

        actingAs($user)->get('/assets/create')->assertStatus($status);
        actingAs($user)->post('/assets', ['asset_model_id' => AssetModel::factory()->create()->id])->assertStatus($status === 200 ? 302 : 403);
    })->with([
        'admin' => [Role::Admin, 200],
        'technician' => [Role::Technician, 403],
        'viewer' => [Role::Viewer, 403],
    ]);

    it('stores a new asset as pending with a temporary tag', function (): void {
        $model = AssetModel::factory()->create();

        $response = actingAs(userWithRole(Role::Admin))->post('/assets', [
            'asset_model_id' => $model->id,
            'serial_number' => 'SN-NEW-001',
            'purchase_order' => 'PO-2026-0099',
            'purchase_date' => now()->subWeek()->toDateString(),
            'purchase_cost' => 18500000,
            'warranty_until' => now()->addYears(3)->toDateString(),
        ]);

        $asset = Asset::query()->where('serial_number', 'SN-NEW-001')->firstOrFail();

        $response->assertRedirect("/assets/{$asset->id}")->assertSessionHas('success');
        expect($asset->status)->toBe(AssetStatus::Pending)
            ->and($asset->asset_tag)->toStartWith('TMP-')
            ->and($asset->created_by)->toBe(userWithRole(Role::Admin)->id)
            ->and($asset->logs()->first()->action)->toBe(AssetAction::Created);
    });

    it('validates the asset form', function (array $payload, string $field): void {
        Asset::factory()->create(['serial_number' => 'SN-TAKEN']);

        actingAs(userWithRole(Role::Admin))
            ->post('/assets', [...['asset_model_id' => AssetModel::factory()->create()->id], ...$payload])
            ->assertSessionHasErrors($field);
    })->with([
        'missing model' => [['asset_model_id' => null], 'asset_model_id'],
        'duplicate serial' => [['serial_number' => 'SN-TAKEN'], 'serial_number'],
        'future purchase' => [['purchase_date' => '2099-01-01'], 'purchase_date'],
        'warranty before purchase' => [['purchase_date' => '2026-01-10', 'warranty_until' => '2025-12-31'], 'warranty_until'],
        'negative cost' => [['purchase_cost' => -1], 'purchase_cost'],
    ]);

    it('updates editable fields without touching the status', function (): void {
        $asset = Asset::factory()->deployed()->create();

        actingAs(userWithRole(Role::Admin))
            ->put("/assets/{$asset->id}", [
                'asset_model_id' => $asset->asset_model_id,
                'serial_number' => 'SN-UPDATED',
                'status' => 'retired',
            ])
            ->assertRedirect("/assets/{$asset->id}");

        $asset->refresh();

        expect($asset->serial_number)->toBe('SN-UPDATED')
            ->and($asset->status)->toBe(AssetStatus::Deployed);
    });
});

describe('show', function (): void {
    it('combines role permission with the lifecycle state', function (string $role, Closure $makeAsset, array $expected): void {
        $asset = $makeAsset();

        actingAs(userWithRole(Role::from($role)))->get("/assets/{$asset->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assets/Show')
                ->where('can', $expected));
    })->with([
        'admin on pending' => ['admin', fn () => Asset::factory()->pending()->create(), ['update' => true, 'tag' => true, 'checkout' => false, 'checkin' => false, 'retire' => false, 'printLabel' => false, 'installComponent' => true, 'sendToMaintenance' => false]],
        'admin on ready' => ['admin', fn () => Asset::factory()->create(), ['update' => true, 'tag' => false, 'checkout' => true, 'checkin' => false, 'retire' => true, 'printLabel' => true, 'installComponent' => true, 'sendToMaintenance' => true]],
        'technician on deployed' => ['technician', fn () => Asset::factory()->deployed()->create(), ['update' => false, 'tag' => false, 'checkout' => false, 'checkin' => true, 'retire' => false, 'printLabel' => true, 'installComponent' => true, 'sendToMaintenance' => true]],
        'viewer on ready' => ['viewer', fn () => Asset::factory()->create(), ['update' => false, 'tag' => false, 'checkout' => false, 'checkin' => false, 'retire' => false, 'printLabel' => true, 'installComponent' => false, 'sendToMaintenance' => false]],
        'admin on retired' => ['admin', fn () => Asset::factory()->retired()->create(), ['update' => false, 'tag' => false, 'checkout' => false, 'checkin' => false, 'retire' => false, 'printLabel' => true, 'installComponent' => false, 'sendToMaintenance' => false]],
    ]);

    it('loads active employees only when the checkout dialog asks for them', function (): void {
        $technician = userWithRole(Role::Technician);
        $asset = Asset::factory()->create();
        Employee::factory()->count(2)->create();
        $resigned = Employee::factory()->resigned()->create();
        $activeCount = Employee::active()->count();

        actingAs($technician)->get("/assets/{$asset->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->missing('employees')
                ->reloadOnly('employees', fn (Assert $reload) => $reload
                    ->has('employees', $activeCount)
                    ->where('employees', fn (iterable $employees): bool => ! in_array(
                        $resigned->id,
                        array_column(is_array($employees) ? $employees : iterator_to_array($employees), 'id'),
                        true,
                    ))));
    });
});

describe('lifecycle endpoints', function (): void {
    it('walks an asset through tag, checkout, checkin and retire', function (): void {
        $asset = Asset::factory()->pending()->create();
        $employee = Employee::factory()->create();

        actingAs(userWithRole(Role::Technician))->post("/assets/{$asset->id}/tag")->assertSessionHas('success');
        actingAs(userWithRole(Role::Technician))
            ->post("/assets/{$asset->id}/checkout", ['assigned_employee_id' => $employee->id, 'note' => 'BAST/IT/2026/0001'])
            ->assertSessionHas('success');
        expect($asset->fresh()->assigned_employee_id)->toBe($employee->id);

        actingAs(userWithRole(Role::Technician))->post("/assets/{$asset->id}/checkin", ['to_status' => 'ready'])->assertSessionHas('success');
        actingAs(userWithRole(Role::Admin))->post("/assets/{$asset->id}/retire", ['retire_reason' => 'sold'])->assertSessionHas('success');

        expect($asset->fresh()->status)->toBe(AssetStatus::Retired)
            ->and($asset->logs()->count())->toBe(4);
    });

    it('turns a forbidden transition into a flash error instead of a crash', function (): void {
        $asset = Asset::factory()->deployed()->create();

        actingAs(userWithRole(Role::Technician))
            ->from("/assets/{$asset->id}")
            ->post("/assets/{$asset->id}/checkout", ['assigned_location_id' => Location::factory()->create()->id])
            ->assertRedirect("/assets/{$asset->id}")
            ->assertSessionHas('error');

        expect($asset->fresh()->status)->toBe(AssetStatus::Deployed);
    });

    it('validates the checkout recipient', function (array $payload, string $field): void {
        $asset = Asset::factory()->create();

        actingAs(userWithRole(Role::Technician))->post("/assets/{$asset->id}/checkout", $payload)->assertSessionHasErrors($field);
    })->with([
        'no recipient' => [[], 'assigned_employee_id'],
        'both recipients' => [fn () => ['assigned_employee_id' => Employee::factory()->create()->id, 'assigned_location_id' => Location::factory()->create()->id], 'assigned_employee_id'],
        'resigned employee' => [fn () => ['assigned_employee_id' => Employee::factory()->resigned()->create()->id], 'assigned_employee_id'],
        'past return date' => [fn () => ['assigned_location_id' => Location::factory()->create()->id, 'expected_return_date' => '2020-01-01'], 'expected_return_date'],
    ]);

    it('forbids actions outside the role', function (string $role, string $endpoint, array $payload): void {
        $asset = Asset::factory()->deployed()->create();

        actingAs(userWithRole(Role::from($role)))->post("/assets/{$asset->id}/{$endpoint}", $payload)->assertForbidden();
    })->with([
        'viewer checkin' => ['viewer', 'checkin', ['to_status' => 'ready']],
        'technician retire' => ['technician', 'retire', ['retire_reason' => 'lost']],
    ]);
});

describe('labels and lookup', function (): void {
    it('renders printable QR labels and skips pending assets', function (): void {
        $tagged = Asset::factory()->create(['asset_tag' => 'LT-00042']);
        $pending = Asset::factory()->pending()->create();

        actingAs(userWithRole(Role::Viewer))->get("/assets/labels?ids[]={$tagged->id}&ids[]={$pending->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assets/Labels')
                ->has('labels', 1)
                ->where('labels.0.asset_tag', 'LT-00042')
                ->where('labels.0.qr_svg', fn (string $svg): bool => str_starts_with($svg, '<svg'))
                ->where('skipped', 1));
    });

    it('finds an asset by tag regardless of case', function (): void {
        $asset = Asset::factory()->create(['asset_tag' => 'NW-00003']);

        actingAs(userWithRole(Role::Viewer))->getJson('/api/assets/by-tag/nw-00003')
            ->assertOk()
            ->assertJsonPath('data.id', $asset->id)
            ->assertJsonPath('data.url', route('assets.show', $asset));
    });

    it('answers unknown tags and guests with JSON errors', function (): void {
        actingAs(userWithRole(Role::Viewer))->getJson('/api/assets/by-tag/XX-99999')->assertNotFound();

        auth()->logout();
        getJson('/api/assets/by-tag/XX-99999')->assertUnauthorized();
    });
});
