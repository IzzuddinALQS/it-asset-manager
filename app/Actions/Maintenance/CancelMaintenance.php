<?php

namespace App\Actions\Maintenance;

use App\Enums\AssetStatus;
use App\Enums\MaintenanceStatus;
use App\Exceptions\MaintenanceException;
use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cancels an open ticket and puts the asset back where it was before maintenance.
 */
class CancelMaintenance
{
    public function __construct(private readonly ReleaseAssetFromMaintenance $releaseAsset) {}

    public function handle(Maintenance $maintenance, User $actor, ?string $reason = null): Maintenance
    {
        return DB::transaction(function () use ($maintenance, $actor, $reason): Maintenance {
            $maintenance = Maintenance::query()->whereKey($maintenance->getKey())->lockForUpdate()->firstOrFail();

            if (! $maintenance->status->canTransitionTo(MaintenanceStatus::Cancelled)) {
                throw MaintenanceException::invalidTransition($maintenance->status, MaintenanceStatus::Cancelled);
            }

            $maintenance->update(['status' => MaintenanceStatus::Cancelled]);

            $asset = Asset::query()->findOrFail($maintenance->asset_id);
            $target = ReleaseAssetFromMaintenance::canReturnToHolder($maintenance, $asset)
                ? AssetStatus::Deployed
                : AssetStatus::Ready;

            $this->releaseAsset->handle(
                $maintenance,
                $target,
                $actor,
                "Tiket {$maintenance->ticket_number} dibatalkan".($reason === null ? '' : ": {$reason}"),
            );

            return $maintenance;
        });
    }
}
