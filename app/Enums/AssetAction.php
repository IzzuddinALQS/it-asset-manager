<?php

namespace App\Enums;

enum AssetAction: string
{
    case Created = 'created';
    case Tagged = 'tagged';
    case Checkout = 'checkout';
    case Checkin = 'checkin';
    case MaintenanceStart = 'maintenance_start';
    case MaintenanceDone = 'maintenance_done';
    case ComponentInstalled = 'component_installed';
    case ComponentRemoved = 'component_removed';
    case Updated = 'updated';
    case Audited = 'audited';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Aset dibuat',
            self::Tagged => 'Ditandai siap pakai',
            self::Checkout => 'Check-out',
            self::Checkin => 'Check-in',
            self::MaintenanceStart => 'Masuk perbaikan',
            self::MaintenanceDone => 'Selesai perbaikan',
            self::ComponentInstalled => 'Komponen dipasang',
            self::ComponentRemoved => 'Komponen dilepas',
            self::Updated => 'Data diperbarui',
            self::Audited => 'Audit fisik',
            self::Retired => 'Dipensiunkan',
        };
    }
}
