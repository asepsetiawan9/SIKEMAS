<?php

declare(strict_types=1);

namespace App\Enums;

enum NotifikasiTipe: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ACTION = 'action';

    public function label(): string
    {
        return match ($this) {
            self::INFO => 'Informasi',
            self::WARNING => 'Peringatan',
            self::ACTION => 'Tindakan Diperlukan',
        };
    }
}
