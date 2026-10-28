<?php

namespace App\Enums;

enum CategoryType: string
{
    case Asset = 'asset';
    case Component = 'component';
    case Accessory = 'accessory';
    case License = 'license';

    public function label(): string
    {
        return match ($this) {
            self::Asset => 'Aset',
            self::Component => 'Komponen',
            self::Accessory => 'Aksesori',
            self::License => 'Lisensi',
        };
    }
}
