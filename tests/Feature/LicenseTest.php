<?php

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Enums\Role;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\License;
use App\Models\LicenseSeat;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\travelTo;

function openLicenseAlert(License $license, AlertType $type): ?Alert
{
    return $license->alerts()->open()->where('type', $type)->first();
}

it('rejects the sixth seat of a five-seat license', function (): void {
    $license = License::factory()->create(['seats_total' => 5]);
    $technician = userWithRole(Role::Technician);

    foreach (Employee::factory()->count(5)->create() as $employee) {
        actingAs($technician)
            ->post("/licenses/{$license->id}/seats", ['holder_type' => 'employee', 'employee_id' => $employee->id])
            ->assertSessionHasNoErrors();
    }

    actingAs($technician)
        ->post("/licenses/{$license->id}/seats", ['holder_type' => 'employee', 'employee_id' => Employee::factory()->create()->id])
        ->assertSessionHasErrors(['employee_id' => "Kuota {$license->name} sudah penuh (5 dari 5 terpakai)."]);

    expect($license->seatsUsed())->toBe(5)
        ->and(openLicenseAlert($license, AlertType::LicenseSeatsFull))->not->toBeNull();
});

it('returns the kuota to the pool when it is released', function (): void {
    $license = License::factory()->create(['seats_total' => 1]);
    $seat = LicenseSeat::factory()->for($license)->create();
    $technician = userWithRole(Role::Technician);

    actingAs($technician)->post("/license-seats/{$seat->id}/release")->assertRedirect();

    expect($seat->fresh()->released_at)->not->toBeNull()
        ->and($seat->fresh()->released_by)->toBe($technician->id)
        ->and($license->seatsAvailable())->toBe(1);

    actingAs($technician)
        ->post("/licenses/{$license->id}/seats", ['holder_type' => 'asset', 'asset_id' => Asset::factory()->create()->id])
        ->assertSessionHasNoErrors();

    expect($license->seatsUsed())->toBe(1);
});

it('refuses to release a kuota twice', function (): void {
    $seat = LicenseSeat::factory()->create(['released_at' => now()]);

    actingAs(userWithRole(Role::Technician))
        ->post("/license-seats/{$seat->id}/release")
        ->assertSessionHas('error', 'Kuota ini sudah dilepas sebelumnya.');
});

it('does not give the same employee two seats of one license', function (): void {
    $license = License::factory()->create(['seats_total' => 5]);
    $seat = LicenseSeat::factory()->for($license)->create();

    actingAs(userWithRole(Role::Technician))
        ->post("/licenses/{$license->id}/seats", ['holder_type' => 'employee', 'employee_id' => $seat->employee_id])
        ->assertSessionHasErrors('employee_id');

    expect($license->seatsUsed())->toBe(1);
});

it('raises the expiry alert from thirty days before the license expires', function (): void {
    travelTo('2026-10-01');
    $license = License::factory()->create(['expires_at' => '2026-11-15']);

    artisan('licenses:sync-alerts')->assertSuccessful();
    expect(openLicenseAlert($license, AlertType::LicenseExpiring))->toBeNull();

    travelTo('2026-10-16');
    artisan('licenses:sync-alerts')->assertSuccessful();

    $alert = openLicenseAlert($license, AlertType::LicenseExpiring);
    expect($alert)->not->toBeNull()
        ->and($alert->severity)->toBe(AlertSeverity::Warning)
        ->and($alert->due_date->toDateString())->toBe('2026-11-15');

    travelTo('2026-11-10');
    artisan('licenses:sync-alerts')->assertSuccessful();

    expect(openLicenseAlert($license, AlertType::LicenseExpiring)->severity)->toBe(AlertSeverity::Critical)
        ->and($license->alerts()->where('type', AlertType::LicenseExpiring)->count())->toBe(1);
});

it('resolves the expiry alert when the license is renewed', function (): void {
    travelTo('2026-10-01');
    $license = License::factory()->create(['expires_at' => '2026-10-20', 'seats_total' => 5]);
    artisan('licenses:sync-alerts');

    actingAs(userWithRole(Role::Admin))
        ->put("/licenses/{$license->id}", [
            'name' => $license->name,
            'license_type' => 'subscription',
            'seats_total' => 5,
            'expires_at' => '2027-10-20',
        ])
        ->assertSessionHasNoErrors();

    expect(openLicenseAlert($license, AlertType::LicenseExpiring))->toBeNull();
});

it('stores the product key encrypted and only ever shows it masked', function (): void {
    actingAs(userWithRole(Role::Admin))
        ->post('/licenses', [
            'name' => 'Microsoft 365 E3',
            'license_type' => 'subscription',
            'product_key' => 'ABCDE-12345-FGHIJ-WXYZ',
            'seats_total' => 5,
            'annual_cost' => 12000000,
            'expires_at' => now()->addYear()->toDateString(),
        ])
        ->assertRedirect();

    $license = License::query()->sole();

    expect(DB::table('licenses')->value('product_key'))->not->toContain('ABCDE')
        ->and($license->product_key)->toBe('ABCDE-12345-FGHIJ-WXYZ');

    actingAs(userWithRole(Role::Viewer))
        ->get("/licenses/{$license->id}")
        ->assertOk()
        ->assertDontSee('ABCDE-12345')
        ->assertInertia(fn ($page) => $page
            ->component('Licenses/Show')
            ->where('license.product_key_masked', '••••••••••••••••••WXYZ'));
});

it('keeps the stored product key when the edit form leaves it blank', function (): void {
    $license = License::factory()->create(['product_key' => 'KEEP-ME-1234', 'seats_total' => 3]);

    actingAs(userWithRole(Role::Admin))
        ->get("/licenses/{$license->id}/edit")
        ->assertDontSee('KEEP-ME-1234');

    actingAs(userWithRole(Role::Admin))
        ->put("/licenses/{$license->id}", [
            'name' => 'Renamed',
            'license_type' => 'perpetual',
            'product_key' => '',
            'seats_total' => 3,
        ])
        ->assertSessionHasNoErrors();

    expect($license->fresh()->product_key)->toBe('KEEP-ME-1234')
        ->and($license->fresh()->name)->toBe('Renamed');
});

it('does not shrink the kuota total below the seats in use', function (): void {
    $license = License::factory()->create(['seats_total' => 5]);
    LicenseSeat::factory()->count(3)->for($license)->create();

    actingAs(userWithRole(Role::Admin))
        ->put("/licenses/{$license->id}", ['name' => $license->name, 'license_type' => 'subscription', 'seats_total' => 2])
        ->assertSessionHasErrors('seats_total');
});

it('lists licenses with kuota usage for every role', function (Role $role): void {
    $license = License::factory()->create(['seats_total' => 4]);
    LicenseSeat::factory()->count(3)->for($license)->create();
    LicenseSeat::factory()->for($license)->create(['released_at' => now()]);

    actingAs(userWithRole($role))
        ->get('/licenses')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Licenses/Index')
            ->where('licenses.data.0.seats_used', 3)
            ->where('licenses.data.0.seats_total', 4)
            ->where('summary.seats_used', 3));
})->with(Role::cases());

it('limits license management to admins and kuota changes to operators', function (): void {
    $license = License::factory()->create();
    $seat = LicenseSeat::factory()->for($license)->create();
    $employee = Employee::factory()->create();

    actingAs(userWithRole(Role::Technician))->get('/licenses/create')->assertForbidden();
    actingAs(userWithRole(Role::Technician))->get("/licenses/{$license->id}/edit")->assertForbidden();
    actingAs(userWithRole(Role::Viewer))
        ->post("/licenses/{$license->id}/seats", ['holder_type' => 'employee', 'employee_id' => $employee->id])
        ->assertForbidden();
    actingAs(userWithRole(Role::Viewer))->post("/license-seats/{$seat->id}/release")->assertForbidden();
});
