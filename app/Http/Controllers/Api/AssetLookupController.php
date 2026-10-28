<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;

/**
 * Resolves a scanned or typed asset tag to the asset it belongs to.
 */
class AssetLookupController extends Controller
{
    public function __invoke(string $tag): JsonResponse
    {
        $asset = Asset::query()
            ->with(['assetModel.category', 'assignedEmployee', 'assignedLocation'])
            ->whereRaw('upper(asset_tag) = ?', [mb_strtoupper(trim($tag))])
            ->firstOrFail();

        $this->authorize('view', $asset);

        return response()->json([
            'data' => [
                'id' => $asset->id,
                'asset_tag' => $asset->asset_tag,
                'status' => $asset->status->value,
                'status_label' => $asset->status->label(),
                'model' => $asset->assetModel?->name,
                'category' => $asset->assetModel?->category?->name,
                'holder' => $asset->assignedEmployee->name ?? $asset->assignedLocation?->name,
                'url' => route('assets.show', $asset),
            ],
        ]);
    }
}
