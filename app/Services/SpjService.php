<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotifikasiTipe;
use App\Enums\SpjStatus;
use App\Enums\StatusKegiatan;
use App\Enums\UserRole;
use App\Exceptions\PaguExceededException;
use App\Exceptions\PendingRejectedSpjException;
use App\Exceptions\SpjStatusTransitionException;
use App\Models\Kegiatan;
use App\Models\Pengaturan;
use App\Models\Spj;
use App\Models\User;
use App\Repositories\SpjRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SpjService
{
    public function __construct(
        protected SpjRepository $spjRepository,
        protected NotifikasiService $notifikasiService
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<Spj>
     */
    public function getList(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->spjRepository->paginate($perPage, $filters);
    }

    /**
     * Generate standard Nomor SPJ: SPJ/{SEKSI}/{BULAN_ROMAWI}/{TAHUN}/{URUT_3DIGIT}
     * Contoh: SPJ/PEMERINTAHAN/IX/2026/001 (BR-SPJ-04)
     */
    public function generateNomorSpj(string $seksiCode, int $bulan, int $tahun): string
    {
        $romanBulan = $this->toRoman($bulan);
        $cleanSeksi = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $seksiCode) ?: 'UMUM');

        // Urut 3 digit berdasarkan SPJ resmi yang sudah memiliki nomor pada tahun anggaran tersebut
        $count = Spj::where('periode_tahun', $tahun)
            ->whereNotNull('nomor_spj')
            ->count() + 1;

        $urut = str_pad((string) $count, 3, '0', STR_PAD_LEFT);

        return "SPJ/{$cleanSeksi}/{$romanBulan}/{$tahun}/{$urut}";
    }

    /**
     * Convert integer month to Roman numeral.
     */
    public function toRoman(int $month): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $map[$month] ?? 'I';
    }

    /**
     * Kasi membuat pengajuan SPJ baru (Fase 3).
     * Enforces BR-SPJ-01, BR-SPJ-02, BR-SPJ-10, BR-KEU-04.
     *
     * @param array<string, mixed> $data
     */
    public function createPengajuan(array $data, User $kasi, ?UploadedFile $fileBukti = null): Spj
    {
        /** @var Kegiatan $kegiatan */
        $kegiatan = Kegiatan::findOrFail($data['kegiatan_id']);

        // BR-SPJ-01: Kasi HANYA bisa mengajukan SPJ untuk kegiatan miliknya sendiri
        if ((int) $kegiatan->kasi_id !== (int) $kasi->id) {
            throw new InvalidArgumentException('Anda hanya dapat mengajukan SPJ untuk kegiatan yang ditugaskan kepada Anda.');
        }

        // BR-KEU-04: Kegiatan dengan status selesai atau dibatalkan tidak bisa menerima SPJ baru
        if ($kegiatan->status !== StatusKegiatan::AKTIF) {
            throw new InvalidArgumentException("Kegiatan '{$kegiatan->nama}' tidak berstatus aktif dan tidak dapat menerima pengajuan SPJ baru.");
        }

        // BR-SPJ-10: Kasi tidak bisa submit SPJ baru jika ada SPJ miliknya yang masih berstatus ditolak
        $hasRejected = Spj::where('diajukan_oleh', $kasi->id)
            ->where('status', SpjStatus::DITOLAK)
            ->exists();

        if ($hasRejected) {
            throw PendingRejectedSpjException::hasRejected();
        }

        $nominal = (float) ($data['nominal'] ?? 0);

        // BR-SPJ-02: Nominal pengajuan TIDAK BOLEH melebihi sisa pagu kegiatan
        if ($nominal > (float) $kegiatan->sisa_pagu) {
            throw PaguExceededException::forNominal($nominal, (float) $kegiatan->sisa_pagu);
        }

        // Handle file upload jika ada file bukti baru
        $filePath = $data['file_bukti'] ?? null;
        if ($fileBukti instanceof UploadedFile) {
            $timestamp = now()->format('YmdHis');
            $randomStr = Str::random(6);
            $extension = $fileBukti->getClientOriginalExtension() ?: 'pdf';
            $filename = "bukti_spj_{$kegiatan->id}_{$timestamp}_{$randomStr}.{$extension}";
            $filePath = $fileBukti->storeAs('bukti_spj', $filename, 'public');
        } elseif (! is_string($filePath)) {
            throw new InvalidArgumentException('Dokumen bukti pertanggungjawaban fisik wajib dilampirkan.');
        }

        $tahunAktif = (int) (Pengaturan::where('key', 'tahun_anggaran_aktif')->value('value') ?? now()->format('Y'));
        $periodeTahun = (int) ($data['periode_tahun'] ?? $tahunAktif);
        $periodeBulan = (int) ($data['periode_bulan'] ?? now()->format('n'));

        $spj = DB::transaction(function () use ($kasi, $kegiatan, $data, $nominal, $filePath, $periodeBulan, $periodeTahun) {
            return $this->spjRepository->create([
                'kegiatan_id' => $kegiatan->id,
                'nominal' => $nominal,
                'status' => SpjStatus::DIAJUKAN_KASI->value,
                'file_bukti' => $filePath,
                'nomor_spj' => null,
                'tanggal_pengajuan' => now()->toDateString(),
                'tanggal_konsolidasi' => null,
                'tanggal_verifikasi' => null,
                'periode_bulan' => $periodeBulan,
                'periode_tahun' => $periodeTahun,
                'jenis_belanja' => $data['jenis_belanja'] ?? null,
                'diajukan_oleh' => $kasi->id,
                'catatan_verifikasi' => null,
            ]);
        });

        // Trigger Notifikasi ke Seluruh Staf Keuangan (Bagian 10)
        $seksiLabel = $kasi->seksi ? (is_string($kasi->seksi) ? $kasi->seksi : $kasi->seksi->value) : 'Kasi';
        $formattedNominal = 'Rp ' . number_format($nominal, 2, ',', '.');
        $stafKeuanganUsers = User::where('role', UserRole::STAF_KEUANGAN->value)->get();

        foreach ($stafKeuanganUsers as $staf) {
            $this->notifikasiService->send(
                userId: $staf->id,
                judul: 'Pengajuan SPJ Baru',
                pesan: "Pengajuan SPJ baru dari Seksi {$seksiLabel} — {$formattedNominal}",
                tipe: NotifikasiTipe::ACTION,
                link: "/spj/{$spj->id}"
            );
        }

        return $spj;
    }

    /**
     * Staf Keuangan mengonsolidasi pengajuan menjadi SPJ resmi bernomor (Fase 3).
     * Enforces BR-SPJ-03, BR-SPJ-04, BR-SPJ-09.
     */
    public function konsolidasi(Spj $spj, User $stafKeuangan, ?string $nomorSpj = null): Spj
    {
        return DB::transaction(function () use ($spj, $stafKeuangan, $nomorSpj) {
            /** @var Spj $lockedSpj */
            $lockedSpj = Spj::lockForUpdate()->findOrFail($spj->id);

            // BR-SPJ-09: Status harus 'diajukan_kasi' atau 'ditolak'
            if ($lockedSpj->status !== SpjStatus::DIAJUKAN_KASI && $lockedSpj->status !== SpjStatus::DITOLAK) {
                throw SpjStatusTransitionException::invalidTransition(
                    $lockedSpj->status->value,
                    SpjStatus::DIKONSOLIDASI->value
                );
            }

            // Generate nomor SPJ resmi jika belum ada
            $nomor = $nomorSpj ?: $lockedSpj->nomor_spj;
            if (! $nomor) {
                $seksi = $lockedSpj->kegiatan->kasi->seksi?->value
                    ?? ($lockedSpj->pengaju->seksi?->value ?? 'UMUM');
                $nomor = $this->generateNomorSpj(
                    $seksi,
                    (int) $lockedSpj->periode_bulan,
                    (int) $lockedSpj->periode_tahun
                );
            }

            $lockedSpj->update([
                'status' => SpjStatus::DIKONSOLIDASI->value,
                'nomor_spj' => $nomor,
                'dikonsolidasi_oleh' => $stafKeuangan->id,
                'tanggal_konsolidasi' => now()->toDateString(),
                'catatan_verifikasi' => null, // Reset catatan jika sebelumnya ditolak
            ]);

            return $lockedSpj;
        });
    }

    /**
     * Staf Keuangan mengajukan SPJ ke Sekmat untuk diverifikasi (Fase 3).
     * Enforces BR-SPJ-09.
     */
    public function ajukanVerifikasi(Spj $spj, User $stafKeuangan): Spj
    {
        $updatedSpj = DB::transaction(function () use ($spj) {
            /** @var Spj $lockedSpj */
            $lockedSpj = Spj::lockForUpdate()->findOrFail($spj->id);

            // BR-SPJ-09: Hanya boleh maju dari 'dikonsolidasi'
            if ($lockedSpj->status !== SpjStatus::DIKONSOLIDASI) {
                throw SpjStatusTransitionException::invalidTransition(
                    $lockedSpj->status->value,
                    SpjStatus::DIAJUKAN_VERIFIKASI->value
                );
            }

            $lockedSpj->update([
                'status' => SpjStatus::DIAJUKAN_VERIFIKASI->value,
            ]);

            return $lockedSpj;
        });

        // Trigger Notifikasi ke Sekmat (Bagian 10)
        $sekmatUsers = User::where('role', UserRole::SEKMAT->value)->get();
        foreach ($sekmatUsers as $sekmat) {
            $this->notifikasiService->send(
                userId: $sekmat->id,
                judul: 'SPJ Menunggu Verifikasi',
                pesan: "SPJ {$updatedSpj->nomor_spj} siap untuk diverifikasi",
                tipe: NotifikasiTipe::ACTION,
                link: "/spj/{$updatedSpj->id}"
            );
        }

        return $updatedSpj;
    }

    /**
     * Sekmat menyetujui atau menolak SPJ (Fase 3).
     * Enforces BR-SPJ-05, BR-SPJ-06, BR-SPJ-09, BR-KEU-03.
     */
    public function verifikasi(Spj $spj, User $sekmat, bool $approve, ?string $catatan = null): Spj
    {
        $updatedSpj = DB::transaction(function () use ($spj, $sekmat, $approve, $catatan) {
            /** @var Spj $lockedSpj */
            $lockedSpj = Spj::lockForUpdate()->findOrFail($spj->id);

            // BR-SPJ-09: Status harus 'diajukan_verifikasi'
            if ($lockedSpj->status !== SpjStatus::DIAJUKAN_VERIFIKASI) {
                $targetStatus = $approve ? SpjStatus::DIVERIFIKASI->value : SpjStatus::DITOLAK->value;
                throw SpjStatusTransitionException::invalidTransition($lockedSpj->status->value, $targetStatus);
            }

            if (! $approve) {
                // BR-SPJ-05: Catatan verifikasi WAJIB diisi saat Sekmat menolak (minimal 10 karakter)
                if (empty($catatan) || mb_strlen(trim($catatan)) < 10) {
                    throw new InvalidArgumentException('Catatan verifikasi wajib diisi minimal 10 karakter saat menolak SPJ.');
                }

                $lockedSpj->update([
                    'status' => SpjStatus::DITOLAK->value,
                    'diverifikasi_oleh' => $sekmat->id,
                    'tanggal_verifikasi' => now()->toDateString(),
                    'catatan_verifikasi' => trim($catatan),
                ]);

                return $lockedSpj;
            }

            // Approve: Status menjadi 'diverifikasi' (immutable BR-SPJ-06)
            $lockedSpj->update([
                'status' => SpjStatus::DIVERIFIKASI->value,
                'diverifikasi_oleh' => $sekmat->id,
                'tanggal_verifikasi' => now()->toDateString(),
                'catatan_verifikasi' => $catatan ? trim($catatan) : null,
            ]);

            return $lockedSpj;
        });

        // Trigger Notifikasi sesuai hasil verifikasi (Bagian 10)
        $nomorSpj = $updatedSpj->nomor_spj ?? "ID #{$updatedSpj->id}";
        $kasiId = (int) $updatedSpj->diajukan_oleh;
        $stafKeuanganUsers = User::where('role', UserRole::STAF_KEUANGAN->value)->get();

        if ($approve) {
            // Notifikasi ke Kasi pengaju
            $this->notifikasiService->send(
                userId: $kasiId,
                judul: 'SPJ Diverifikasi',
                pesan: "SPJ {$nomorSpj} telah disetujui dan diverifikasi oleh Sekmat",
                tipe: NotifikasiTipe::INFO,
                link: "/spj/{$updatedSpj->id}"
            );

            // Notifikasi ke Staf Keuangan
            foreach ($stafKeuanganUsers as $staf) {
                $this->notifikasiService->send(
                    userId: $staf->id,
                    judul: 'SPJ Diverifikasi',
                    pesan: "SPJ {$nomorSpj} telah diverifikasi oleh Sekmat",
                    tipe: NotifikasiTipe::INFO,
                    link: "/spj/{$updatedSpj->id}"
                );
            }

            // BR-KEU-03: Cek jika realisasi sudah mencapai > 80% pagu
            $kegiatan = $updatedSpj->kegiatan()->first();
            if ($kegiatan && (float) $kegiatan->pagu > 0) {
                $totalRealisasi = (float) $kegiatan->total_realisasi;
                $pagu = (float) $kegiatan->pagu;
                $persentase = ($totalRealisasi / $pagu) * 100;

                if ($persentase >= 80) {
                    $persenFormatted = number_format($persentase, 1, ',', '.');
                    // Kirim warning ke Staf Keuangan dan Sekmat
                    $sekmatUsers = User::where('role', UserRole::SEKMAT->value)->get();
                    $alertRecipients = $stafKeuanganUsers->merge($sekmatUsers);

                    foreach ($alertRecipients as $user) {
                        $this->notifikasiService->send(
                            userId: $user->id,
                            judul: 'Peringatan Pagu',
                            pesan: "Kegiatan '{$kegiatan->nama}' sudah mencapai {$persenFormatted}% dari total pagu anggaran",
                            tipe: NotifikasiTipe::WARNING,
                            link: '/kegiatan'
                        );
                    }
                }
            }
        } else {
            // Reject: Notifikasi ke Kasi & Staf Keuangan
            $catatanSingkat = Str::limit($catatan ?? 'Tidak memenuhi kelengkapan', 50);

            $this->notifikasiService->send(
                userId: $kasiId,
                judul: 'SPJ Ditolak',
                pesan: "SPJ {$nomorSpj} ditolak oleh Sekmat: {$catatanSingkat}",
                tipe: NotifikasiTipe::WARNING,
                link: "/spj/{$updatedSpj->id}"
            );

            foreach ($stafKeuanganUsers as $staf) {
                $this->notifikasiService->send(
                    userId: $staf->id,
                    judul: 'SPJ Ditolak',
                    pesan: "SPJ {$nomorSpj} ditolak: {$catatanSingkat}",
                    tipe: NotifikasiTipe::WARNING,
                    link: "/spj/{$updatedSpj->id}"
                );
            }
        }

        return $updatedSpj;
    }

    /**
     * Mengunggah ulang berkas bukti fisik revisi pada SPJ yang berstatus DITOLAK atau DIAJUKAN_KASI.
     * Enforces FLOW-01.
     */
    public function revisiBukti(Spj $spj, User $user, UploadedFile $fileBukti, ?string $catatan = null): Spj
    {
        return DB::transaction(function () use ($spj, $user, $fileBukti, $catatan) {
            /** @var Spj $lockedSpj */
            $lockedSpj = Spj::lockForUpdate()->findOrFail($spj->id);

            // Hanya boleh direvisi jika status ditolak atau diajukan_kasi
            if ($lockedSpj->status !== SpjStatus::DITOLAK && $lockedSpj->status !== SpjStatus::DIAJUKAN_KASI) {
                throw new InvalidArgumentException("Berkas bukti hanya dapat direvisi pada pengajuan yang berstatus ditolak atau baru diajukan.");
            }

            // Otorisasi: Harus pengaju (Kasi) atau Staf Keuangan
            $isPengaju = (int) $lockedSpj->diajukan_oleh === (int) $user->id;
            $isKeuangan = $user->role === UserRole::STAF_KEUANGAN || (is_string($user->role) && $user->role === UserRole::STAF_KEUANGAN->value);

            if (! $isPengaju && ! $isKeuangan) {
                throw new InvalidArgumentException("Anda tidak memiliki hak untuk merevisi berkas bukti SPJ ini.");
            }

            // Hapus berkas bukti lama jika ada
            if ($lockedSpj->file_bukti && \Illuminate\Support\Facades\Storage::disk('public')->exists($lockedSpj->file_bukti)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($lockedSpj->file_bukti);
            }

            // Simpan berkas bukti baru
            $ext = $fileBukti->getClientOriginalExtension();
            $safeName = 'spj_revisi_' . time() . '_' . uniqid() . '.' . $ext;
            $path = $fileBukti->storeAs('spj_dokumen', $safeName, 'public');

            $lockedSpj->update([
                'file_bukti' => $path,
            ]);

            // Catat log aktivitas
            \App\Models\LogAktivitas::create([
                'user_id' => $user->id,
                'tabel_terkait' => 'spj',
                'record_id' => $lockedSpj->id,
                'aksi' => 'revisi_bukti',
                'keterangan' => "Pengunggahan ulang berkas bukti fisik revisi oleh {$user->name}" . ($catatan ? ": {$catatan}" : ''),
                'ip_address' => request()->ip(),
            ]);

            // Kirim notifikasi ke Staf Keuangan jika Kasi yang mengunggah
            if ($isPengaju) {
                $stafKeuanganUsers = User::where('role', UserRole::STAF_KEUANGAN->value)->get();
                $nomorOrId = $lockedSpj->nomor_spj ?: "ID #{$lockedSpj->id}";
                foreach ($stafKeuanganUsers as $staf) {
                    $this->notifikasiService->send(
                        userId: $staf->id,
                        judul: 'Berkas Bukti SPJ Telah Direvisi',
                        pesan: "Kasi {$user->name} telah mengunggah ulang dokumen bukti fisik untuk {$nomorOrId}",
                        tipe: NotifikasiTipe::ACTION,
                        link: "/spj/{$lockedSpj->id}"
                    );
                }
            }

            return $lockedSpj;
        });
    }
}
