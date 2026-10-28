<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Technician = 'technician';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Technician => 'Teknisi IT',
            self::Viewer => 'Viewer',
        };
    }

    public function canManage(): bool
    {
        return $this === self::Admin;
    }

    public function canOperate(): bool
    {
        return in_array($this, [self::Admin, self::Technician], true);
    }
}
