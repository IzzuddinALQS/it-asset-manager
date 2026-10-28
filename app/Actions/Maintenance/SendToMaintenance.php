<?php

namespace App\Actions\Maintenance;

use App\Actions\Assets\RecordAssetLog;
use App\Enums\AssetAction;
use App\Enums\AssetStatus;
use App\Enums\HandledBy;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Exceptions\MaintenanceException;
use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Opens a ticket and moves the asset into maintenance, remembering the status it
 * came from. A deployed asset keeps its holder so it can be handed straight back.
 */
class SendToMaintenance
{
    public function __construct(
        private readonly GenerateTicketNumber $generateTicketNumber,
        private readonly RecordAssetLog $recordLog,
    ) {}

    /**
     * @param  array{type: string, priority?: string|null, title: string, description?: string|null, handled_by?: string|null, supplier_id?: int|null, reported_by_employee_id?: int|null, estimated_cost?: numeric-string|int|float|null, start_date?: string|null, target_date?: string|null, plan_id?: int|null}  $data
     */
    public function handle(Asset $asset, array $data, User $actor): Maintenance
    {
        return DB::transaction(function () use ($asset, $data, $actor): Maintenance {
            $asset = Asset::query()->whereKey($asset->getKey())->lockForUpdate()->firstOrFail();

            /** @var Maintenance|null $openTicket */
            $openTicket = $asset->maintenances()->open()->first();

            if ($openTicket !== null) {
                throw MaintenanceException::alreadyOpen($asset->asset_tag, $openTicket->ticket_number);
            }

            // An asset already in maintenance without a ticket (e.g. checked in as broken) is adopted as-is.
            $alreadyInMaintenance = $asset->status === AssetStatus::Maintenance;

            if (! $alreadyInMaintenance && ! $asset->status->canTransitionTo(AssetStatus::Maintenance)) {
                throw MaintenanceException::assetUnavailable($asset->asset_tag, $asset->status->label());
            }

            $statusBefore = $alreadyInMaintenance ? AssetStatus::Ready : $asset->status;

            $maintenance = $asset->maintenances()->create([
                'ticket_number' => $this->generateTicketNumber->handle(),
                'plan_id' => $data['plan_id'] ?? null,
                'type' => MaintenanceType::from($data['type']),
                'status' => MaintenanceStatus::Reported,
                'priority' => MaintenancePriority::from($data['priority'] ?? MaintenancePriority::Medium->value),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'handled_by' => HandledBy::from($data['handled_by'] ?? HandledBy::Internal->value),
                'supplier_id' => $data['supplier_id'] ?? null,
                'reported_by_employee_id' => $data['reported_by_employee_id'] ?? null,
                'estimated_cost' => $data['estimated_cost'] ?? null,
                'start_date' => $data['start_date'] ?? Carbon::today()->toDateString(),
                'target_date' => $data['target_date'] ?? null,
                'asset_status_before' => $statusBefore,
                'created_by' => $actor->id,
            ]);

            if (! $alreadyInMaintenance) {
                $asset->forceFill(['status' => AssetStatus::Maintenance])->save();
            }

            $this->recordLog->handle(
                $asset,
                AssetAction::MaintenanceStart,
                $alreadyInMaintenance ? null : $statusBefore,
                AssetStatus::Maintenance,
                $actor,
                [
                    'note' => "Tiket {$maintenance->ticket_number} dibuka: {$maintenance->title}",
                    'meta' => ['maintenance_id' => $maintenance->id, 'ticket_number' => $maintenance->ticket_number],
                ],
            );

            return $maintenance;
        });
    }
}
