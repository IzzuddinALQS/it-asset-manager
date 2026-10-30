<?php

namespace App\Enums;

enum AlertType: string
{
    case EmployeeOffboarding = 'employee_offboarding';
    case WarrantyExpiring = 'warranty_expiring';
    case LicenseExpiring = 'license_expiring';
    case LicenseSeatsFull = 'license_seats_full';
    case OverdueReturn = 'overdue_return';
    case LowStock = 'low_stock';
    case MaintenanceDue = 'maintenance_due';

    public function label(): string
    {
        return match ($this) {
            self::EmployeeOffboarding => 'Offboarding karyawan',
            self::WarrantyExpiring => 'Garansi segera habis',
            self::LicenseExpiring => 'Lisensi segera habis',
            self::LicenseSeatsFull => 'Kuota lisensi penuh',
            self::OverdueReturn => 'Pengembalian terlambat',
            self::LowStock => 'Stok menipis',
            self::MaintenanceDue => 'Maintenance jatuh tempo',
        };
    }
}
