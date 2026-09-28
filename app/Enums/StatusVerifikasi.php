<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status verifikasi belanja.
 * Alur simpel: Operator → Sekmat → Camat.
 *
 * State Machine:
 *   draft → diajukan → diverifikasi_sekmat → disetujui_camat (FINAL)
 *                ↓              ↓
 *        dikembalikan_sekmat  dikembalikan_camat
 *                ↓              ↓
 *              draft ←──────────┘
 */
enum StatusVerifikasi: string
{
    case DRAFT = 'draft';
    case DIAJUKAN = 'diajukan';
    case DIVERIFIKASI_SEKMAT = 'diverifikasi_sekmat';
    case DIKEMBALIKAN_SEKMAT = 'dikembalikan_sekmat';
    case DISETUJUI_CAMAT = 'disetujui_camat';
    case DIKEMBALIKAN_CAMAT = 'dikembalikan_camat';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::DIAJUKAN => 'Diajukan ke Sekmat',
            self::DIVERIFIKASI_SEKMAT => 'Diverifikasi Sekmat',
            self::DIKEMBALIKAN_SEKMAT => 'Dikembalikan Sekmat',
            self::DISETUJUI_CAMAT => 'Disetujui Camat',
            self::DIKEMBALIKAN_CAMAT => 'Dikembalikan Camat',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::DIAJUKAN => 'blue',
            self::DIVERIFIKASI_SEKMAT => 'indigo',
            self::DIKEMBALIKAN_SEKMAT => 'amber',
            self::DISETUJUI_CAMAT => 'green',
            self::DIKEMBALIKAN_CAMAT => 'red',
        };
    }

    /**
     * Check if transition to target status is permitted.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::DRAFT => $target === self::DIAJUKAN,
            self::DIAJUKAN => in_array($target, [
                self::DIVERIFIKASI_SEKMAT,
                self::DIKEMBALIKAN_SEKMAT,
            ], true),
            self::DIVERIFIKASI_SEKMAT => in_array($target, [
                self::DISETUJUI_CAMAT,
                self::DIKEMBALIKAN_CAMAT,
            ], true),
            self::DIKEMBALIKAN_SEKMAT => in_array($target, [self::DRAFT, self::DIAJUKAN], true),
            self::DIKEMBALIKAN_CAMAT => in_array($target, [self::DRAFT, self::DIAJUKAN], true),
            self::DISETUJUI_CAMAT => false, // FINAL — immutable
        };
    }

    /**
     * Apakah status ini memungkinkan edit oleh operator?
     */
    public function isEditable(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::DIKEMBALIKAN_SEKMAT,
            self::DIKEMBALIKAN_CAMAT,
        ], true);
    }

    /**
     * Apakah status ini merupakan status final (locked)?
     */
    public function isFinal(): bool
    {
        return $this === self::DISETUJUI_CAMAT;
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
