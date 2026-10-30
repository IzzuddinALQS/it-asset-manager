<?php

namespace App\Actions\Assets;

use App\Enums\AssetAction;
use App\Enums\AssetStatus;
use App\Enums\RetireReason;
use App\Exceptions\InvalidAssetTransitionException;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RetireAsset
{
    public function __construct(private readonly RecordAssetLog $recordLog) {}

    /**
     * @param  array{retire_reason: string, note?: string|null}  $data
     */
    public function handle(Asset $asset, array $data, User $actor): Asset
    {
        return DB::transaction(function () use ($asset, $data, $actor): Asset {
            $asset = Asset::query()->whereKey($asset->getKey())->lockForUpdate()->firstOrFail();

            if (! $asset->status->canTransitionTo(AssetStatus::Retired)) {
                throw InvalidAssetTransitionException::between($asset->status, AssetStatus::Retired);
            }

            $fromStatus = $asset->status;
            $reason = RetireReason::from($data['retire_reason']);

            $asset->forceFill([
                'status' => AssetStatus::Retired,
                'retired_at' => Carbon::now(),
                'retire_reason' => $reason,
                'assigned_employee_id' => null,
                'assigned_location_id' => null,
                'assigned_at' => null,
                'expected_return_date' => null,
            ])->save();

            // A retired device no longer consumes license seats.
            $releasedSeats = $asset->licenseSeats()
                ->whereNull('released_at')
                ->update(['released_at' => Carbon::now(), 'released_by' => $actor->id]);

            $this->recordLog->handle(
                $asset,
                AssetAction::Retired,
                $fromStatus,
                AssetStatus::Retired,
                $actor,
                [
                    'note' => $data['note'] ?? "Inventaris dipensiunkan: {$reason->label()}",
                    'meta' => ['retire_reason' => $reason->value, 'released_license_seats' => $releasedSeats],
                ],
            );

            return $asset;
        });
    }
}
