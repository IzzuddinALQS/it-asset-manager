<?php

namespace App\Actions\Licenses;

use App\Models\License;
use Illuminate\Support\Facades\DB;

class SaveLicense
{
    public function __construct(private readonly SyncLicenseAlerts $syncLicenseAlerts) {}

    /**
     * Creates or updates a license, then re-evaluates its expiry and seat alerts.
     * An empty product key on update keeps the stored key, since the form never receives it.
     *
     * @param  array<string, mixed>  $data  Validated payload from SaveLicenseRequest.
     */
    public function handle(License $license, array $data): License
    {
        if ($license->exists && blank($data['product_key'] ?? null)) {
            unset($data['product_key']);
        }

        return DB::transaction(function () use ($license, $data): License {
            $license->fill($data)->save();

            $this->syncLicenseAlerts->handle($license);

            return $license;
        });
    }
}
