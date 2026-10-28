<?php

namespace App\Actions\Maintenance;

use App\Actions\Assets\RecordAssetLog;
use App\Enums\AssetAction;
use App\Enums\AssetStatus;
use App\Exceptions\MaintenanceException;
use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\User;

/**
 * Takes the asset out of maintenance when its ticket closes: back to its holder
 * (deployed) or onto the shelf (ready). Callers own the transaction.
 */
class ReleaseAssetFromMaintenance
{
    public function __construct(private readonly RecordAssetLog $recordLog) {}

    public function handle(Maintenance $maintenance, AssetStatus $target, User $actor, string $note): void
    {
        $asset = Asset::query()->whereKey($maintenance->asset_id)->lockForUpdate()->firstOrFail();

        // Retired while the ticket was open: nothing to give back.
        if ($asset->status !== AssetStatus::Maintenance) {
            return;
        }

        if ($target === AssetStatus::Deployed && ! self::canReturnToHolder($maintenance, $asset)) {
            throw MaintenanceException::cannotReturnToHolder();
        }

        $previousEmployeeId = $asset->assigned_employee_id;
        $previousLocationId = $asset->assigned_location_id;

        $asset->forceFill($target === AssetStatus::Deployed
            ? ['status' => AssetStatus::Deployed]
            : [
                'status' => AssetStatus::Ready,
                'assigned_employee_id' => null,
                'assigned_location_id' => null,
                'assigned_at' => null,
                'expected_return_date' => null,
            ])->save();

        $this->recordLog->handle(
            $asset,
            AssetAction::MaintenanceDone,
            AssetStatus::Maintenance,
            $target,
            $actor,
            [
                'employee_id' => $previousEmployeeId,
                'location_id' => $previousLocationId,
                'note' => $note,
                'meta' => [
                    'maintenance_id' => $maintenance->id,
                    'ticket_number' => $maintenance->ticket_number,
                    'ticket_status' => $maintenance->status->value,
                ],
            ],
        );
    }

    /**
     * Only an asset that was deployed and still has its holder recorded can go straight back.
     */
    public static function canReturnToHolder(Maintenance $maintenance, Asset $asset): bool
    {
        return $maintenance->asset_status_before === AssetStatus::Deployed
            && ($asset->assigned_employee_id !== null || $asset->assigned_location_id !== null);
    }
}
