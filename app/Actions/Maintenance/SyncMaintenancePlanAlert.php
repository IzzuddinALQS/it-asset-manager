<?php

namespace App\Actions\Maintenance;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\Alert;
use App\Models\MaintenancePlan;
use Illuminate\Support\Carbon;

/**
 * Keeps at most one open `maintenance_due` alert per active plan from H-7 onwards,
 * resolving it once the schedule moves forward or the plan is switched off.
 */
class SyncMaintenancePlanAlert
{
    public const int DUE_WARNING_DAYS = 7;

    public function handle(MaintenancePlan $plan): void
    {
        $dueDate = $plan->next_due_date;
        $daysLeft = (int) Carbon::today()->diffInDays($dueDate, false);

        if (! $plan->is_active || $daysLeft > self::DUE_WARNING_DAYS) {
            $plan->alerts()->open()->where('type', AlertType::MaintenanceDue)->update(['resolved_at' => Carbon::now()]);

            return;
        }

        $attributes = [
            'severity' => $daysLeft < 0 ? AlertSeverity::Critical : AlertSeverity::Warning,
            'message' => $daysLeft < 0
                ? "Jadwal {$plan->name} terlambat sejak {$dueDate->translatedFormat('j M Y')}."
                : "Jadwal {$plan->name} jatuh tempo pada {$dueDate->translatedFormat('j M Y')}.",
            'due_date' => $dueDate->toDateString(),
        ];

        /** @var Alert|null $openAlert */
        $openAlert = $plan->alerts()->open()->where('type', AlertType::MaintenanceDue)->first();

        if ($openAlert !== null) {
            $openAlert->update($attributes);

            return;
        }

        $plan->alerts()->create([...$attributes, 'type' => AlertType::MaintenanceDue]);
    }
}
