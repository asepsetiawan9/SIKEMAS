<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatusVerifikasi;
use App\Models\Belanja;
use App\Models\RiwayatProses;
use App\Models\User;
use App\Repositories\BelanjaRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BelanjaService
{
    public function __construct(
        private readonly BelanjaRepository $repository,
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<Belanja>
     */
    public function getPaginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->getPaginated($filters, $perPage);
    }

    public function getDetail(int $id): ?Belanja
    {
        return $this->repository->getDetail($id);
    }

    /**
     * @return LengthAwarePaginator<Belanja>
     */
    public function getAntreanSekmat(int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->getAntreanSekmat($perPage);
    }

    /**
     * @return LengthAwarePaginator<Belanja>
     */
    public function getAntreanCamat(int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->getAntreanCamat($perPage);
    }

    /**
     * @return array<string, int>
     */
    public function getDashboardStats(): array
    {
        return $this->repository->getDashboardStats();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Belanja>
     */
    public function getRecent(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getRecent($limit);
    }

    /**
     * Buat belanja baru.
     *
     * @param array<string, mixed> $data
     */
    public function createBelanja(array $data, User|int $user): Belanja
    {
        $userModel = $user instanceof User ? $user : User::findOrFail($user);

        return DB::transaction(function () use ($data, $userModel) {
            // Hitung total nilai & harga satuan
            if (isset($data['nominal']) && ! isset($data['harga_satuan'])) {
                $data['harga_satuan'] = (float) $data['nominal'];
                $data['volume'] = $data['volume'] ?? 1;
                $data['satuan'] = $data['satuan'] ?? 'paket';
                $data['total_nilai'] = (float) $data['nominal'];
            } else {
                $harga = (float) ($data['harga_satuan'] ?? 0);
                $volume = (float) ($data['volume'] ?? 1);
                $data['total_nilai'] = (float) ($data['total_nilai'] ?? ($harga * $volume));
            }

            $data['created_by'] = $userModel->id;
            $data['status_verifikasi'] = StatusVerifikasi::DRAFT->value;
            $data['status_dokumen'] = 'belum_lengkap';

            $belanja = $this->repository->create($data);

            // Catat riwayat
            $this->catatRiwayat($belanja, 'input_data', "Memasukkan data belanja: {$belanja->uraian}", $userModel);

            return $belanja;
        });
    }

    /**
     * Update belanja (hanya jika editable).
     *
     * @param array<string, mixed> $data
     * @throws \DomainException|\RuntimeException
     */
    public function updateBelanja(Belanja|int $belanja, array $data, User|int $user): Belanja
    {
        $belanjaModel = $belanja instanceof Belanja ? $belanja : Belanja::findOrFail($belanja);
        $userModel = $user instanceof User ? $user : User::findOrFail($user);

        if (! $belanjaModel->isEditable()) {
            throw new \DomainException('Belanja tidak bisa diubah pada status saat ini.');
        }

        return DB::transaction(function () use ($belanjaModel, $data, $userModel) {
            // Recalculate total / nominal
            if (isset($data['nominal']) && ! isset($data['harga_satuan'])) {
                $data['total_nilai'] = (float) $data['nominal'];
                $data['harga_satuan'] = (float) $data['nominal'];
            } elseif (isset($data['harga_satuan']) || isset($data['volume'])) {
                $harga = (float) ($data['harga_satuan'] ?? $belanjaModel->harga_satuan);
                $volume = (float) ($data['volume'] ?? $belanjaModel->volume);
                $data['total_nilai'] = $harga * $volume;
            }

            // Jika dikembalikan, reset ke draft saat diedit
            if (in_array($belanjaModel->status_verifikasi, [
                StatusVerifikasi::DIKEMBALIKAN_SEKMAT,
                StatusVerifikasi::DIKEMBALIKAN_CAMAT,
            ])) {
                $data['status_verifikasi'] = StatusVerifikasi::DRAFT->value;
            }

            $belanjaModel = $this->repository->update($belanjaModel, $data);

            $this->catatRiwayat($belanjaModel, 'update_data', 'Memperbarui data belanja', $userModel);

            return $belanjaModel;
        });
    }

    /**
     * Hapus belanja (hanya jika draft).
     *
     * @throws \DomainException|\RuntimeException
     */
    public function deleteBelanja(Belanja|int $belanja, User|int|null $user = null): bool
    {
        $belanjaModel = $belanja instanceof Belanja ? $belanja : Belanja::findOrFail($belanja);

        if ($belanjaModel->status_verifikasi !== StatusVerifikasi::DRAFT) {
            throw new \DomainException('Hanya belanja berstatus Draft yang bisa dihapus.');
        }

        return DB::transaction(function () use ($belanjaModel) {
            // Hapus file-file fisik dokumen dari storage
            foreach ($belanjaModel->dokumenBukti as $dokumen) {
                if ($dokumen->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($dokumen->file_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($dokumen->file_path);
                }
            }

            return $this->repository->delete($belanjaModel);
        });
    }

    /**
     * Operator mengajukan verifikasi ke Sekmat (BR-VER-01).
     *
     * @throws \DomainException|\RuntimeException
     */
    public function ajukanVerifikasi(Belanja|int $belanja, User|int $user): Belanja
    {
        $belanjaModel = $belanja instanceof Belanja ? $belanja : Belanja::findOrFail($belanja);
        $userModel = $user instanceof User ? $user : User::findOrFail($user);

        // BR-VER-01: Minimal 1 dokumen bukti
        if ($belanjaModel->dokumenBukti()->count() === 0) {
            throw new \DomainException('Belanja harus memiliki minimal 1 dokumen bukti sebelum diajukan.');
        }

        if (! $belanjaModel->status_verifikasi->canTransitionTo(StatusVerifikasi::DIAJUKAN)) {
            throw new \DomainException('Belanja tidak bisa diajukan pada status saat ini.');
        }

        return DB::transaction(function () use ($belanjaModel, $userModel) {
            $updated = $this->repository->update($belanjaModel, [
                'status_verifikasi' => StatusVerifikasi::DIAJUKAN->value,
                'diajukan_pada' => now(),
            ]);

            $this->catatRiwayat($updated, 'ajukan_verifikasi', 'Mengajukan verifikasi ke Sekmat', $userModel);

            return $updated;
        });
    }

    /**
     * Sekmat memverifikasi (approve).
     *
     * @throws \DomainException|\RuntimeException
     */
    public function verifikasiSekmat(Belanja|int $belanja, User|int $sekmat, ?string $catatan = null): Belanja
    {
        $belanjaModel = $belanja instanceof Belanja ? $belanja : Belanja::findOrFail($belanja);
        $sekmatModel = $sekmat instanceof User ? $sekmat : User::findOrFail($sekmat);

        if (! $belanjaModel->status_verifikasi->canTransitionTo(StatusVerifikasi::DIVERIFIKASI_SEKMAT)) {
            throw new \DomainException('Belanja tidak bisa diverifikasi pada status saat ini.');
        }

        return DB::transaction(function () use ($belanjaModel, $sekmatModel, $catatan) {
            $updated = $this->repository->update($belanjaModel, [
                'status_verifikasi' => StatusVerifikasi::DIVERIFIKASI_SEKMAT->value,
                'diverifikasi_sekmat_pada' => now(),
                'diverifikasi_oleh' => $sekmatModel->id,
                'catatan_sekmat' => $catatan ? trim($catatan) : null,
            ]);

            $keterangan = $catatan ? "Diverifikasi oleh Sekmat dengan catatan: {$catatan}" : 'Diverifikasi/disetujui oleh Sekmat';
            $this->catatRiwayat($updated, 'verifikasi_sekmat', $keterangan, $sekmatModel);

            return $updated;
        });
    }

    /**
     * Sekmat mengembalikan dengan catatan (BR-VER-02).
     *
     * @throws \DomainException|\RuntimeException
     */
    public function kembalikanSekmat(Belanja|int $belanja, User|int|string $param2, User|int|string|null $param3 = null): Belanja
    {
        $belanjaModel = $belanja instanceof Belanja ? $belanja : Belanja::findOrFail($belanja);

        // Resolve argument order (can be ($belanja, $catatan, $user) or ($belanja, $user, $catatan))
        if (is_string($param2)) {
            $catatan = $param2;
            $userModel = $param3 instanceof User ? $param3 : User::findOrFail($param3);
        } else {
            $userModel = $param2 instanceof User ? $param2 : User::findOrFail($param2);
            $catatan = (string) $param3;
        }

        if (mb_strlen(trim($catatan)) < 5) {
            throw new \DomainException('Catatan pengembalian harus minimal 5 karakter.');
        }

        if (! $belanjaModel->status_verifikasi->canTransitionTo(StatusVerifikasi::DIKEMBALIKAN_SEKMAT)) {
            throw new \DomainException('Belanja tidak bisa dikembalikan pada status saat ini.');
        }

        return DB::transaction(function () use ($belanjaModel, $catatan, $userModel) {
            $updated = $this->repository->update($belanjaModel, [
                'status_verifikasi' => StatusVerifikasi::DIKEMBALIKAN_SEKMAT->value,
                'catatan_sekmat' => trim($catatan),
                'diverifikasi_oleh' => $userModel->id,
            ]);

            $this->catatRiwayat(
                $updated,
                'tolak_sekmat',
                "Dikembalikan oleh Sekmat: {$catatan}",
                $userModel
            );

            return $updated;
        });
    }

    /**
     * Camat menyetujui (BR-VER-04: FINAL).
     *
     * @throws \DomainException|\RuntimeException
     */
    public function setujuiCamat(Belanja|int $belanja, User|int $camat, ?string $catatan = null): Belanja
    {
        $belanjaModel = $belanja instanceof Belanja ? $belanja : Belanja::findOrFail($belanja);
        $camatModel = $camat instanceof User ? $camat : User::findOrFail($camat);

        if (! $belanjaModel->status_verifikasi->canTransitionTo(StatusVerifikasi::DISETUJUI_CAMAT)) {
            throw new \DomainException('Belanja tidak bisa disetujui pada status saat ini.');
        }

        return DB::transaction(function () use ($belanjaModel, $camatModel, $catatan) {
            $updated = $this->repository->update($belanjaModel, [
                'status_verifikasi' => StatusVerifikasi::DISETUJUI_CAMAT->value,
                'disetujui_camat_pada' => now(),
                'disetujui_oleh' => $camatModel->id,
                'catatan_camat' => $catatan ? trim($catatan) : null,
            ]);

            $keterangan = $catatan ? "Disetujui oleh Camat (FINAL) dengan catatan: {$catatan}" : 'Disetujui oleh Camat (FINAL)';
            $this->catatRiwayat($updated, 'setujui_camat', $keterangan, $camatModel);

            return $updated;
        });
    }

    /**
     * Camat mengembalikan dengan catatan (BR-VER-03).
     *
     * @throws \DomainException|\RuntimeException
     */
    public function kembalikanCamat(Belanja|int $belanja, User|int|string $param2, User|int|string|null $param3 = null): Belanja
    {
        $belanjaModel = $belanja instanceof Belanja ? $belanja : Belanja::findOrFail($belanja);

        // Resolve argument order (can be ($belanja, $catatan, $user) or ($belanja, $user, $catatan))
        if (is_string($param2)) {
            $catatan = $param2;
            $userModel = $param3 instanceof User ? $param3 : User::findOrFail($param3);
        } else {
            $userModel = $param2 instanceof User ? $param2 : User::findOrFail($param2);
            $catatan = (string) $param3;
        }

        if (mb_strlen(trim($catatan)) < 5) {
            throw new \DomainException('Catatan pengembalian harus minimal 5 karakter.');
        }

        if (! $belanjaModel->status_verifikasi->canTransitionTo(StatusVerifikasi::DIKEMBALIKAN_CAMAT)) {
            throw new \DomainException('Belanja tidak bisa dikembalikan pada status saat ini.');
        }

        return DB::transaction(function () use ($belanjaModel, $catatan, $userModel) {
            $updated = $this->repository->update($belanjaModel, [
                'status_verifikasi' => StatusVerifikasi::DIKEMBALIKAN_CAMAT->value,
                'catatan_camat' => trim($catatan),
                'disetujui_oleh' => $userModel->id,
            ]);

            $this->catatRiwayat(
                $updated,
                'tolak_camat',
                "Dikembalikan oleh Camat: {$catatan}",
                $userModel
            );

            return $updated;
        });
    }

    /**
     * Catat riwayat proses (BR-VER-07).
     */
    public function catatRiwayat(Belanja $belanja, string $aksi, string $keterangan, User|int $user): RiwayatProses
    {
        $userId = $user instanceof User ? $user->id : (int) $user;

        return RiwayatProses::create([
            'belanja_id' => $belanja->id,
            'aksi' => $aksi,
            'keterangan' => $keterangan,
            'user_id' => $userId,
        ]);
    }
}
