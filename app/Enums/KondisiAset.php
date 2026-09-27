<?php

declare(strict_types=1);

namespace App\Enums;

enum KondisiAset: string
{
    case BAIK = 'baik';
    case RUSAK_RINGAN = 'rusak_ringan';
    case RUSAK_BERAT = 'rusak_berat';

    public function label(): string
    {
        return match ($this) {
            self::BAIK => 'Baik',
            self::RUSAK_RINGAN => 'Rusak Ringan',
            self::RUSAK_BERAT => 'Rusak Berat',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BAIK => 'green',
            self::RUSAK_RINGAN => 'amber',
            self::RUSAK_BERAT => 'red',
        };
    }
}
