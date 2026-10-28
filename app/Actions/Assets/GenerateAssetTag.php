<?php

namespace App\Actions\Assets;

use App\Models\Asset;
use App\Models\AssetModel;
use Illuminate\Support\Str;

/**
 * Builds the next human-readable asset tag, e.g. `LT-00013`.
 *
 * Callers must run this inside a transaction that locks the category row so two
 * concurrent tagging requests cannot pick the same number.
 */
class GenerateAssetTag
{
    public function handle(AssetModel $assetModel): string
    {
        $assetModel->loadMissing('category');

        $prefix = $assetModel->category?->tag_prefix;
        $prefix = $prefix !== null && $prefix !== '' ? $prefix : 'AS';

        $lastTag = Asset::withTrashed()
            ->where('asset_tag', 'like', $prefix.'-%')
            ->orderByRaw('LENGTH(asset_tag) DESC, asset_tag DESC')
            ->value('asset_tag');

        $nextNumber = $lastTag === null ? 1 : ((int) Str::afterLast($lastTag, '-')) + 1;

        return sprintf('%s-%05d', $prefix, $nextNumber);
    }
}
