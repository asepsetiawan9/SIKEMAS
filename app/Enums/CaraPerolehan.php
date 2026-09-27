<?php

declare(strict_types=1);

namespace App\Enums;

enum CaraPerolehan: string
{
    case PEMBELIAN = 'pembelian';
    case HIBAH = 'hibah';
    case SUMBANGAN = 'sumbangan';
    case PRODUKSI_SENDIRI = 'produksi_sendiri';
    case LAINNYA = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::PEMBELIAN => 'Pembelian',
            self::HIBAH => 'Hibah',
            self::SUMBANGAN => 'Sumbangan',
            self::PRODUKSI_SENDIRI => 'Produksi Sendiri',
            self::LAINNYA => 'Lain-lain',
        };
    }
}
