<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status kelengkapan dokumen bukti belanja.
 * Otomatis dihitung berdasarkan keberadaan file dokumen.
 */
enum StatusDokumen: string
{
    case BELUM_LENGKAP = 'belum_lengkap';
    case LENGKAP = 'lengkap';

    public function label(): string
    {
        return match ($this) {
            self::BELUM_LENGKAP => 'Belum Lengkap',
            self::LENGKAP => 'Lengkap',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BELUM_LENGKAP => 'red',
            self::LENGKAP => 'green',
        };
    }
}
