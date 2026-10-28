<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\CheckoutAccessory;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\CheckoutAccessoryRequest;
use App\Models\Accessory;
use Illuminate\Http\RedirectResponse;

class CheckoutAccessoryController extends Controller
{
    use ResolvesActor;

    public function __invoke(CheckoutAccessoryRequest $request, Accessory $accessory, CheckoutAccessory $checkoutAccessory): RedirectResponse
    {
        $note = $request->string('note')->trim()->toString();

        try {
            $checkout = $checkoutAccessory->handle($accessory, [
                'employee_id' => $request->integer('employee_id'),
                'qty' => $request->integer('qty'),
                'note' => $note === '' ? null : $note,
            ], $this->actor($request));
        } catch (InsufficientStockException $exception) {
            return back()->withErrors(['qty' => $exception->getMessage()]);
        }

        return back()->with('success', "{$checkout->qty}× {$accessory->name} dipinjamkan ke {$checkout->employee?->name}.");
    }
}
