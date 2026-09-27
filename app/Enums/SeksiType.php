<?php

declare(strict_types=1);

namespace App\Enums;

enum SeksiType: string
{
    case PEMERINTAHAN = 'pemerintahan';
    case TRANTIB = 'trantib';
    case PMD = 'pmd';
    case KESSOS = 'kessos';
    case PELAYANAN = 'pelayanan';

    public function label(): string
    {
        return match ($this) {
            self::PEMERINTAHAN => 'Pemerintahan',
            self::TRANTIB => 'Ketentraman & Ketertiban',
            self::PMD => 'Pemberdayaan Masyarakat Desa',
            self::KESSOS => 'Kesejahteraan Sosial',
            self::PELAYANAN => 'Pelayanan Umum',
        };
    }

    public function code(): string
    {
        return strtoupper($this->value);
    }
}
