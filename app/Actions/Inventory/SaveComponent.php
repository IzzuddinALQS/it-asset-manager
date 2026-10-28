<?php

namespace App\Actions\Inventory;

use App\Models\Component;
use Illuminate\Support\Facades\DB;

class SaveComponent
{
    public function __construct(private readonly SyncLowStockAlert $syncLowStockAlert) {}

    /**
     * Creates or updates a component, then re-evaluates its low-stock alert.
     *
     * @param  array<string, mixed>  $data  Validated payload from SaveComponentRequest.
     */
    public function handle(Component $component, array $data): Component
    {
        return DB::transaction(function () use ($component, $data): Component {
            $component->fill($data)->save();

            $this->syncLowStockAlert->handle($component);

            return $component;
        });
    }
}
