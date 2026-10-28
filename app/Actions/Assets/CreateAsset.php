<?php

namespace App\Actions\Assets;

use App\Enums\AssetAction;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateAsset
{
    public function __construct(private readonly RecordAssetLog $recordLog) {}

    /**
     * @param  array<string, mixed>  $data  Validated payload from StoreAssetRequest.
     */
    public function handle(array $data, User $actor): Asset
    {
        return DB::transaction(function () use ($data, $actor): Asset {
            $asset = Asset::create([
                ...$data,
                'asset_tag' => 'TMP-'.Str::upper(Str::random(8)),
                'status' => AssetStatus::Pending,
                'created_by' => $actor->id,
            ]);

            $this->recordLog->handle(
                $asset,
                AssetAction::Created,
                null,
                AssetStatus::Pending,
                $actor,
                ['note' => 'Aset dicatat dari pengadaan '.($asset->purchase_order ?? 'tanpa nomor PO')],
            );

            return $asset;
        });
    }
}
