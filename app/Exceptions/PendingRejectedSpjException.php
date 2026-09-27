<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

class PendingRejectedSpjException extends DomainException
{
    public static function hasRejected(): self
    {
        return new self("Anda masih memiliki pengajuan SPJ berstatus 'Ditolak' yang belum direvisi. Harap selesaikan revisi SPJ yang ditolak sebelum mengajukan SPJ baru.");
    }
}
