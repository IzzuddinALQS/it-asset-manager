<?php

namespace App\Actions\Inventory;

use App\Actions\Assets\RecordAssetLog;
use App\Enums\AssetAction;
use App\Exceptions\InsufficientStockException;
use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RemoveComponent
{
    public function __construct(
        private readonly RecordAssetLog $recordLog,
        private readonly SyncLowStockAlert $syncLowStockAlert,
    ) {}

    /**
     * Returns the units to stock by closing the installation; `component_stock` does the counting.
     */
    public function handle(ComponentInstallation $installation, User $actor, ?string $note = null): ComponentInstallation
    {
        return DB::transaction(function () use ($installation, $actor, $note): ComponentInstallation {
            $installation = ComponentInstallation::query()->whereKey($installation->getKey())->lockForUpdate()->firstOrFail();

            if ($installation->removed_at !== null) {
                throw InsufficientStockException::alreadyClosed();
            }

            $installation->forceFill([
                'removed_at' => Carbon::now(),
                'removed_by' => $actor->id,
            ])->save();

            /** @var Component $component */
            $component = Component::withTrashed()->findOrFail($installation->component_id);

            $this->recordLog->handle(
                $installation->asset()->firstOrFail(),
                AssetAction::ComponentRemoved,
                null,
                null,
                $actor,
                [
                    'note' => $note,
                    'meta' => [
                        'component_id' => $component->id,
                        'component' => $component->name,
                        'qty' => $installation->qty,
                        'is_factory' => $installation->is_factory,
                    ],
                ],
            );

            $this->syncLowStockAlert->handle($component);

            return $installation;
        });
    }
}
