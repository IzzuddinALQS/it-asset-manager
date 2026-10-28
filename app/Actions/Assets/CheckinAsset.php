<?php

namespace App\Actions\Assets;

use App\Enums\AssetAction;
use App\Enums\AssetStatus;
use App\Exceptions\InvalidAssetTransitionException;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckinAsset
{
    public function __construct(private readonly RecordAssetLog $recordLog) {}

    /**
     * @param  array{to_status?: string|null, note?: string|null}  $data
     */
    public function handle(Asset $asset, array $data, User $actor): Asset
    {
        return DB::transaction(function () use ($asset, $data, $actor): Asset {
            $asset = Asset::query()->whereKey($asset->getKey())->lockForUpdate()->firstOrFail();

            $target = AssetStatus::from($data['to_status'] ?? AssetStatus::Ready->value);

            // Check-in only ends a deployment; maintenance and retirement have their own actions.
            if ($asset->status !== AssetStatus::Deployed || ! in_array($target, [AssetStatus::Ready, AssetStatus::Maintenance], true)) {
                throw InvalidAssetTransitionException::between($asset->status, $target);
            }

            if ($asset->assigned_employee_id === null && $asset->assigned_location_id === null) {
                throw InvalidAssetTransitionException::withoutHolder();
            }

            $previousStatus = $asset->status;
            $previousEmployeeId = $asset->assigned_employee_id;
            $previousLocationId = $asset->assigned_location_id;

            $asset->forceFill([
                'status' => $target,
                'assigned_employee_id' => null,
                'assigned_location_id' => null,
                'assigned_at' => null,
                'expected_return_date' => null,
            ])->save();

            $this->recordLog->handle(
                $asset,
                AssetAction::Checkin,
                $previousStatus,
                $target,
                $actor,
                [
                    'employee_id' => $previousEmployeeId,
                    'location_id' => $previousLocationId,
                    'note' => $data['note'] ?? null,
                    'meta' => ['returned_to' => $target->value],
                ],
            );

            return $asset;
        });
    }
}
