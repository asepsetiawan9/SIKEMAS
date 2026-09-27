<?php

declare(strict_types=1);

namespace App\Enums;

enum JenisKibKir: string
{
    case KIB = 'KIB';
    case KIR = 'KIR';

    public function label(): string
    {
        return match ($this) {
            self::KIB => 'Kartu Inventaris Barang (KIB)',
            self::KIR => 'Kartu Inventaris Ruangan (KIR)',
        };
    }
}
