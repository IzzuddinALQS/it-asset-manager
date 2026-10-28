<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Deployed = 'deployed';
    case Maintenance = 'maintenance';
    case Retired = 'retired';

    /**
     * Allowed status transitions. Retired is a terminal state; maintenance may hand
     * a device straight back to the holder it kept while being repaired.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Ready],
            self::Ready => [self::Deployed, self::Maintenance, self::Retired],
            self::Deployed => [self::Ready, self::Maintenance, self::Retired],
            self::Maintenance => [self::Ready, self::Deployed, self::Retired],
            self::Retired => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Ready => 'Siap Pakai',
            self::Deployed => 'Dipakaikan',
            self::Maintenance => 'Perbaikan',
            self::Retired => 'Dipensiunkan',
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::Ready;
    }

    public function isAssigned(): bool
    {
        return $this === self::Deployed;
    }
}
