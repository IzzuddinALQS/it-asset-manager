<?php

use App\Jobs\GenerateAlertsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Covers what licenses:sync-alerts and maintenance:sync-alerts did, plus warranty,
// overdue return, low stock and offboarding; those commands remain for manual runs.
Schedule::job(new GenerateAlertsJob)->dailyAt('06:00')->withoutOverlapping();
