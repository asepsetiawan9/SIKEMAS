<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Jenis dokumen bukti belanja.
 * 4 jenis standar + "lainnya" yang nama dokumennya bisa di-custom oleh user.
 */
enum JenisDokumen: string
{
    case NOTA = 'nota';
    case KWITANSI = 'kwitansi';
    case FAKTUR = 'faktur';
    case KONTRAK = 'kontrak';
    case LAINNYA = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::NOTA => 'Nota',
            self::KWITANSI => 'Kwitansi',
            self::FAKTUR => 'Faktur',
            self::KONTRAK => 'Dokumen Kontrak',
            self::LAINNYA => 'Dokumen Lainnya',
        };
    }

    /**
     * Jenis dokumen standar (non-custom).
     *
     * @return array<self>
     */
    public static function standar(): array
    {
        return [
            self::NOTA,
            self::KWITANSI,
            self::FAKTUR,
            self::KONTRAK,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(self::cases(), function (array $carry, self $item): array {
            $carry[$item->value] = $item->label();
            return $carry;
        }, []);
    }
}
