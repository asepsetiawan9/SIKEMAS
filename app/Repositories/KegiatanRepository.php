<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\StatusKegiatan;
use App\Models\Kegiatan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class KegiatanRepository
{
    /**
     * @return LengthAwarePaginator<Kegiatan>
     */
    public function paginate(
        int $perPage = 15,
        ?int $tahun = null,
        ?int $kasiId = null,
        ?string $search = null,
        ?string $status = null
    ): LengthAwarePaginator {
        return Kegiatan::query()
            ->with(['kasi', 'spj'])
            ->when($tahun, fn ($q) => $q->where('tahun_anggaran', $tahun))
            ->when($kasiId, fn ($q) => $q->where('kasi_id', $kasiId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode_rekening', 'like', "%{$search}%")
                        ->orWhereHas('kasi', fn ($k) => $k->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Kegiatan>
     */
    public function getActiveByKasi(int $kasiId, ?int $tahun = null): Collection
    {
        return Kegiatan::query()
            ->with(['spj'])
            ->where('kasi_id', $kasiId)
            ->where('status', StatusKegiatan::AKTIF)
            ->when($tahun, fn ($q) => $q->where('tahun_anggaran', $tahun))
            ->get();
    }

    public function findById(int $id): ?Kegiatan
    {
        return Kegiatan::with(['kasi', 'spj'])->find($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Kegiatan
    {
        return Kegiatan::create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Kegiatan $kegiatan, array $data): bool
    {
        return $kegiatan->update($data);
    }

    public function delete(Kegiatan $kegiatan): bool
    {
        return (bool) $kegiatan->delete();
    }
}
