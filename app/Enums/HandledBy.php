<?php

namespace App\Enums;

enum HandledBy: string
{
    case Internal = 'internal';
    case Vendor = 'vendor';
    case Warranty = 'warranty';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Internal IT',
            self::Vendor => 'Vendor',
            self::Warranty => 'Garansi',
        };
    }
}
