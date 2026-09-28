<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\DokumenBukti;
use Illuminate\Database\Eloquent\Collection;

class DokumenBuktiRepository
{
    /**
     * Ambil semua dokumen untuk satu belanja.
     *
     * @return Collection<int, DokumenBukti>
     */
    public function getByBelanja(int $belanjaId): Collection
    {
        return DokumenBukti::with('uploader')
            ->where('belanja_id', $belanjaId)
            ->orderBy('jenis_dokumen')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Simpan dokumen baru.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): DokumenBukti
    {
        return DokumenBukti::create($data);
    }

    /**
     * Hapus dokumen.
     */
    public function delete(DokumenBukti $dokumen): bool
    {
        return (bool) $dokumen->delete();
    }

    /**
     * Cari dokumen berdasarkan jenis untuk satu belanja.
     */
    public function findByJenis(int $belanjaId, string $jenisDokumen): ?DokumenBukti
    {
        return DokumenBukti::where('belanja_id', $belanjaId)
            ->where('jenis_dokumen', $jenisDokumen)
            ->first();
    }
}
