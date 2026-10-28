<?php

namespace App\Actions\Inventory;

use App\Actions\Assets\RecordAssetLog;
use App\Enums\AssetAction;
use App\Exceptions\InsufficientStockException;
use App\Models\Asset;
use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentStock;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InstallComponent
{
    public function __construct(
        private readonly RecordAssetLog $recordLog,
        private readonly SyncLowStockAlert $syncLowStockAlert,
    ) {}

    /**
     * Factory-fitted parts never came from IT stock, so they skip the availability check
     * and are excluded from `component_stock.qty_installed`.
     *
     * @param  array{component_id: int, qty: int, is_factory?: bool, maintenance_id?: int|null, note?: string|null}  $data
     */
    public function handle(Asset $asset, array $data, User $actor): ComponentInstallation
    {
        return DB::transaction(function () use ($asset, $data, $actor): ComponentInstallation {
            // Locking the component row serializes concurrent installs of the same part.
            $component = Component::query()->whereKey($data['component_id'])->lockForUpdate()->firstOrFail();
            $isFactory = $data['is_factory'] ?? false;
            $qty = $data['qty'];

            if (! $isFactory) {
                $available = ComponentStock::query()->find($component->id)->qty_available ?? $component->qty_total;

                if ($qty > $available) {
                    throw InsufficientStockException::for($component->name, $qty, $available);
                }
            }

            $installation = $component->installations()->create([
                'asset_id' => $asset->id,
                'qty' => $qty,
                'is_factory' => $isFactory,
                'maintenance_id' => $data['maintenance_id'] ?? null,
                'installed_at' => Carbon::now(),
                'installed_by' => $actor->id,
            ]);

            $this->recordLog->handle(
                $asset,
                AssetAction::ComponentInstalled,
                null,
                null,
                $actor,
                [
                    'note' => $data['note'] ?? null,
                    'meta' => [
                        'component_id' => $component->id,
                        'component' => $component->name,
                        'qty' => $qty,
                        'is_factory' => $isFactory,
                        'maintenance_id' => $data['maintenance_id'] ?? null,
                    ],
                ],
            );

            $this->syncLowStockAlert->handle($component);

            return $installation;
        });
    }
}
