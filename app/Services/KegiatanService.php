<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatusKegiatan;
use App\Models\Kegiatan;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Repositories\KegiatanRepository;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KegiatanService
{
    public function __construct(
        protected KegiatanRepository $kegiatanRepository
    ) {}

    /**
     * @return LengthAwarePaginator<Kegiatan>
     */
    public function getList(
        int $perPage = 15,
        ?int $tahun = null,
        ?int $kasiId = null,
        ?string $search = null,
        ?string $status = null
    ): LengthAwarePaginator {
        return $this->kegiatanRepository->paginate($perPage, $tahun, $kasiId, $search, $status);
    }

    /**
     * @return Collection<int, Kegiatan>
     */
    public function getKegiatanForKasi(User $user, ?int $tahun = null): Collection
    {
        return $this->kegiatanRepository->getActiveByKasi($user->id, $tahun);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Kegiatan
    {
        return $this->createKegiatan($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createKegiatan(array $data): Kegiatan
    {
        return DB::transaction(function () use ($data) {
            if (! isset($data['status'])) {
                $data['status'] = StatusKegiatan::AKTIF->value;
            }

            $kegiatan = $this->kegiatanRepository->create($data);

            if (Auth::check()) {
                LogAktivitas::catat(
                    userId: (int) Auth::id(),
                    aksi: 'create_kegiatan',
                    tabelTerkait: 'kegiatan',
                    recordId: (int) $kegiatan->id,
                    keterangan: "Kegiatan anggaran baru ditambahkan: {$kegiatan->nama} ({$kegiatan->kode_rekening}) pagu Rp " . number_format((float) $kegiatan->pagu, 2, ',', '.')
                );
            }

            return $kegiatan;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int|Kegiatan $kegiatan, array $data): bool
    {
        return DB::transaction(function () use ($kegiatan, $data) {
            $model = $kegiatan instanceof Kegiatan ? $kegiatan : $this->kegiatanRepository->findById((int) $kegiatan);
            if (! $model) {
                return false;
            }

            $success = $this->kegiatanRepository->update($model, $data);

            if ($success && Auth::check()) {
                LogAktivitas::catat(
                    userId: (int) Auth::id(),
                    aksi: 'update_kegiatan',
                    tabelTerkait: 'kegiatan',
                    recordId: (int) $model->id,
                    keterangan: "Kegiatan anggaran diperbarui: {$model->nama} ({$model->kode_rekening})"
                );
            }

            return $success;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateKegiatan(Kegiatan $kegiatan, array $data): bool
    {
        return $this->update($kegiatan, $data);
    }

    public function delete(int|Kegiatan $kegiatan): bool
    {
        return DB::transaction(function () use ($kegiatan) {
            $model = $kegiatan instanceof Kegiatan ? $kegiatan : $this->kegiatanRepository->findById((int) $kegiatan);
            if (! $model) {
                return false;
            }

            // Validasi: Tidak boleh menghapus kegiatan jika sudah ada riwayat SPJ terkait
            if ($model->spj()->exists()) {
                throw new DomainException('Kegiatan tidak dapat dihapus karena sudah memiliki riwayat dokumen SPJ.');
            }

            $nama = $model->nama;
            $kode = $model->kode_rekening;
            $id = (int) $model->id;

            $success = $this->kegiatanRepository->delete($model);

            if ($success && Auth::check()) {
                LogAktivitas::catat(
                    userId: (int) Auth::id(),
                    aksi: 'delete_kegiatan',
                    tabelTerkait: 'kegiatan',
                    recordId: $id,
                    keterangan: "Kegiatan anggaran dihapus: {$nama} ({$kode})"
                );
            }

            return $success;
        });
    }
}
