<?php

namespace App\Http\Controllers\Assets;

use App\Actions\Assets\TagAsset;
use App\Exceptions\InvalidAssetTransitionException;
use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assets\TagAssetRequest;
use App\Models\Asset;
use Illuminate\Http\RedirectResponse;

class TagAssetController extends Controller
{
    use ResolvesActor;

    public function __invoke(TagAssetRequest $request, Asset $asset, TagAsset $tagAsset): RedirectResponse
    {
        try {
            $note = $request->string('note')->trim()->toString();

            $tagAsset->handle($asset, $this->actor($request), $note === '' ? null : $note);
        } catch (InvalidAssetTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Inventaris {$asset->fresh()->asset_tag} ditandai siap guna.");
    }
}
