<?php

namespace App\Console\Commands;

use App\Actions\Licenses\SyncLicenseAlerts;
use App\Models\License;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('licenses:sync-alerts')]
#[Description('Open or resolve license expiry (H-30) and full-seat alerts')]
class SyncLicenseAlertsCommand extends Command
{
    /**
     * Runs daily because expiry alerts depend on today's date, not on any write.
     */
    public function handle(SyncLicenseAlerts $syncLicenseAlerts): int
    {
        $count = 0;

        License::query()->lazyById()->each(function (License $license) use ($syncLicenseAlerts, &$count): void {
            $syncLicenseAlerts->handle($license);
            $count++;
        });

        $this->info("{$count} lisensi diperiksa.");

        return self::SUCCESS;
    }
}
