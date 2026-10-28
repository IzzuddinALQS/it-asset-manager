<?php

namespace App\Actions\Alerts;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Enums\AssetStatus;
use App\Models\Asset;
use Illuminate\Support\Carbon;

/**
 * Keeps at most one open `warranty_expiring` alert (H-30 up to the expiry date) and
 * one open `overdue_return` alert per asset, resolving each once the condition clears.
 */
class SyncAssetAlerts
{
    public const int WARRANTY_WARNING_DAYS = 30;

    public function __construct(private SyncSubjectAlert $syncSubjectAlert) {}

    public function handle(Asset $asset): void
    {
        $this->syncWarranty($asset);
        $this->syncOverdueReturn($asset);
    }

    private function syncWarranty(Asset $asset): void
    {
        $warrantyUntil = $asset->warranty_until;
        $daysLeft = $warrantyUntil === null ? null : (int) Carbon::today()->diffInDays($warrantyUntil, false);

        // Lapsed warranties are not actionable, so the alert window closes on the expiry date.
        if ($asset->trashed() || $asset->isRetired() || $daysLeft === null || $daysLeft < 0 || $daysLeft > self::WARRANTY_WARNING_DAYS) {
            $this->syncSubjectAlert->resolve($asset, AlertType::WarrantyExpiring);

            return;
        }

        $this->syncSubjectAlert->open($asset, AlertType::WarrantyExpiring, [
            'severity' => $daysLeft <= 7 ? AlertSeverity::Critical : AlertSeverity::Warning,
            'message' => "Garansi {$asset->asset_tag} berakhir dalam {$daysLeft} hari ({$warrantyUntil->translatedFormat('j M Y')}).",
            'due_date' => $warrantyUntil->toDateString(),
        ]);
    }

    private function syncOverdueReturn(Asset $asset): void
    {
        $expectedReturn = $asset->expected_return_date;

        if ($asset->trashed() || $asset->status !== AssetStatus::Deployed || $expectedReturn === null || ! $expectedReturn->lt(Carbon::today())) {
            $this->syncSubjectAlert->resolve($asset, AlertType::OverdueReturn);

            return;
        }

        $daysLate = (int) $expectedReturn->diffInDays(Carbon::today());
        $holder = $asset->assignedEmployee->name ?? $asset->assignedLocation->name ?? 'pemegang';

        $this->syncSubjectAlert->open($asset, AlertType::OverdueReturn, [
            'severity' => $daysLate > 7 ? AlertSeverity::Critical : AlertSeverity::Warning,
            'message' => "{$asset->asset_tag} seharusnya dikembalikan oleh {$holder} pada {$expectedReturn->translatedFormat('j M Y')} (terlambat {$daysLate} hari).",
            'due_date' => $expectedReturn->toDateString(),
        ]);
    }
}
