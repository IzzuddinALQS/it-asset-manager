<?php

namespace App\Enums;

enum RetireReason: string
{
    case EndOfLife = 'end_of_life';
    case Broken = 'broken';
    case Lost = 'lost';
    case Sold = 'sold';
    case Donated = 'donated';
    case Destroyed = 'destroyed';

    public function label(): string
    {
        return match ($this) {
            self::EndOfLife => 'Habis masa pakai',
            self::Broken => 'Rusak berat',
            self::Lost => 'Hilang',
            self::Sold => 'Dijual',
            self::Donated => 'Dihibahkan',
            self::Destroyed => 'Dimusnahkan',
        };
    }
}
