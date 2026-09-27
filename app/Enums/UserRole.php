<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case STAF_UMUM = 'staf_umum';
    case STAF_KEUANGAN = 'staf_keuangan';
    case KASI = 'kasi';
    case SEKMAT = 'sekmat';
    case CAMAT = 'camat';

    public function label(): string
    {
        return match ($this) {
            self::STAF_UMUM => 'Staf Kasubag Umum',
            self::STAF_KEUANGAN => 'Staf Keuangan',
            self::KASI => 'Kasi',
            self::SEKMAT => 'Sekretaris Kecamatan',
            self::CAMAT => 'Camat',
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
