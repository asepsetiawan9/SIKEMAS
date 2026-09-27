<?php

declare(strict_types=1);

namespace App\Enums;

enum SpjStatus: string
{
    case DRAFT = 'draft';
    case DIAJUKAN_KASI = 'diajukan_kasi';
    case DIKONSOLIDASI = 'dikonsolidasi';
    case DIAJUKAN_VERIFIKASI = 'diajukan_verifikasi';
    case DIVERIFIKASI = 'diverifikasi';
    case DITOLAK = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::DIAJUKAN_KASI => 'Diajukan Kasi',
            self::DIKONSOLIDASI => 'Dikonsolidasi',
            self::DIAJUKAN_VERIFIKASI => 'Diajukan Verifikasi',
            self::DIVERIFIKASI => 'Diverifikasi',
            self::DITOLAK => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::DIAJUKAN_KASI => 'blue',
            self::DIKONSOLIDASI => 'indigo',
            self::DIAJUKAN_VERIFIKASI => 'amber',
            self::DIVERIFIKASI => 'green',
            self::DITOLAK => 'red',
        };
    }

    /**
     * Check if transition to next status is permitted.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::DRAFT => $target === self::DIAJUKAN_KASI,
            self::DIAJUKAN_KASI => $target === self::DIKONSOLIDASI,
            self::DIKONSOLIDASI => $target === self::DIAJUKAN_VERIFIKASI,
            self::DIAJUKAN_VERIFIKASI => in_array($target, [self::DIVERIFIKASI, self::DITOLAK], true),
            self::DITOLAK => $target === self::DIKONSOLIDASI,
            self::DIVERIFIKASI => false, // Immutable final status
        };
    }
}
