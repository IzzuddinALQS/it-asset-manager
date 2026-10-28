<?php

namespace App\Actions\Inventory;

use App\Exceptions\InsufficientStockException;
use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\AccessoryStock;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CheckoutAccessory
{
    public function __construct(private readonly SyncLowStockAlert $syncLowStockAlert) {}

    /**
     * @param  array{employee_id: int, qty: int, note?: string|null}  $data
     */
    public function handle(Accessory $accessory, array $data, User $actor): AccessoryCheckout
    {
        return DB::transaction(function () use ($accessory, $data, $actor): AccessoryCheckout {
            // Locking the accessory row serializes concurrent checkouts of the same item.
            $accessory = Accessory::query()->whereKey($accessory->getKey())->lockForUpdate()->firstOrFail();
            $available = AccessoryStock::query()->find($accessory->id)->qty_available ?? $accessory->qty_total;

            if ($data['qty'] > $available) {
                throw InsufficientStockException::for($accessory->name, $data['qty'], $available);
            }

            $checkout = $accessory->checkouts()->create([
                'employee_id' => $data['employee_id'],
                'qty' => $data['qty'],
                'checked_out_at' => Carbon::now(),
                'checked_out_by' => $actor->id,
                'note' => $data['note'] ?? null,
            ]);

            $this->syncLowStockAlert->handle($accessory);

            return $checkout;
        });
    }
}
