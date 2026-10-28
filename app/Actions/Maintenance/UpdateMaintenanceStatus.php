<?php

namespace App\Actions\Maintenance;

use App\Enums\MaintenanceStatus;
use App\Exceptions\MaintenanceException;
use App\Models\Maintenance;
use Illuminate\Support\Facades\DB;

/**
 * Moves an open ticket between the working columns of the board. Closing a ticket
 * goes through CompleteMaintenance or CancelMaintenance so the asset is released.
 */
class UpdateMaintenanceStatus
{
    public function handle(Maintenance $maintenance, MaintenanceStatus $target): Maintenance
    {
        return DB::transaction(function () use ($maintenance, $target): Maintenance {
            $maintenance = Maintenance::query()->whereKey($maintenance->getKey())->lockForUpdate()->firstOrFail();

            $isWorkingColumn = in_array($target, [MaintenanceStatus::InProgress, MaintenanceStatus::WaitingParts], true);

            if (! $isWorkingColumn || ! $maintenance->status->canTransitionTo($target)) {
                throw MaintenanceException::invalidTransition($maintenance->status, $target);
            }

            $maintenance->update(['status' => $target]);

            return $maintenance;
        });
    }
}
