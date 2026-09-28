<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\KegiatanRap;
use App\Models\Program;
use App\Models\SubKegiatan;
use App\Repositories\ProgramRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProgramService
{
    public function __construct(
        private readonly ProgramRepository $repository,
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<Program>
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getPaginated($filters, $perPage);
    }

    /**
     * @return Collection<int, Program>
     */
    public function getAll(?int $tahun = null): Collection
    {
        return $this->repository->getAll($tahun);
    }

    /**
     * Ambil hierarki tree lengkap untuk tampilan UI Program/Index.jsx dan cascading dropdown.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHierarchyTree(?int $tahun = null): array
    {
        $tahun = $tahun ?: (int) date('Y');
        $programs = Program::with([
            'kegiatanRap' => fn ($q) => $q->orderBy('kode'),
            'kegiatanRap.subKegiatan' => fn ($q) => $q->orderBy('kode'),
        ])
            ->where('tahun_anggaran', $tahun)
            ->orderBy('kode')
            ->get();

        return $programs->map(function (Program $p) {
            $kegiatanMapped = $p->kegiatanRap->map(function (KegiatanRap $k) {
                $subMapped = $k->subKegiatan->map(fn (SubKegiatan $s) => [
                    'id' => $s->id,
                    'kegiatan_rap_id' => $s->kegiatan_rap_id,
                    'kode' => $s->kode,
                    'nama' => $s->nama,
                    'keterangan' => $s->keterangan ?? $s->deskripsi,
                ])->values()->all();

                return [
                    'id' => $k->id,
                    'program_id' => $k->program_id,
                    'kode' => $k->kode,
                    'nama' => $k->nama,
                    'keterangan' => $k->keterangan ?? $k->deskripsi,
                    'sub_kegiatan' => $subMapped,
                ];
            })->values()->all();

            return [
                'id' => $p->id,
                'kode' => $p->kode,
                'nama' => $p->nama,
                'tahun_anggaran' => $p->tahun_anggaran,
                'keterangan' => $p->keterangan ?? $p->deskripsi,
                'kegiatan' => $kegiatanMapped,
                'kegiatan_rap' => $kegiatanMapped,
            ];
        })->values()->all();
    }

    /**
     * Ambil data dropdown options pohon program untuk form belanja.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDropdownOptions(?int $tahun = null): array
    {
        return $this->getHierarchyTree($tahun);
    }

    public function getWithHierarchy(int $id): ?Program
    {
        return $this->repository->getWithHierarchy($id);
    }

    /**
     * Buat program baru.
     *
     * @param array<string, mixed> $data
     */
    public function createProgram(array $data, ?int $userId = null): Program
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['created_by'] = $userId ?? 1;
            return $this->repository->create($data);
        });
    }

    /**
     * Update program.
     *
     * @param array<string, mixed> $data
     */
    public function updateProgram(Program|int $program, array $data): Program
    {
        $model = $program instanceof Program ? $program : Program::findOrFail($program);
        return DB::transaction(fn () => $this->repository->update($model, $data));
    }

    /**
     * Hapus program (cascade ke kegiatan & sub kegiatan).
     *
     * @throws \RuntimeException jika masih ada belanja
     */
    public function deleteProgram(Program|int $program): bool
    {
        $model = $program instanceof Program ? $program : Program::findOrFail($program);

        // Proteksi: jangan hapus jika ada belanja di bawahnya
        $belanjaCount = $model->subKegiatan()
            ->join('belanja', 'belanja.sub_kegiatan_id', '=', 'sub_kegiatan.id')
            ->count();

        if ($belanjaCount > 0) {
            throw new \RuntimeException(
                "Program tidak bisa dihapus karena masih memiliki {$belanjaCount} data belanja."
            );
        }

        return DB::transaction(fn () => $this->repository->delete($model));
    }

    // ── Kegiatan RAP ──

    /**
     * @param array<string, mixed> $data
     */
    public function createKegiatan(array $data): KegiatanRap
    {
        return DB::transaction(fn () => $this->repository->createKegiatan($data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateKegiatan(KegiatanRap|int $kegiatan, array $data): KegiatanRap
    {
        $model = $kegiatan instanceof KegiatanRap ? $kegiatan : KegiatanRap::findOrFail($kegiatan);
        return DB::transaction(fn () => $this->repository->updateKegiatan($model, $data));
    }

    /**
     * @throws \RuntimeException jika masih ada belanja
     */
    public function deleteKegiatan(KegiatanRap|int $kegiatan): bool
    {
        $model = $kegiatan instanceof KegiatanRap ? $kegiatan : KegiatanRap::findOrFail($kegiatan);

        $belanjaCount = SubKegiatan::where('kegiatan_rap_id', $model->id)
            ->join('belanja', 'belanja.sub_kegiatan_id', '=', 'sub_kegiatan.id')
            ->count();

        if ($belanjaCount > 0) {
            throw new \RuntimeException(
                "Kegiatan tidak bisa dihapus karena masih memiliki {$belanjaCount} data belanja."
            );
        }

        return DB::transaction(fn () => $this->repository->deleteKegiatan($model));
    }

    // ── Sub Kegiatan ──

    /**
     * @param array<string, mixed> $data
     */
    public function createSubKegiatan(array $data): SubKegiatan
    {
        return DB::transaction(fn () => $this->repository->createSubKegiatan($data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateSubKegiatan(SubKegiatan|int $subKegiatan, array $data): SubKegiatan
    {
        $model = $subKegiatan instanceof SubKegiatan ? $subKegiatan : SubKegiatan::findOrFail($subKegiatan);
        return DB::transaction(fn () => $this->repository->updateSubKegiatan($model, $data));
    }

    /**
     * @throws \RuntimeException jika masih ada belanja
     */
    public function deleteSubKegiatan(SubKegiatan|int $subKegiatan): bool
    {
        $model = $subKegiatan instanceof SubKegiatan ? $subKegiatan : SubKegiatan::findOrFail($subKegiatan);

        $belanjaCount = $model->belanja()->count();

        if ($belanjaCount > 0) {
            throw new \RuntimeException(
                "Sub kegiatan tidak bisa dihapus karena masih memiliki {$belanjaCount} data belanja."
            );
        }

        return DB::transaction(fn () => $this->repository->deleteSubKegiatan($model));
    }

    /**
     * @return array<int>
     */
    public function getAvailableTahun(): array
    {
        return $this->repository->getAvailableTahun();
    }
}
