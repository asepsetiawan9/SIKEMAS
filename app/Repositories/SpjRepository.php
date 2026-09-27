<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\SpjStatus;
use App\Models\Spj;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SpjRepository
{
    /**
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<Spj>
     */
    public function paginate(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator {
        $status = $filters['status'] ?? null;
        $kasiId = $filters['kasi_id'] ?? null;
        $kegiatanId = $filters['kegiatan_id'] ?? null;
        $bulan = $filters['bulan'] ?? null;
        $tahun = $filters['tahun'] ?? null;
        $search = $filters['search'] ?? null;

        return Spj::query()
            ->with(['kegiatan.kasi', 'diajukanOleh', 'dikonsolidasiOleh', 'diverifikasiOleh'])
            ->when($status, function (Builder $q, $val) {
                if ($val instanceof SpjStatus) {
                    $q->where('status', $val->value);
                } elseif (is_string($val) && $val !== 'all') {
                    $q->where('status', $val);
                }
            })
            ->when($kasiId, fn (Builder $q) => $q->where('diajukan_oleh', $kasiId))
            ->when($kegiatanId, fn (Builder $q) => $q->where('kegiatan_id', $kegiatanId))
            ->when($bulan, fn (Builder $q) => $q->where('periode_bulan', (int) $bulan))
            ->when($tahun, fn (Builder $q) => $q->where('periode_tahun', (int) $tahun))
            ->when($search, function (Builder $q, string $term) {
                $q->where(function (Builder $sub) use ($term) {
                    $sub->where('nomor_spj', 'like', "%{$term}%")
                        ->orWhere('jenis_belanja', 'like', "%{$term}%")
                        ->orWhereHas('kegiatan', fn (Builder $k) => $k->where('nama', 'like', "%{$term}%"))
                        ->orWhereHas('diajukanOleh', fn (Builder $u) => $u->where('name', 'like', "%{$term}%"));
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(int $id): ?Spj
    {
        return Spj::with(['kegiatan.kasi', 'diajukanOleh', 'dikonsolidasiOleh', 'diverifikasiOleh'])->find($id);
    }

    /**
     * @return Collection<int, Spj>
     */
    public function findPendingKonsolidasi(): Collection
    {
        return Spj::with(['kegiatan.kasi', 'diajukanOleh'])
            ->where('status', SpjStatus::DIAJUKAN_KASI->value)
            ->oldest('tanggal_pengajuan')
            ->get();
    }

    /**
     * @return Collection<int, Spj>
     */
    public function findDitolak(): Collection
    {
        return Spj::with(['kegiatan.kasi', 'diajukanOleh', 'diverifikasiOleh'])
            ->where('status', SpjStatus::DITOLAK->value)
            ->latest('tanggal_verifikasi')
            ->get();
    }

    /**
     * @return Collection<int, Spj>
     */
    public function findPendingVerifikasi(): Collection
    {
        return Spj::with(['kegiatan.kasi', 'diajukanOleh', 'dikonsolidasiOleh'])
            ->where('status', SpjStatus::DIAJUKAN_VERIFIKASI->value)
            ->oldest('tanggal_konsolidasi')
            ->get();
    }

    /**
     * @return Collection<int, Spj>
     */
    public function findDitolakByKasi(int $kasiId): Collection
    {
        return Spj::with(['kegiatan', 'diverifikasiOleh'])
            ->where('diajukan_oleh', $kasiId)
            ->where('status', SpjStatus::DITOLAK->value)
            ->latest('updated_at')
            ->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Spj
    {
        return Spj::create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Spj $spj, array $data): bool
    {
        return $spj->update($data);
    }

    public function countByStatus(SpjStatus $status): int
    {
        return Spj::where('status', $status->value)->count();
    }
}
