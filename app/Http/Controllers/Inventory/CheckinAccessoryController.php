<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\CheckinAccessory;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\CheckinAccessoryRequest;
use App\Models\AccessoryCheckout;
use Illuminate\Http\RedirectResponse;

class CheckinAccessoryController extends Controller
{
    use ResolvesActor;

    public function __invoke(CheckinAccessoryRequest $request, AccessoryCheckout $checkout, CheckinAccessory $checkinAccessory): RedirectResponse
    {
        $note = $request->string('note')->trim()->toString();

        try {
            $returned = $checkinAccessory->handle(
                $checkout,
                $request->integer('qty'),
                $this->actor($request),
                $note === '' ? null : $note,
            );
        } catch (InsufficientStockException $exception) {
            return back()->withErrors(['qty' => $exception->getMessage()]);
        }

        return back()->with('success', "{$returned->qty} unit dikembalikan ke stok.");
    }
}
