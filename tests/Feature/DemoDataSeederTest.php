<?php

use App\Enums\AssetStatus;
use App\Enums\Role;
use App\Models\Accessory;
use App\Models\AccessoryStock;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\AssetModel;
use App\Models\Component;
use App\Models\ComponentStock;
use App\Models\Department;
use App\Models\Employee;
use App\Models\License;
use App\Models\Location;
use App\Models\Maintenance;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;

beforeEach(function (): void {
    app(DemoDataSeeder::class)->run();
});

it('seeds the documented amount of demo data', function (): void {
    expect(Location::count())->toBe(3)
        ->and(Department::count())->toBe(6)
        ->and(Employee::count())->toBe(40)
        ->and(AssetModel::count())->toBe(25)
        ->and(Asset::count())->toBe(60)
        ->and(License::count())->toBe(8)
        ->and(Component::count())->toBe(5)
        ->and(Accessory::count())->toBe(5)
        ->and(Maintenance::count())->toBe(6)
        ->and(User::pluck('role')->all())->toEqualCanonicalizing(Role::cases());

    foreach (AssetStatus::cases() as $status) {
        expect(Asset::where('status', $status)->exists())->toBeTrue();
    }
});

it('keeps every asset status in line with its latest log', function (): void {
    Asset::with('logs')->get()->each(function (Asset $asset): void {
        $latestStatusLog = $asset->logs->first(fn (AssetLog $log): bool => $log->to_status !== null);

        expect($latestStatusLog?->to_status)->toBe($asset->status, "Asset {$asset->asset_tag}");
    });

    expect(AssetLog::where('created_at', '>', now())->exists())->toBeFalse();
});

it('gives every asset in maintenance exactly one open ticket', function (): void {
    Asset::where('status', AssetStatus::Maintenance)->get()->each(function (Asset $asset): void {
        expect($asset->maintenances()->open()->count())->toBe(1);
    });

    expect(Maintenance::open()->count())->toBe(Asset::where('status', AssetStatus::Maintenance)->count());
});

it('never over-allocates license seats or stock', function (): void {
    License::withCount('activeSeats')->get()->each(function (License $license): void {
        expect($license->active_seats_count)->toBeLessThanOrEqual($license->seats_total);
    });

    expect(ComponentStock::where('qty_available', '<', 0)->exists())->toBeFalse()
        ->and(AccessoryStock::where('qty_available', '<', 0)->exists())->toBeFalse();
});

it('creates alerts that point at real subjects', function (): void {
    expect(Alert::open()->count())->toBeGreaterThan(0);

    Alert::with('subject')->get()->each(function (Alert $alert): void {
        expect($alert->subject)->not->toBeNull("Alert {$alert->id} ({$alert->subject_type})");
    });
});
