<?php

declare(strict_types=1);

namespace App\Enums;

enum SumberDana: string
{
    case APBD = 'APBD';
    case DAU = 'DAU';
    case DAK = 'DAK';
    case BHP = 'BHP';
    case ADD = 'ADD';
    case LAINNYA = 'LAINNYA';

    public function label(): string
    {
        return match ($this) {
            self::APBD => 'APBD Kabupaten',
            self::DAU => 'Dana Alokasi Umum',
            self::DAK => 'Dana Alokasi Khusus',
            self::BHP => 'Bagi Hasil Pajak',
            self::ADD => 'Alokasi Dana Desa',
            self::LAINNYA => 'Lain-lain',
        };
    }
}
