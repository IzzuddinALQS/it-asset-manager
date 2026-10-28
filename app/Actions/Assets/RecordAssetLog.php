<?php

namespace App\Actions\Assets;

use App\Enums\AssetAction;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\User;

/**
 * Single writer for `asset_logs`. Every asset action funnels through here so the
 * history stays consistent and always lives inside the caller's transaction.
 */
class RecordAssetLog
{
    /**
     * @param  array{note?: string|null, employee_id?: int|null, location_id?: int|null, meta?: array<string, mixed>|null}  $attributes
     */
    public function handle(
        Asset $asset,
        AssetAction $action,
        ?AssetStatus $fromStatus,
        ?AssetStatus $toStatus,
        ?User $performedBy,
        array $attributes = [],
    ): AssetLog {
        return $asset->logs()->create([
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'employee_id' => $attributes['employee_id'] ?? null,
            'location_id' => $attributes['location_id'] ?? null,
            'note' => $attributes['note'] ?? null,
            'meta' => $attributes['meta'] ?? null,
            'performed_by' => $performedBy?->id,
        ]);
    }
}
