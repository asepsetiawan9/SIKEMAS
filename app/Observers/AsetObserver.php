<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\KondisiAset;
use App\Enums\NotifikasiTipe;
use App\Enums\UserRole;
use App\Models\Aset;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Services\NotifikasiService;
use Illuminate\Support\Facades\Auth;

class AsetObserver
{
    /**
     * Handle the Aset "creating" event.
     */
    public function creating(Aset $aset): void
    {
        if ($aset->tanggal_verifikasi_fisik === null) {
            $aset->tanggal_verifikasi_fisik = now()->toDateString();
        }
    }

    /**
     * Handle the Aset "created" event.
     */
    public function created(Aset $aset): void
    {
        $userId = Auth::id();

        LogAktivitas::catat(
            userId: $userId,
            aksi: 'create_aset',
            tabelTerkait: 'aset',
            recordId: (int) $aset->id,
            keterangan: "Aset BMD baru didaftarkan: {$aset->nama} ({$aset->kode_barang}) di lokasi {$aset->lokasi}"
        );

        // Notifikasi ke Staf Umum sesuai Bagian 10
        $stafUmumUsers = User::where('role', UserRole::STAF_UMUM->value)->where('is_active', true)->get();
        $notifikasiService = app(NotifikasiService::class);

        foreach ($stafUmumUsers as $staf) {
            $notifikasiService->send(
                userId: (int) $staf->id,
                judul: 'Aset Baru',
                pesan: "Aset baru ditambahkan: {$aset->nama} ({$aset->kode_barang})",
                tipe: NotifikasiTipe::INFO,
                link: "/aset/{$aset->id}"
            );
        }
    }

    /**
     * Handle the Aset "updating" event.
     */
    public function updating(Aset $aset): void
    {
        // BR-ASET-05: Setiap update kondisi/lokasi WAJIB mengupdate tanggal_verifikasi_fisik
        if ($aset->isDirty('kondisi') || $aset->isDirty('lokasi')) {
            $aset->tanggal_verifikasi_fisik = now()->toDateString();
        }
    }

    /**
     * Handle the Aset "updated" event.
     */
    public function updated(Aset $aset): void
    {
        $userId = Auth::id();
        $changes = [];

        if ($aset->wasChanged('kondisi')) {
            $oldKondisi = $aset->getOriginal('kondisi');
            $oldVal = $oldKondisi instanceof \BackedEnum ? $oldKondisi->value : (string) $oldKondisi;
            $newVal = $aset->kondisi instanceof \BackedEnum ? $aset->kondisi->value : (string) $aset->kondisi;
            $changes[] = "kondisi diubah dari '{$oldVal}' ke '{$newVal}'";

            // BR-ASET-02: Kondisi rusak_berat otomatis kandidat penghapusan, kirim notifikasi warning
            if ($newVal === KondisiAset::RUSAK_BERAT->value) {
                $targetUsers = User::whereIn('role', [UserRole::STAF_KEUANGAN->value, UserRole::SEKMAT->value])
                    ->where('is_active', true)
                    ->get();
                $notifikasiService = app(NotifikasiService::class);

                foreach ($targetUsers as $target) {
                    $notifikasiService->send(
                        userId: (int) $target->id,
                        judul: 'Peringatan Aset Rusak Berat',
                        pesan: "Aset '{$aset->nama}' ({$aset->kode_barang}) berstatus Rusak Berat dan masuk kandidat penghapusan.",
                        tipe: NotifikasiTipe::WARNING,
                        link: "/aset/{$aset->id}"
                    );
                }
            }
        }

        if ($aset->wasChanged('lokasi')) {
            $oldLokasi = (string) $aset->getOriginal('lokasi');
            $changes[] = "lokasi dipindahkan dari '{$oldLokasi}' ke '{$aset->lokasi}'";
        }

        if ($aset->wasChanged('kode_barang')) {
            $oldKode = (string) $aset->getOriginal('kode_barang');
            $changes[] = "kode barang direvisi dari '{$oldKode}' ke '{$aset->kode_barang}'";
        }

        if ($aset->wasChanged('penanggung_jawab')) {
            $changes[] = 'pergantian penanggung jawab aset';
        }

        if (! empty($changes)) {
            LogAktivitas::catat(
                userId: $userId,
                aksi: 'update_aset',
                tabelTerkait: 'aset',
                recordId: (int) $aset->id,
                keterangan: "Pembaruan aset {$aset->kode_barang}: " . implode(', ', $changes)
            );
        }
    }
}
