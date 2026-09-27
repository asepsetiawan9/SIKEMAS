<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

class SpjStatusTransitionException extends DomainException
{
    public static function invalidTransition(string $from, string $to): self
    {
        return new self("Transisi status SPJ tidak valid dari '{$from}' ke '{$to}'. Perubahan status harus mengikuti alur tahapan resmi.");
    }
}
