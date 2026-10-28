<?php

namespace App\Http\Controllers\Assets;

use App\Actions\Assets\CheckoutAsset;
use App\Exceptions\InvalidAssetTransitionException;
use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assets\CheckoutAssetRequest;
use App\Models\Asset;
use Illuminate\Http\RedirectResponse;

class CheckoutAssetController extends Controller
{
    use ResolvesActor;

    public function __invoke(CheckoutAssetRequest $request, Asset $asset, CheckoutAsset $checkoutAsset): RedirectResponse
    {
        $employeeId = $request->integer('assigned_employee_id');
        $locationId = $request->integer('assigned_location_id');
        $note = $request->string('note')->trim()->toString();

        try {
            $checkoutAsset->handle($asset, [
                'assigned_employee_id' => $employeeId > 0 ? $employeeId : null,
                'assigned_location_id' => $locationId > 0 ? $locationId : null,
                'expected_return_date' => $request->date('expected_return_date')?->toDateString(),
                'note' => $note === '' ? null : $note,
            ], $this->actor($request));
        } catch (InvalidAssetTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Aset {$asset->asset_tag} berhasil di-check-out.");
    }
}
