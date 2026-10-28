<?php

namespace App\Enums;

enum ReportType: string
{
    case AssetsByStatus = 'assets-by-status';
    case AssetsByCategory = 'assets-by-category';
    case AssetsByLocation = 'assets-by-location';
    case AssetValue = 'asset-value';
    case LicenseUtilization = 'license-utilization';
    case MaintenanceCost = 'maintenance-cost';
    case Stock = 'stock';

    public function label(): string
    {
        return match ($this) {
            self::AssetsByStatus => 'Aset per status',
            self::AssetsByCategory => 'Aset per kategori',
            self::AssetsByLocation => 'Aset per lokasi',
            self::AssetValue => 'Nilai aset',
            self::LicenseUtilization => 'Lisensi & utilisasi',
            self::MaintenanceCost => 'Biaya maintenance',
            self::Stock => 'Stok komponen & aksesori',
        };
    }

    /**
     * The date the period filter applies to, or null when the report is a snapshot.
     */
    public function dateLabel(): ?string
    {
        return match ($this) {
            self::AssetsByStatus, self::AssetsByCategory, self::AssetsByLocation, self::AssetValue => 'Tanggal pembelian',
            self::LicenseUtilization => 'Tanggal kedaluwarsa',
            self::MaintenanceCost => 'Tanggal selesai',
            self::Stock => null,
        };
    }
}
