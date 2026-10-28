<?php

namespace App\Actions\Inventory;

use App\Exceptions\InsufficientStockException;
use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CheckinAccessory
{
    public function __construct(private readonly SyncLowStockAlert $syncLowStockAlert) {}

    /**
     * A partial return splits the checkout: the open row keeps the units still on loan and a
     * new closed row records the returned units, so history shows what came back and when.
     *
     * @return AccessoryCheckout The closed row holding the returned units.
     */
    public function handle(AccessoryCheckout $checkout, int $qty, User $actor, ?string $note = null): AccessoryCheckout
    {
        return DB::transaction(function () use ($checkout, $qty, $actor, $note): AccessoryCheckout {
            $checkout = AccessoryCheckout::query()->whereKey($checkout->getKey())->lockForUpdate()->firstOrFail();

            if (! $checkout->isActive()) {
                throw InsufficientStockException::alreadyClosed();
            }

            if ($qty > $checkout->qty) {
                throw InsufficientStockException::returnExceedsCheckout($qty, $checkout->qty);
            }

            $closing = [
                'checked_in_at' => Carbon::now(),
                'checked_in_by' => $actor->id,
                'note' => $note ?? $checkout->note,
            ];

            if ($qty === $checkout->qty) {
                $checkout->forceFill($closing)->save();
                $returned = $checkout;
            } else {
                $checkout->forceFill(['qty' => $checkout->qty - $qty])->save();
                $returned = $checkout->replicate()->forceFill([...$closing, 'qty' => $qty]);
                $returned->save();
            }

            /** @var Accessory $accessory */
            $accessory = Accessory::withTrashed()->findOrFail($checkout->accessory_id);
            $this->syncLowStockAlert->handle($accessory);

            return $returned;
        });
    }
}
