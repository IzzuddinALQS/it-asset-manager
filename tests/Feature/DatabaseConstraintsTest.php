<?php

use App\Enums\AlertType;
use App\Enums\AssetStatus;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Component;
use App\Models\Employee;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\Location;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Runs the callback inside a savepoint so a rejected statement does not abort
 * the surrounding RefreshDatabase transaction on PostgreSQL.
 *
 * @param  Closure(): mixed  $callback
 */
function attemptWrite(Closure $callback): Closure
{
    return fn () => DB::transaction($callback);
}

it('rejects an asset held by both an employee and a location', function (): void {
    expect(attemptWrite(fn () => Asset::factory()->deployed()->create([
        'assigned_location_id' => Location::factory(),
    ])))->toThrow(QueryException::class, 'assets_single_assignee');
});

it('rejects a deployed asset without a holder', function (): void {
    expect(attemptWrite(fn () => Asset::factory()->create([
        'status' => AssetStatus::Deployed,
    ])))->toThrow(QueryException::class, 'assets_deployed_has_assignee');
});

it('allows a deployed asset held by a location only', function (): void {
    $asset = Asset::factory()->create([
        'status' => AssetStatus::Deployed,
        'assigned_location_id' => Location::factory(),
    ]);

    expect($asset->assignedLocation)->toBeInstanceOf(Location::class)
        ->and($asset->assignedEmployee)->toBeNull();
});

it('rejects an asset status outside the enum', function (): void {
    $asset = Asset::factory()->create();

    expect(attemptWrite(fn () => DB::table('assets')->where('id', $asset->id)->update(['status' => 'lost'])))
        ->toThrow(QueryException::class, 'assets_status_check');
});

it('rejects a license seat without an employee or asset', function (): void {
    expect(attemptWrite(fn () => LicenseSeat::factory()->create(['employee_id' => null, 'asset_id' => null])))
        ->toThrow(QueryException::class, 'license_seats_target');
});

it('rejects a license without seats', function (): void {
    expect(attemptWrite(fn () => License::factory()->create(['seats_total' => 0])))
        ->toThrow(QueryException::class, 'licenses_seats_total_check');
});

it('rejects a negative component quantity', function (): void {
    expect(attemptWrite(fn () => Component::factory()->create(['qty_total' => -1])))
        ->toThrow(QueryException::class, 'components_qty_total_check');
});

it('allows only one open alert per type and subject', function (): void {
    $employee = Employee::factory()->create();
    $attributes = ['type' => AlertType::EmployeeOffboarding, 'subject_type' => 'employee', 'subject_id' => $employee->id];

    $first = Alert::factory()->create($attributes);

    expect(attemptWrite(fn () => Alert::factory()->create($attributes)))
        ->toThrow(QueryException::class, 'alerts_open_unique');

    $first->update(['resolved_at' => now()]);

    expect(Alert::factory()->create($attributes)->exists)->toBeTrue();
});
