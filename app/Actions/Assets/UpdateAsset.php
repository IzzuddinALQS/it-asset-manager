<?php

namespace App\Actions\Assets;

use App\Enums\AssetAction;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateAsset
{
    /**
     * Fields the action is allowed to touch. Status and holder columns are
     * deliberately excluded: those change only through their own actions.
     *
     * @var list<string>
     */
    private const array EDITABLE = [
        'asset_model_id',
        'serial_number',
        'location_id',
        'supplier_id',
        'purchase_order',
        'purchase_date',
        'purchase_cost',
        'warranty_until',
        'notes',
    ];

    public function __construct(private readonly RecordAssetLog $recordLog) {}

    /**
     * @param  array<string, mixed>  $data  Validated payload from UpdateAssetRequest.
     */
    public function handle(Asset $asset, array $data, User $actor): Asset
    {
        return DB::transaction(function () use ($asset, $data, $actor): Asset {
            $asset = Asset::query()->whereKey($asset->getKey())->lockForUpdate()->firstOrFail();

            $asset->fill(collect($data)->only(self::EDITABLE)->all());

            if (! $asset->isDirty()) {
                return $asset;
            }

            $changedFields = array_keys($asset->getDirty());
            $asset->save();

            $this->recordLog->handle(
                $asset,
                AssetAction::Updated,
                $asset->status,
                $asset->status,
                $actor,
                ['note' => 'Data aset diperbarui', 'meta' => ['changed_fields' => $changedFields]],
            );

            return $asset;
        });
    }
}
