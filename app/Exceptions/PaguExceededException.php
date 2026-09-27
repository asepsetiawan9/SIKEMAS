<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

class PaguExceededException extends DomainException
{
    public static function forNominal(float $nominal, float $sisaPagu): self
    {
        $formattedNominal = 'Rp ' . number_format($nominal, 2, ',', '.');
        $formattedSisa = 'Rp ' . number_format($sisaPagu, 2, ',', '.');

        return new self("Nominal pengajuan ({$formattedNominal}) melebihi sisa pagu anggaran kegiatan ({$formattedSisa}).");
    }
}
