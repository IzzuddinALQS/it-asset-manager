<?php

use App\Enums\AssetAction;
use App\Enums\MaintenanceStatus;
use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\Employee;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('stores alert subjects with morph aliases and resolves them back', function (): void {
    $asset = Asset::factory()->create();

    $alert = $asset->alerts()->create(Alert::factory()->raw(['subject_type' => null, 'subject_id' => null]));

    expect(DB::table('alerts')->where('id', $alert->id)->value('subject_type'))->toBe('asset')
        ->and($alert->fresh()->subject->is($asset))->toBeTrue();
});

it('scopes open alerts', function (): void {
    Alert::factory()->create();
    Alert::factory()->create(['resolved_at' => now()]);

    expect(Alert::open()->count())->toBe(1);
});

it('encrypts the license product key at rest and masks it for display', function (): void {
    $license = License::factory()->create(['product_key' => 'ABCDE-12345-FGHIJ-67890']);

    expect(DB::table('licenses')->where('id', $license->id)->value('product_key'))->not->toContain('ABCDE')
        ->and($license->fresh()->product_key)->toBe('ABCDE-12345-FGHIJ-67890')
        ->and($license->maskedProductKey())->toEndWith('7890')
        ->and($license->maskedProductKey())->not->toContain('ABCDE');
});

it('counts only active license seats as used', function (): void {
    $license = License::factory()->create(['seats_total' => 3]);
    LicenseSeat::factory()->count(2)->for($license)->create();
    LicenseSeat::factory()->for($license)->create(['released_at' => now()]);
    LicenseSeat::factory()->for($license)->create(['employee_id' => null, 'asset_id' => Asset::factory()]);

    expect($license->seatsUsed())->toBe(3)
        ->and($license->seatsAvailable())->toBe(0);
});

it('scopes licenses expiring within the given window', function (): void {
    License::factory()->create(['expires_at' => now()->addDays(10)]);
    License::factory()->create(['expires_at' => now()->addDays(90)]);
    License::factory()->create(['expires_at' => now()->subDay()]);
    License::factory()->create(['expires_at' => null]);

    expect(License::expiringSoon(30)->count())->toBe(1);
});

it('scopes open maintenance tickets', function (): void {
    foreach (MaintenanceStatus::cases() as $status) {
        Maintenance::factory()->create(['status' => $status]);
    }

    expect(Maintenance::open()->count())->toBe(3)
        ->and(Maintenance::open()->get()->every(fn (Maintenance $maintenance): bool => $maintenance->status->isOpen()))->toBeTrue();
});

it('exposes computed stock through the stock relations', function (): void {
    $component = Component::factory()->create(['qty_total' => 5]);
    ComponentInstallation::factory()->for($component)->create(['qty' => 2]);

    $accessory = Accessory::factory()->create(['qty_total' => 4]);
    AccessoryCheckout::factory()->for($accessory)->create(['qty' => 1]);

    expect($component->stock->qty_available)->toBe(3)
        ->and($accessory->stock->qty_out)->toBe(1)
        ->and($accessory->stock->qty_available)->toBe(3);
});

it('links a user to its employee in both directions', function (): void {
    $user = User::factory()->technician()->create();

    expect($user->employee->user->is($user))->toBeTrue()
        ->and($user->canOperate())->toBeTrue()
        ->and($user->isAdmin())->toBeFalse();
});

it('returns asset logs newest first', function (): void {
    $asset = Asset::factory()->create();
    AssetLog::factory()->for($asset)->create(['action' => AssetAction::Created, 'created_at' => now()->subDays(2)]);
    AssetLog::factory()->for($asset)->create(['action' => AssetAction::Tagged, 'created_at' => now()->subDay()]);

    expect($asset->logs->pluck('action')->all())->toBe([AssetAction::Tagged, AssetAction::Created]);
});

it('lists what an employee currently holds', function (): void {
    $employee = Employee::factory()->create();
    Asset::factory()->create(['status' => 'deployed', 'assigned_employee_id' => $employee->id]);
    AccessoryCheckout::factory()->for($employee)->create();
    LicenseSeat::factory()->for($employee)->create();

    expect($employee->assets)->toHaveCount(1)
        ->and($employee->accessoryCheckouts)->toHaveCount(1)
        ->and($employee->licenseSeats)->toHaveCount(1);
});
