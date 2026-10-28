<?php

namespace App\Actions\Maintenance;

use App\Actions\Inventory\InstallComponent;
use App\Enums\AssetStatus;
use App\Enums\MaintenanceStatus;
use App\Exceptions\MaintenanceException;
use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CompleteMaintenance
{
    public function __construct(
        private readonly InstallComponent $installComponent,
        private readonly ReleaseAssetFromMaintenance $releaseAsset,
    ) {}

    /**
     * Parts used are installed from stock and linked to the ticket; any stock
     * shortage rolls back the whole completion.
     *
     * @param  array{return_status: string, actual_cost?: numeric-string|int|float|null, components?: list<array{component_id: int, qty: int}>, note?: string|null}  $data
     */
    public function handle(Maintenance $maintenance, array $data, User $actor): Maintenance
    {
        return DB::transaction(function () use ($maintenance, $data, $actor): Maintenance {
            $maintenance = Maintenance::query()->whereKey($maintenance->getKey())->lockForUpdate()->firstOrFail();

            if (! $maintenance->status->canTransitionTo(MaintenanceStatus::Done)) {
                throw MaintenanceException::invalidTransition($maintenance->status, MaintenanceStatus::Done);
            }

            $asset = Asset::query()->findOrFail($maintenance->asset_id);

            foreach ($data['components'] ?? [] as $component) {
                $this->installComponent->handle($asset, [
                    'component_id' => $component['component_id'],
                    'qty' => $component['qty'],
                    'maintenance_id' => $maintenance->id,
                    'note' => "Dipasang pada tiket {$maintenance->ticket_number}",
                ], $actor);
            }

            $maintenance->update([
                'status' => MaintenanceStatus::Done,
                'actual_cost' => $data['actual_cost'] ?? null,
                'completed_at' => Carbon::now(),
            ]);

            $target = AssetStatus::from($data['return_status']);
            $defaultNote = $target === AssetStatus::Deployed
                ? "Tiket {$maintenance->ticket_number} selesai, aset dikembalikan ke pengguna"
                : "Tiket {$maintenance->ticket_number} selesai, aset siap pakai";

            $this->releaseAsset->handle($maintenance, $target, $actor, $data['note'] ?? $defaultNote);

            return $maintenance;
        });
    }
}
