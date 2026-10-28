<?php

namespace App\Actions\Licenses;

use App\Exceptions\LicenseSeatException;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseLicenseSeat
{
    public function __construct(private readonly SyncLicenseAlerts $syncLicenseAlerts) {}

    /**
     * Closes the seat so it counts as available again; the row stays as history.
     */
    public function handle(LicenseSeat $seat, User $actor): LicenseSeat
    {
        return DB::transaction(function () use ($seat, $actor): LicenseSeat {
            /** @var License $license */
            $license = License::withTrashed()->whereKey($seat->license_id)->lockForUpdate()->firstOrFail();
            $seat = LicenseSeat::query()->whereKey($seat->getKey())->lockForUpdate()->firstOrFail();

            if (! $seat->isActive()) {
                throw LicenseSeatException::alreadyReleased();
            }

            $seat->forceFill([
                'released_at' => Carbon::now(),
                'released_by' => $actor->id,
            ])->save();

            $this->syncLicenseAlerts->handle($license);

            return $seat;
        });
    }
}
