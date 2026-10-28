<?php

namespace App\Console\Commands;

use App\Actions\Maintenance\SyncMaintenancePlanAlert;
use App\Models\MaintenancePlan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('maintenance:sync-alerts')]
#[Description('Open or resolve maintenance_due alerts for preventive schedules (H-7)')]
class SyncMaintenanceAlertsCommand extends Command
{
    /**
     * Runs daily because due alerts depend on today's date, not on any write.
     */
    public function handle(SyncMaintenancePlanAlert $syncAlert): int
    {
        $count = 0;

        MaintenancePlan::query()->lazyById()->each(function (MaintenancePlan $plan) use ($syncAlert, &$count): void {
            $syncAlert->handle($plan);
            $count++;
        });

        $this->info("{$count} jadwal maintenance diperiksa.");

        return self::SUCCESS;
    }
}
