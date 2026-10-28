<?php

namespace App\Actions\Assets;

use App\Enums\AssetAction;
use App\Enums\AssetStatus;
use App\Exceptions\InvalidAssetTransitionException;
use App\Models\Asset;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Promotes a pending asset to ready and gives it its permanent asset tag.
 */
class TagAsset
{
    public function __construct(
        private readonly GenerateAssetTag $generateTag,
        private readonly RecordAssetLog $recordLog,
    ) {}

    public function handle(Asset $asset, User $actor, ?string $note = null): Asset
    {
        return DB::transaction(function () use ($asset, $actor, $note): Asset {
            $asset = Asset::query()->whereKey($asset->getKey())->lockForUpdate()->firstOrFail();

            if (! $asset->status->canTransitionTo(AssetStatus::Ready)) {
                throw InvalidAssetTransitionException::between($asset->status, AssetStatus::Ready);
            }

            $assetModel = $asset->assetModel()->with('category')->firstOrFail();

            if ($assetModel->category !== null) {
                Category::query()->whereKey($assetModel->category->getKey())->lockForUpdate()->first();
            }

            $fromStatus = $asset->status;
            $assetTag = Str::startsWith($asset->asset_tag, 'TMP-')
                ? $this->generateTag->handle($assetModel)
                : $asset->asset_tag;

            $asset->forceFill([
                'asset_tag' => $assetTag,
                'status' => AssetStatus::Ready,
            ])->save();

            $this->recordLog->handle(
                $asset,
                AssetAction::Tagged,
                $fromStatus,
                AssetStatus::Ready,
                $actor,
                ['note' => $note ?? "Label QR {$assetTag} dicetak"],
            );

            return $asset;
        });
    }
}
