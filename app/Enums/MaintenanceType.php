<?php

namespace App\Enums;

enum MaintenanceType: string
{
    case Repair = 'repair';
    case Preventive = 'preventive';
    case Upgrade = 'upgrade';
    case Calibration = 'calibration';

    public function label(): string
    {
        return match ($this) {
            self::Repair => 'Perbaikan',
            self::Preventive => 'Preventif',
            self::Upgrade => 'Upgrade',
            self::Calibration => 'Kalibrasi',
        };
    }
}
