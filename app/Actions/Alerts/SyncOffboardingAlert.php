<?php

namespace App\Actions\Alerts;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Enums\AssetStatus;
use App\Models\Employee;

/**
 * Keeps one open `employee_offboarding` alert while a resigned, transferred or inactive
 * employee still holds assets, accessories or license seats.
 */
class SyncOffboardingAlert
{
    /** Days after the status change by which held items should be returned. */
    public const int RETURN_WITHIN_DAYS = 7;

    public function __construct(private SyncSubjectAlert $syncSubjectAlert) {}

    public function handle(Employee $employee): void
    {
        $assets = $employee->assets()->whereNot('status', AssetStatus::Retired)->count();
        $accessories = (int) $employee->accessoryCheckouts()->whereNull('checked_in_at')->sum('qty');
        $seats = $employee->licenseSeats()->whereNull('released_at')->count();

        if ($employee->isActive() || $assets + $accessories + $seats === 0) {
            $this->syncSubjectAlert->resolve($employee, AlertType::EmployeeOffboarding);

            return;
        }

        $held = collect([
            $assets > 0 ? "{$assets} aset" : null,
            $accessories > 0 ? "{$accessories} aksesori" : null,
            $seats > 0 ? "{$seats} seat lisensi" : null,
        ])->filter()->join(', ', ' dan ');

        $this->syncSubjectAlert->open($employee, AlertType::EmployeeOffboarding, [
            'severity' => AlertSeverity::Critical,
            'message' => "{$employee->name} berstatus {$employee->employment_status->label()} dan masih memegang {$held}.",
            'due_date' => $employee->status_effective_date?->copy()->addDays(self::RETURN_WITHIN_DAYS)->toDateString(),
        ]);
    }
}
