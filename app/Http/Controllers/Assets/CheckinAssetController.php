<?php

namespace App\Http\Controllers\Assets;

use App\Actions\Assets\CheckinAsset;
use App\Exceptions\InvalidAssetTransitionException;
use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assets\CheckinAssetRequest;
use App\Models\Asset;
use Illuminate\Http\RedirectResponse;

class CheckinAssetController extends Controller
{
    use ResolvesActor;

    public function __invoke(CheckinAssetRequest $request, Asset $asset, CheckinAsset $checkinAsset): RedirectResponse
    {
        $note = $request->string('note')->trim()->toString();

        try {
            $asset = $checkinAsset->handle($asset, [
                'to_status' => $request->string('to_status')->toString(),
                'note' => $note === '' ? null : $note,
            ], $this->actor($request));
        } catch (InvalidAssetTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Aset {$asset->asset_tag} dikembalikan dengan status {$asset->status->label()}.");
    }
}
