<?php

namespace App\Console\Commands;

use App\Actions\Herd\SyncEmployeesFromHerd;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('herd:sync-employees')]
#[Description('Upsert employees granted to this app in HERD and flag offboarding')]
class SyncHerdEmployeesCommand extends Command
{
    public function handle(SyncEmployeesFromHerd $syncEmployees): int
    {
        $run = $syncEmployees->handle();

        if ($run->status === 'failed') {
            $this->error("Sinkronisasi HERD gagal: {$run->error_message}");

            return self::FAILURE;
        }

        $this->info("Sinkronisasi HERD selesai: {$run->created_count} baru, {$run->updated_count} diperbarui, {$run->offboarding_count} offboarding.");

        return self::SUCCESS;
    }
}
