<?php

namespace App\Actions\Licenses;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\Alert;
use App\Models\License;
use Illuminate\Support\Carbon;

/**
 * Keeps at most one open `license_expiring` alert (from H-30 onwards) and one open
 * `license_seats_full` alert per license, resolving each once the condition clears.
 */
class SyncLicenseAlerts
{
    public const int EXPIRY_WARNING_DAYS = 30;

    public function handle(License $license): void
    {
        $this->syncExpiry($license);
        $this->syncSeatsFull($license);
    }

    private function syncExpiry(License $license): void
    {
        $expiresAt = $license->expires_at;
        $daysLeft = $expiresAt === null ? null : (int) Carbon::today()->diffInDays($expiresAt, false);

        if ($license->trashed() || $daysLeft === null || $daysLeft > self::EXPIRY_WARNING_DAYS) {
            $this->resolve($license, AlertType::LicenseExpiring);

            return;
        }

        $this->open($license, AlertType::LicenseExpiring, [
            'severity' => $daysLeft <= 7 ? AlertSeverity::Critical : AlertSeverity::Warning,
            'message' => $daysLeft < 0
                ? "Lisensi {$license->name} sudah habis sejak {$expiresAt->translatedFormat('j M Y')}."
                : "Lisensi {$license->name} habis dalam {$daysLeft} hari ({$expiresAt->translatedFormat('j M Y')}).",
            'due_date' => $expiresAt->toDateString(),
        ]);
    }

    private function syncSeatsFull(License $license): void
    {
        $used = $license->seatsUsed();

        if ($license->trashed() || $used < $license->seats_total) {
            $this->resolve($license, AlertType::LicenseSeatsFull);

            return;
        }

        $this->open($license, AlertType::LicenseSeatsFull, [
            'severity' => AlertSeverity::Info,
            'message' => "Seat {$license->name} penuh ({$used} dari {$license->seats_total} terpakai).",
            'due_date' => null,
        ]);
    }

    /**
     * @param  array{severity: AlertSeverity, message: string, due_date: string|null}  $attributes
     */
    private function open(License $license, AlertType $type, array $attributes): void
    {
        /** @var Alert|null $openAlert */
        $openAlert = $license->alerts()->open()->where('type', $type)->first();

        if ($openAlert !== null) {
            $openAlert->update($attributes);

            return;
        }

        $license->alerts()->create([...$attributes, 'type' => $type]);
    }

    private function resolve(License $license, AlertType $type): void
    {
        $license->alerts()->open()->where('type', $type)->update(['resolved_at' => Carbon::now()]);
    }
}
