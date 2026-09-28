<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Jenis belanja pada RAP/SPJ.
 * Mengikuti klasifikasi manual yang digunakan Kecamatan Caringin.
 */
enum JenisBelanja: string
{
    case CETAK = 'cetak';
    case MAMIN = 'mamin';
    case PERDIN = 'perdin';
    case ATK = 'atk';
    case LAINNYA = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::CETAK => 'Cetak/Fotokopi',
            self::MAMIN => 'Makan dan Minum',
            self::PERDIN => 'Perjalanan Dinas',
            self::ATK => 'Alat Tulis Kantor',
            self::LAINNYA => 'Belanja Lainnya',
        };
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
