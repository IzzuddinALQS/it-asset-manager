<?php

namespace App\Jobs;

use App\Actions\Alerts\SyncAssetAlerts;
use App\Actions\Alerts\SyncOffboardingAlert;
use App\Actions\Inventory\SyncLowStockAlert;
use App\Actions\Licenses\SyncLicenseAlerts;
use App\Actions\Maintenance\SyncMaintenancePlanAlert;
use App\Enums\AlertType;
use App\Models\Accessory;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Component;
use App\Models\Employee;
use App\Models\License;
use App\Models\MaintenancePlan;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

/**
 * Daily sweep that opens or resolves every date- and state-driven alert. Each sync
 * keeps at most one open alert per (type, subject), so running it twice is a no-op.
 */
class GenerateAlertsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(
        SyncAssetAlerts $syncAssetAlerts,
        SyncLicenseAlerts $syncLicenseAlerts,
        SyncOffboardingAlert $syncOffboardingAlert,
        SyncLowStockAlert $syncLowStockAlert,
        SyncMaintenancePlanAlert $syncMaintenancePlanAlert,
    ): void {
        Asset::withTrashed()
            ->with(['assignedEmployee:id,name', 'assignedLocation:id,name'])
            ->lazyById()
            ->each(fn (Asset $asset) => $syncAssetAlerts->handle($asset));

        License::withTrashed()->lazyById()->each(fn (License $license) => $syncLicenseAlerts->handle($license));

        Employee::query()
            ->needsOffboarding()
            ->orWhereHas('alerts', fn (Builder $query) => $query->open()->where('type', AlertType::EmployeeOffboarding))
            ->lazyById()
            ->each(fn (Employee $employee) => $syncOffboardingAlert->handle($employee));

        Component::query()->lazyById()->each(fn (Component $component) => $syncLowStockAlert->handle($component));
        Accessory::query()->lazyById()->each(fn (Accessory $accessory) => $syncLowStockAlert->handle($accessory));

        MaintenancePlan::query()->lazyById()->each(fn (MaintenancePlan $plan) => $syncMaintenancePlanAlert->handle($plan));

        $this->resolveOrphanedAlerts();
    }

    /**
     * Alerts whose subject was deleted (or soft-deleted) can no longer be acted on.
     */
    private function resolveOrphanedAlerts(): void
    {
        Alert::query()->open()->with('subject')->lazyById()->each(function (Alert $alert): void {
            if ($alert->subject === null) {
                $alert->update(['resolved_at' => Carbon::now()]);
            }
        });
    }
}
