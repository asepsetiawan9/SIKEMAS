<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\StatusVerifikasi;
use App\Models\Belanja;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class BelanjaRepository
{
    /**
     * Ambil daftar belanja dengan filter komprehensif (BR-CARI-01, BR-CARI-02).
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<Belanja>
     */
    public function getPaginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Belanja::query()
            ->with([
                'subKegiatan.kegiatanRap.program',
                'creator',
                'dokumenBukti',
            ])
            ->withCount('dokumenBukti');

        $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('tanggal_belanja')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Alias method untuk getPaginated.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<Belanja>
     */
    public function getFilteredList(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->getPaginated($filters, $perPage);
    }

    /**
     * Ambil antrean verifikasi untuk Sekmat.
     *
     * @return LengthAwarePaginator<Belanja>
     */
    public function getAntreanSekmat(int $perPage = 20): LengthAwarePaginator
    {
        return Belanja::query()
            ->with([
                'subKegiatan.kegiatanRap.program',
                'creator',
                'dokumenBukti',
            ])
            ->withCount('dokumenBukti')
            ->where('status_verifikasi', StatusVerifikasi::DIAJUKAN->value)
            ->orderBy('diajukan_pada')
            ->paginate($perPage);
    }

    /**
     * Ambil antrean persetujuan untuk Camat.
     *
     * @return LengthAwarePaginator<Belanja>
     */
    public function getAntreanCamat(int $perPage = 20): LengthAwarePaginator
    {
        return Belanja::query()
            ->with([
                'subKegiatan.kegiatanRap.program',
                'creator',
                'verifikator',
                'dokumenBukti',
            ])
            ->withCount('dokumenBukti')
            ->where('status_verifikasi', StatusVerifikasi::DIVERIFIKASI_SEKMAT->value)
            ->orderBy('diverifikasi_sekmat_pada')
            ->paginate($perPage);
    }

    /**
     * Ambil detail belanja lengkap dengan seluruh relasi.
     */
    public function getDetail(int $id): ?Belanja
    {
        return Belanja::with([
            'subKegiatan.kegiatanRap.program',
            'creator',
            'verifikator',
            'approver',
            'dokumenBukti.uploader',
            'riwayatProses.user',
        ])->find($id);
    }

    /**
     * Alias method untuk getDetail.
     */
    public function findByIdWithDetails(int $id): ?Belanja
    {
        return $this->getDetail($id);
    }

    /**
     * Simpan belanja baru.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Belanja
    {
        return Belanja::create($data);
    }

    /**
     * Update belanja.
     *
     * @param array<string, mixed> $data
     */
    public function update(Belanja $belanja, array $data): Belanja
    {
        $belanja->update($data);
        return $belanja->fresh();
    }

    /**
     * Hapus belanja (hanya jika masih draft).
     */
    public function delete(Belanja $belanja): bool
    {
        return (bool) $belanja->delete();
    }

    /**
     * Statistik ringkasan untuk dashboard.
     *
     * @return array<string, int|float>
     */
    public function getDashboardStats(): array
    {
        $baseQuery = Belanja::query();

        return [
            'total_belanja' => (clone $baseQuery)->count(),
            'total_nominal' => (float) (clone $baseQuery)->sum('total_nilai'),
            'bukti_lengkap' => (clone $baseQuery)->where('status_dokumen', 'lengkap')->count(),
            'belum_lengkap' => (clone $baseQuery)->where('status_dokumen', 'belum_lengkap')->count(),
            'menunggu_sekmat' => (clone $baseQuery)->where('status_verifikasi', StatusVerifikasi::DIAJUKAN->value)->count(),
            'menunggu_camat' => (clone $baseQuery)->where('status_verifikasi', StatusVerifikasi::DIVERIFIKASI_SEKMAT->value)->count(),
            'disetujui' => (clone $baseQuery)->where('status_verifikasi', StatusVerifikasi::DISETUJUI_CAMAT->value)->count(),
            'nominal_disetujui' => (float) (clone $baseQuery)->where('status_verifikasi', StatusVerifikasi::DISETUJUI_CAMAT->value)->sum('total_nilai'),
            'dikembalikan' => (clone $baseQuery)->whereIn('status_verifikasi', [
                StatusVerifikasi::DIKEMBALIKAN_SEKMAT->value,
                StatusVerifikasi::DIKEMBALIKAN_CAMAT->value,
            ])->count(),
        ];
    }

    /**
     * Belanja terbaru untuk dashboard.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Belanja>
     */
    public function getRecent(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Belanja::with([
            'subKegiatan.kegiatanRap.program',
            'creator',
        ])
            ->withCount('dokumenBukti')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Apply filter ke query builder.
     *
     * @param Builder<Belanja> $query
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        // Pencarian teks bebas (BR-CARI-02)
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('uraian', 'like', "%{$search}%")
                  ->orWhere('spesifikasi', 'like', "%{$search}%")
                  ->orWhereHas('dokumenBukti', function (Builder $dq) use ($search) {
                      $dq->where('nomor_dokumen', 'like', "%{$search}%")
                         ->orWhere('nama_dokumen', 'like', "%{$search}%");
                  });
            });
        }

        // Filter tahun (dari program)
        if (! empty($filters['tahun'])) {
            $query->whereHas('subKegiatan.kegiatanRap.program', function (Builder $q) use ($filters) {
                $q->where('tahun_anggaran', $filters['tahun']);
            });
        }

        // Filter program
        if (! empty($filters['program_id'])) {
            $query->whereHas('subKegiatan.kegiatanRap', function (Builder $q) use ($filters) {
                $q->where('program_id', $filters['program_id']);
            });
        }

        // Filter kegiatan
        if (! empty($filters['kegiatan_rap_id'])) {
            $query->whereHas('subKegiatan', function (Builder $q) use ($filters) {
                $q->where('kegiatan_rap_id', $filters['kegiatan_rap_id']);
            });
        }

        // Filter sub kegiatan
        if (! empty($filters['sub_kegiatan_id'])) {
            $query->where('sub_kegiatan_id', $filters['sub_kegiatan_id']);
        }

        // Filter jenis belanja
        if (! empty($filters['jenis_belanja'])) {
            $query->where('jenis_belanja', $filters['jenis_belanja']);
        }

        // Filter status dokumen
        if (! empty($filters['status_dokumen'])) {
            $query->where('status_dokumen', $filters['status_dokumen']);
        }

        // Filter status verifikasi
        if (! empty($filters['status_verifikasi'])) {
            $query->where('status_verifikasi', $filters['status_verifikasi']);
        }

        // Filter tanggal (range)
        if (! empty($filters['tanggal_dari'])) {
            $query->where('tanggal_belanja', '>=', $filters['tanggal_dari']);
        }
        if (! empty($filters['tanggal_sampai'])) {
            $query->where('tanggal_belanja', '<=', $filters['tanggal_sampai']);
        }
    }
}
