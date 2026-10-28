<?php

namespace App\Enums;

enum LicenseType: string
{
    case Subscription = 'subscription';
    case Perpetual = 'perpetual';
    case Oem = 'oem';
    case Concurrent = 'concurrent';

    public function label(): string
    {
        return match ($this) {
            self::Subscription => 'Langganan',
            self::Perpetual => 'Perpetual',
            self::Oem => 'OEM',
            self::Concurrent => 'Concurrent',
        };
    }
}
