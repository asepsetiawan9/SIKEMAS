<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusKegiatan: string
{
    case AKTIF = 'aktif';
    case SELESAI = 'selesai';
    case DIBATALKAN = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::AKTIF => 'Aktif',
            self::SELESAI => 'Selesai',
            self::DIBATALKAN => 'Dibatalkan',
        };
    }
}
