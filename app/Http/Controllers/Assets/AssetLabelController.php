<?php

namespace App\Http\Controllers\Assets;

use App\Actions\Assets\RenderAssetQrCode;
use App\Enums\AssetStatus;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Printable A4 sheet of QR labels for one or more tagged assets.
 */
class AssetLabelController extends Controller
{
    private const int MAX_LABELS = 60;

    public function __invoke(Request $request, RenderAssetQrCode $renderQrCode): Response
    {
        $this->authorize('viewAny', Asset::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_LABELS],
            'ids.*' => ['integer'],
        ]);

        $assets = Asset::query()
            ->with('assetModel')
            ->whereKey($validated['ids'])
            ->whereNot('status', AssetStatus::Pending)
            ->orderBy('asset_tag')
            ->get();

        return Inertia::render('Assets/Labels', [
            'labels' => $assets->map(fn (Asset $asset): array => [
                'id' => $asset->id,
                'asset_tag' => $asset->asset_tag,
                'model' => $asset->assetModel?->name,
                'serial_number' => $asset->serial_number,
                'qr_svg' => $renderQrCode->handle($asset),
            ])->all(),
            'skipped' => count($validated['ids']) - $assets->count(),
        ]);
    }
}
