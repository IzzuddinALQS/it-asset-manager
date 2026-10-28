<?php

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Enums\AssetStatus;
use App\Enums\EmploymentStatus;
use App\Jobs\GenerateAlertsJob;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\License;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\Schedule;

use function Pest\Laravel\travel;

function openAlertsFor(object $subject, AlertType $type): int
{
    return Alert::open()->where('type', $type)
        ->where('subject_type', $subject->getMorphClass())
        ->where('subject_id', $subject->getKey())
        ->count();
}

it('is idempotent against the seeded data', function (): void {
    app(DemoDataSeeder::class)->run();
    $before = Alert::orderBy('id')->get(['id', 'type', 'subject_type', 'subject_id', 'resolved_at'])->toArray();

    GenerateAlertsJob::dispatchSync();
    GenerateAlertsJob::dispatchSync();

    expect(Alert::orderBy('id')->get(['id', 'type', 'subject_type', 'subject_id', 'resolved_at'])->toArray())->toBe($before);

    foreach (AlertType::cases() as $type) {
        expect(Alert::open()->where('type', $type)->exists())->toBeTrue("No open {$type->value} alert in seed data");
    }
});

it('opens a warranty alert from H-30 and resolves it after expiry', function (): void {
    $asset = Asset::factory()->create(['status' => AssetStatus::Ready, 'warranty_until' => now()->addDays(31)]);

    GenerateAlertsJob::dispatchSync();
    expect(openAlertsFor($asset, AlertType::WarrantyExpiring))->toBe(0);

    travel(1)->day();
    GenerateAlertsJob::dispatchSync();
    expect(openAlertsFor($asset, AlertType::WarrantyExpiring))->toBe(1);

    travel(31)->days();
    GenerateAlertsJob::dispatchSync();
    expect(openAlertsFor($asset, AlertType::WarrantyExpiring))->toBe(0);
});

it('flags overdue returns until the asset is checked in', function (): void {
    $employee = Employee::factory()->create();
    $asset = Asset::factory()->create([
        'status' => AssetStatus::Deployed,
        'assigned_employee_id' => $employee->id,
        'expected_return_date' => now()->subDays(10),
    ]);

    GenerateAlertsJob::dispatchSync();

    $alert = Alert::open()->where('type', AlertType::OverdueReturn)->sole();
    expect($alert->severity)->toBe(AlertSeverity::Critical)
        ->and($alert->message)->toContain($employee->name);

    $asset->update(['status' => AssetStatus::Ready, 'assigned_employee_id' => null, 'expected_return_date' => null]);
    GenerateAlertsJob::dispatchSync();

    expect(openAlertsFor($asset, AlertType::OverdueReturn))->toBe(0);
});

it('raises offboarding alerts only for leavers who still hold items', function (): void {
    $leaver = Employee::factory()->create(['employment_status' => EmploymentStatus::Resigned]);
    $emptyHanded = Employee::factory()->create(['employment_status' => EmploymentStatus::Resigned]);
    $active = Employee::factory()->create();
    $asset = Asset::factory()->create(['status' => AssetStatus::Deployed, 'assigned_employee_id' => $leaver->id]);
    Asset::factory()->create(['status' => AssetStatus::Deployed, 'assigned_employee_id' => $active->id]);

    GenerateAlertsJob::dispatchSync();

    expect(openAlertsFor($leaver, AlertType::EmployeeOffboarding))->toBe(1)
        ->and(openAlertsFor($emptyHanded, AlertType::EmployeeOffboarding))->toBe(0)
        ->and(openAlertsFor($active, AlertType::EmployeeOffboarding))->toBe(0);

    $asset->update(['status' => AssetStatus::Ready, 'assigned_employee_id' => null]);
    GenerateAlertsJob::dispatchSync();

    expect(openAlertsFor($leaver, AlertType::EmployeeOffboarding))->toBe(0);
});

it('resolves alerts whose subject was deleted', function (): void {
    $license = License::factory()->create(['expires_at' => now()->addDays(5)]);
    GenerateAlertsJob::dispatchSync();
    expect(openAlertsFor($license, AlertType::LicenseExpiring))->toBe(1);

    $license->delete();
    GenerateAlertsJob::dispatchSync();

    expect(openAlertsFor($license, AlertType::LicenseExpiring))->toBe(0);
});

it('is scheduled daily', function (): void {
    $event = collect(Schedule::events())->first(fn ($event): bool => str_contains((string) $event->description, GenerateAlertsJob::class));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 6 * * *');
});
