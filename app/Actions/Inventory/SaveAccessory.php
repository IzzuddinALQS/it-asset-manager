<?php

namespace App\Actions\Inventory;

use App\Models\Accessory;
use Illuminate\Support\Facades\DB;

class SaveAccessory
{
    public function __construct(private readonly SyncLowStockAlert $syncLowStockAlert) {}

    /**
     * Creates or updates an accessory, then re-evaluates its low-stock alert.
     *
     * @param  array<string, mixed>  $data  Validated payload from SaveAccessoryRequest.
     */
    public function handle(Accessory $accessory, array $data): Accessory
    {
        return DB::transaction(function () use ($accessory, $data): Accessory {
            $accessory->fill($data)->save();

            $this->syncLowStockAlert->handle($accessory);

            return $accessory;
        });
    }
}
