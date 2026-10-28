<?php

namespace App\Http\Controllers\Assets;

use App\Actions\Assets\RetireAsset;
use App\Exceptions\InvalidAssetTransitionException;
use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assets\RetireAssetRequest;
use App\Models\Asset;
use Illuminate\Http\RedirectResponse;

class RetireAssetController extends Controller
{
    use ResolvesActor;

    public function __invoke(RetireAssetRequest $request, Asset $asset, RetireAsset $retireAsset): RedirectResponse
    {
        $note = $request->string('note')->trim()->toString();

        try {
            $retireAsset->handle($asset, [
                'retire_reason' => $request->string('retire_reason')->toString(),
                'note' => $note === '' ? null : $note,
            ], $this->actor($request));
        } catch (InvalidAssetTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Aset {$asset->asset_tag} dipensiunkan.");
    }
}
