<?php

namespace App\Actions\Inventory;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\Accessory;
use App\Models\AccessoryStock;
use App\Models\Alert;
use App\Models\Component;
use App\Models\ComponentStock;
use Illuminate\Support\Carbon;

/**
 * Keeps exactly one open `low_stock` alert per item while its available stock is at
 * or below the minimum, and resolves it automatically once stock recovers.
 */
class SyncLowStockAlert
{
    public function handle(Component|Accessory $item): void
    {
        $available = $this->availableQty($item);

        /** @var Alert|null $openAlert */
        $openAlert = $item->alerts()->open()->where('type', AlertType::LowStock)->first();

        if ($available > $item->min_qty) {
            $openAlert?->forceFill(['resolved_at' => Carbon::now()])->save();

            return;
        }

        $attributes = [
            'severity' => $available === 0 ? AlertSeverity::Critical : AlertSeverity::Warning,
            'message' => "Stok {$item->name} tersisa {$available} unit (minimum {$item->min_qty}).",
        ];

        if ($openAlert !== null) {
            $openAlert->update($attributes);

            return;
        }

        $item->alerts()->create([...$attributes, 'type' => AlertType::LowStock]);
    }

    private function availableQty(Component|Accessory $item): int
    {
        $stock = $item instanceof Component
            ? ComponentStock::query()->find($item->id)
            : AccessoryStock::query()->find($item->id);

        return $stock->qty_available ?? $item->qty_total;
    }
}
