<?php

namespace App\Actions\Maintenance;

use App\Enums\AssetStatus;
use App\Enums\HandledBy;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use App\Models\Asset;
use App\Models\MaintenancePlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Runs a preventive schedule: opens one preventive ticket per matching asset that
 * is in service and free of open tickets, then moves the plan to its next date.
 */
class GeneratePlanTickets
{
    public function __construct(
        private readonly SendToMaintenance $sendToMaintenance,
        private readonly SyncMaintenancePlanAlert $syncAlert,
    ) {}

    /**
     * @return int Number of tickets opened.
     */
    public function handle(MaintenancePlan $plan, User $actor): int
    {
        return DB::transaction(function () use ($plan, $actor): int {
            $plan = MaintenancePlan::query()->whereKey($plan->getKey())->lockForUpdate()->firstOrFail();

            $assets = $this->matchingAssets($plan)->orderBy('asset_tag')->get();
            $targetDate = Carbon::today()->addWeek()->toDateString();

            foreach ($assets as $asset) {
                $this->sendToMaintenance->handle($asset, [
                    'type' => MaintenanceType::Preventive->value,
                    'priority' => MaintenancePriority::Low->value,
                    'title' => $plan->name,
                    'description' => $plan->pic === null ? null : "PIC: {$plan->pic}",
                    'handled_by' => ($plan->supplier_id === null ? HandledBy::Internal : HandledBy::Vendor)->value,
                    'supplier_id' => $plan->supplier_id,
                    'target_date' => $targetDate,
                    'plan_id' => $plan->id,
                ], $actor);
            }

            // Skip intervals that were missed entirely so the next date lands in the future.
            $nextDue = $plan->next_due_date->copy();

            do {
                $nextDue = $nextDue->addMonthsNoOverflow($plan->interval_months);
            } while ($nextDue->lte(Carbon::today()));

            $plan->update(['next_due_date' => $nextDue->toDateString()]);

            $this->syncAlert->handle($plan);

            return $assets->count();
        });
    }

    /**
     * Assets in service that a plan covers and that have no open ticket.
     *
     * @return Builder<Asset>
     */
    public function matchingAssets(MaintenancePlan $plan): Builder
    {
        return Asset::query()
            ->whereIn('status', [AssetStatus::Ready, AssetStatus::Deployed])
            ->when($plan->category_id !== null, fn (Builder $query) => $query
                ->whereHas('assetModel', fn (Builder $query) => $query->where('category_id', $plan->category_id)))
            ->when($plan->location_id !== null, fn (Builder $query) => $query->where('location_id', $plan->location_id))
            ->whereDoesntHave('maintenances', fn (Builder $query) => $query->open());
    }
}
