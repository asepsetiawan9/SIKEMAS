<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\KondisiAset;
use App\Models\Aset;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AsetRepository
{
    /**
     * @return LengthAwarePaginator<Aset>
     */
    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?KondisiAset $kondisi = null,
        ?string $lokasi = null,
        ?int $tahun = null,
        bool $overdueOnly = false,
        ?string $jenisDokumen = null
    ): LengthAwarePaginator {
        return Aset::query()
            ->with(['penanggungJawab', 'kibKir'])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%")
                        ->orWhere('lokasi', 'like', "%{$search}%")
                        ->orWhere('merk_type', 'like', "%{$search}%")
                        ->orWhere('nomor_register', 'like', "%{$search}%");
                });
            })
            ->when($kondisi, fn ($q) => $q->where('kondisi', $kondisi->value))
            ->when($lokasi, fn ($q) => $q->where('lokasi', 'like', "%{$lokasi}%"))
            ->when($tahun, fn ($q) => $q->where('tahun_perolehan', $tahun))
            ->when($overdueOnly, function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('tanggal_verifikasi_fisik')
                        ->orWhere('tanggal_verifikasi_fisik', '<', now()->subDays(90)->toDateString());
                });
            })
            ->when($jenisDokumen === 'kir', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereHas('kibKir', fn ($k) => $k->where('jenis', 'kir'))
                        ->orWhere('kode_barang', 'like', '06.%');
                });
            })
            ->when($jenisDokumen === 'kib', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereHas('kibKir', fn ($k) => $k->where('jenis', '!=', 'kir'))
                        ->orWhere('kode_barang', 'not like', '06.%');
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(int $id): ?Aset
    {
        return Aset::with(['penanggungJawab', 'kibKirs.dibuatOleh', 'kibKir'])->find($id);
    }

    public function findByKode(string $kodeBarang): ?Aset
    {
        return Aset::with(['penanggungJawab', 'kibKir'])->where('kode_barang', $kodeBarang)->first();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Aset
    {
        return Aset::create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Aset $aset, array $data): bool
    {
        return $aset->update($data);
    }

    /**
     * @return Collection<int, Aset>
     */
    public function getRusakBerat(): Collection
    {
        return Aset::where('kondisi', KondisiAset::RUSAK_BERAT->value)->get();
    }

    /**
     * Statistics summary for cards and overview.
     *
     * @return array{
     *     total_aset: int,
     *     total_nilai: float,
     *     total_baik: int,
     *     total_rusak_ringan: int,
     *     total_rusak_berat: int,
     *     total_overdue: int
     * }
     */
    public function getStatistics(): array
    {
        $overdueThreshold = now()->subDays(90)->toDateString();

        return [
            'total_aset' => Aset::count(),
            'total_nilai' => (float) Aset::sum('nilai'),
            'total_baik' => Aset::where('kondisi', KondisiAset::BAIK->value)->count(),
            'total_rusak_ringan' => Aset::where('kondisi', KondisiAset::RUSAK_RINGAN->value)->count(),
            'total_rusak_berat' => Aset::where('kondisi', KondisiAset::RUSAK_BERAT->value)->count(),
            'total_overdue' => Aset::where(function ($q) use ($overdueThreshold) {
                $q->whereNull('tanggal_verifikasi_fisik')
                    ->orWhere('tanggal_verifikasi_fisik', '<', $overdueThreshold);
            })->count(),
        ];
    }

    /**
     * @return list<string>
     */
    public function getDistinctLokasi(): array
    {
        return Aset::select('lokasi')
            ->distinct()
            ->whereNotNull('lokasi')
            ->orderBy('lokasi')
            ->pluck('lokasi')
            ->toArray();
    }

    /**
     * @return list<int>
     */
    public function getDistinctTahun(): array
    {
        return Aset::select('tahun_perolehan')
            ->distinct()
            ->whereNotNull('tahun_perolehan')
            ->orderByDesc('tahun_perolehan')
            ->pluck('tahun_perolehan')
            ->toArray();
    }
}
