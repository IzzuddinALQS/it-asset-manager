<?php

namespace App\Actions\Maintenance;

use App\Models\MaintenancePlan;
use Illuminate\Support\Facades\DB;

class SaveMaintenancePlan
{
    public function __construct(private readonly SyncMaintenancePlanAlert $syncAlert) {}

    /**
     * @param  array{name: string, category_id: int, location_id?: int|null, interval_months: int, next_due_date: string, pic?: string|null, supplier_id?: int|null, is_active?: bool}  $data
     */
    public function handle(MaintenancePlan $plan, array $data): MaintenancePlan
    {
        return DB::transaction(function () use ($plan, $data): MaintenancePlan {
            // New plans start active unless the form says otherwise.
            $plan->fill(['is_active' => $plan->exists ? $plan->is_active : true, ...$data])->save();

            $this->syncAlert->handle($plan);

            return $plan;
        });
    }
}
