<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Active = 'active';
    case Resigned = 'resigned';
    case Transferred = 'transferred';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Resigned => 'Resign',
            self::Transferred => 'Mutasi',
            self::Inactive => 'Nonaktif',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    /**
     * Statuses that require offboarding of held items.
     *
     * @return array<int, self>
     */
    public static function offboardingStatuses(): array
    {
        return [self::Resigned, self::Transferred, self::Inactive];
    }
}
