<?php

namespace App\Actions\Licenses;

use App\Exceptions\LicenseSeatException;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AssignLicenseSeat
{
    public function __construct(private readonly SyncLicenseAlerts $syncLicenseAlerts) {}

    /**
     * Gives one seat to an employee or an asset, refusing once every seat is taken.
     */
    public function handle(License $license, Employee|Asset $holder, User $actor): LicenseSeat
    {
        return DB::transaction(function () use ($license, $holder, $actor): LicenseSeat {
            // Locking the license row serializes concurrent assignments, so the count below stays true.
            $license = License::query()->whereKey($license->getKey())->lockForUpdate()->firstOrFail();
            $holderColumn = $holder instanceof Employee ? 'employee_id' : 'asset_id';

            if ($license->activeSeats()->where($holderColumn, $holder->getKey())->exists()) {
                throw LicenseSeatException::alreadyAssigned($holder instanceof Employee ? $holder->name : $holder->asset_tag);
            }

            if ($license->seatsUsed() >= $license->seats_total) {
                throw LicenseSeatException::seatsFull($license->name, $license->seats_total);
            }

            $seat = $license->seats()->create([
                $holderColumn => $holder->getKey(),
                'assigned_at' => Carbon::now(),
                'assigned_by' => $actor->id,
            ]);

            $this->syncLicenseAlerts->handle($license);

            return $seat;
        });
    }
}
