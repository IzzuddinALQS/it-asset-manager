<?php

namespace App\Http\Controllers\Api;

use App\Enums\AssetStatus;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Type-ahead search over assets that can still receive something (not retired).
 * `for=maintenance` narrows it to assets that may get a new ticket.
 */
class AssetSearchController extends Controller
{
    public const int LIMIT = 20;

    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Asset::class);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'for' => ['nullable', 'in:maintenance'],
            'id' => ['nullable', 'integer'],
        ]);
        $term = trim($validated['q'] ?? '');

        $assets = Asset::query()
            ->whereNot('status', AssetStatus::Retired)
            ->when(($validated['for'] ?? null) === 'maintenance', fn ($query) => $query
                ->whereIn('status', [AssetStatus::Ready, AssetStatus::Deployed, AssetStatus::Maintenance])
                ->whereDoesntHave('maintenances', fn ($query) => $query->open()))
            ->with('assetModel:id,name')
            ->when(isset($validated['id']), fn ($query) => $query->whereKey($validated['id']))
            ->when($term !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('asset_tag', 'ilike', "%{$term}%")
                ->orWhere('serial_number', 'ilike', "%{$term}%")
                ->orWhereHas('assetModel', fn ($query) => $query->where('name', 'ilike', "%{$term}%"))))
            ->orderBy('asset_tag')
            ->limit(self::LIMIT)
            ->get(['id', 'asset_tag', 'asset_model_id', 'status']);

        return response()->json([
            'data' => $assets->map(fn (Asset $asset): array => [
                'value' => (string) $asset->id,
                'label' => $asset->asset_tag,
                'hint' => collect([$asset->assetModel?->name, $asset->status->label()])->filter()->implode(' · '),
            ])->all(),
        ]);
    }
}
