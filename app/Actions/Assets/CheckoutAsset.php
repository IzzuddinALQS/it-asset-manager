<?php

namespace App\Actions\Assets;

use App\Enums\AssetAction;
use App\Enums\AssetStatus;
use App\Exceptions\InvalidAssetTransitionException;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CheckoutAsset
{
    public function __construct(private readonly RecordAssetLog $recordLog) {}

    /**
     * @param  array{assigned_employee_id?: int|null, assigned_location_id?: int|null, expected_return_date?: string|null, note?: string|null}  $data
     */
    public function handle(Asset $asset, array $data, User $actor): Asset
    {
        return DB::transaction(function () use ($asset, $data, $actor): Asset {
            $asset = Asset::query()->whereKey($asset->getKey())->lockForUpdate()->firstOrFail();

            // Only shelf stock is checked out; maintenance hands assets back via CompleteMaintenance.
            if ($asset->status !== AssetStatus::Ready) {
                throw InvalidAssetTransitionException::between($asset->status, AssetStatus::Deployed);
            }

            $employee = isset($data['assigned_employee_id']) ? Employee::query()->find($data['assigned_employee_id']) : null;
            $locationId = $employee === null ? ($data['assigned_location_id'] ?? null) : null;
            $employeeId = $employee?->id;

            if ($employee === null && $locationId === null) {
                throw InvalidAssetTransitionException::withoutRecipient();
            }

            if ($employee !== null && ! $employee->isActive()) {
                throw InvalidAssetTransitionException::inactiveRecipient($employee);
            }

            $asset->forceFill([
                'status' => AssetStatus::Deployed,
                'assigned_employee_id' => $employeeId,
                'assigned_location_id' => $locationId,
                'assigned_at' => Carbon::now(),
                'expected_return_date' => $data['expected_return_date'] ?? null,
                // Physical location follows the holder so lists and reports stay accurate.
                'location_id' => $locationId ?? $employee->location_id ?? $asset->location_id,
            ])->save();

            $this->recordLog->handle(
                $asset,
                AssetAction::Checkout,
                AssetStatus::Ready,
                AssetStatus::Deployed,
                $actor,
                [
                    'employee_id' => $employeeId,
                    'location_id' => $locationId,
                    'note' => $data['note'] ?? null,
                    'meta' => [
                        'expected_return_date' => $data['expected_return_date'] ?? null,
                    ],
                ],
            );

            return $asset;
        });
    }
}
