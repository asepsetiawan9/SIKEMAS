<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Program;
use App\Models\KegiatanRap;
use App\Models\SubKegiatan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProgramRepository
{
    /**
     * Ambil semua program dengan filter.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<Program>
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Program::query()
            ->with(['creator', 'kegiatanRap.subKegiatan'])
            ->withCount('kegiatanRap');

        if (! empty($filters['tahun'])) {
            $query->where('tahun_anggaran', $filters['tahun']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('kode')->paginate($perPage)->withQueryString();
    }

    /**
     * Ambil semua program (tanpa paginasi) untuk dropdown.
     *
     * @return Collection<int, Program>
     */
    public function getAll(?int $tahun = null): Collection
    {
        $query = Program::query()->with(['kegiatanRap.subKegiatan']);

        if ($tahun) {
            $query->where('tahun_anggaran', $tahun);
        }

        return $query->orderBy('kode')->get();
    }

    /**
     * Ambil program dengan seluruh hierarki (eager loaded).
     */
    public function getWithHierarchy(int $id): ?Program
    {
        return Program::with([
            'creator',
            'kegiatanRap' => fn ($q) => $q->orderBy('kode'),
            'kegiatanRap.subKegiatan' => fn ($q) => $q->orderBy('kode'),
        ])->find($id);
    }

    /**
     * Simpan program baru.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Program
    {
        return Program::create($data);
    }

    /**
     * Update program.
     *
     * @param array<string, mixed> $data
     */
    public function update(Program $program, array $data): Program
    {
        $program->update($data);
        return $program->fresh();
    }

    /**
     * Hapus program.
     */
    public function delete(Program $program): bool
    {
        return (bool) $program->delete();
    }

    // ── Kegiatan RAP ──

    /**
     * @param array<string, mixed> $data
     */
    public function createKegiatan(array $data): KegiatanRap
    {
        return KegiatanRap::create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateKegiatan(KegiatanRap $kegiatan, array $data): KegiatanRap
    {
        $kegiatan->update($data);
        return $kegiatan->fresh();
    }

    public function deleteKegiatan(KegiatanRap $kegiatan): bool
    {
        return (bool) $kegiatan->delete();
    }

    // ── Sub Kegiatan ──

    /**
     * @param array<string, mixed> $data
     */
    public function createSubKegiatan(array $data): SubKegiatan
    {
        return SubKegiatan::create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateSubKegiatan(SubKegiatan $subKegiatan, array $data): SubKegiatan
    {
        $subKegiatan->update($data);
        return $subKegiatan->fresh();
    }

    public function deleteSubKegiatan(SubKegiatan $subKegiatan): bool
    {
        return (bool) $subKegiatan->delete();
    }

    /**
     * Daftar tahun anggaran yang tersedia untuk dropdown filter.
     *
     * @return array<int>
     */
    public function getAvailableTahun(): array
    {
        return Program::query()
            ->distinct()
            ->orderByDesc('tahun_anggaran')
            ->pluck('tahun_anggaran')
            ->toArray();
    }
}
