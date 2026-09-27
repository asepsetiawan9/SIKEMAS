<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\LogAktivitas;
use App\Models\Spj;
use Illuminate\Support\Facades\Auth;

class SpjObserver
{
    /**
     * Handle the Spj "created" event.
     */
    public function created(Spj $spj): void
    {
        $userId = Auth::id() ?? $spj->diajukan_oleh;
        $kegiatanNama = $spj->kegiatan?->nama ?? "Kegiatan #{$spj->kegiatan_id}";
        $nominal = number_format((float) $spj->nominal, 2, ',', '.');

        LogAktivitas::catat(
            userId: $userId,
            aksi: 'create_spj',
            tabelTerkait: 'spj',
            recordId: (int) $spj->id,
            keterangan: "Pengajuan SPJ baru dibuat untuk {$kegiatanNama} senilai Rp {$nominal} (Status: {$spj->status->value})"
        );
    }

    /**
     * Handle the Spj "updating" event.
     */
    public function updating(Spj $spj): void
    {
        if ($spj->isDirty('status')) {
            $oldStatus = $spj->getOriginal('status');
            $oldVal = $oldStatus instanceof \BackedEnum ? $oldStatus->value : (string) $oldStatus;
            $newVal = $spj->status instanceof \BackedEnum ? $spj->status->value : (string) $spj->status;

            $userId = Auth::id() ?? $spj->diverifikasi_oleh ?? $spj->dikonsolidasi_oleh ?? $spj->diajukan_oleh;
            $nomor = $spj->nomor_spj ? " (No: {$spj->nomor_spj})" : '';
            $catatan = ($newVal === 'ditolak' && $spj->catatan_verifikasi)
                ? " - Alasan penolakan: {$spj->catatan_verifikasi}"
                : '';

            LogAktivitas::catat(
                userId: $userId,
                aksi: 'update_status_spj',
                tabelTerkait: 'spj',
                recordId: (int) $spj->id,
                keterangan: "Status SPJ{$nomor} berubah dari '{$oldVal}' menjadi '{$newVal}'{$catatan}"
            );
        }
    }
}
