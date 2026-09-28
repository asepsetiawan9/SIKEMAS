<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Belanja;
use App\Models\DokumenBukti;
use App\Models\User;
use App\Repositories\DokumenBuktiRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DokumenBuktiService
{
    public function __construct(
        private readonly DokumenBuktiRepository $repository,
        private readonly BelanjaService $belanjaService,
    ) {}

    /**
     * Upload dokumen bukti belanja (BR-DOK-01, BR-DOK-04, BR-DOK-07).
     *
     * @throws \DomainException
     */
    public function upload(
        Belanja $belanja,
        UploadedFile $file,
        string $jenisDokumen,
        string $namaDokumen,
        User $user,
        ?string $nomorDokumen = null,
    ): DokumenBukti {
        // BR-VER-05: Dokumen terkunci setelah disetujui Camat
        if ($belanja->isDokumenLocked()) {
            throw new \DomainException('Dokumen tidak bisa diubah karena belanja sudah disetujui Camat.');
        }

        // BR-DOK-04: Validasi tipe file
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (! in_array($file->getMimeType(), $allowedTypes, true)) {
            throw new \DomainException('Tipe file tidak didukung. Gunakan PDF, JPG, atau PNG.');
        }

        // BR-DOK-04: Maksimal 10MB
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new \DomainException('Ukuran file maksimal 10MB.');
        }

        return DB::transaction(function () use ($belanja, $file, $jenisDokumen, $namaDokumen, $user, $nomorDokumen) {
            // Simpan file ke storage
            $path = $file->store("bukti_belanja/{$belanja->id}", 'public');

            $dokumen = $this->repository->create([
                'belanja_id' => $belanja->id,
                'jenis_dokumen' => $jenisDokumen,
                'nama_dokumen' => $namaDokumen,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType(),
                'nomor_dokumen' => $nomorDokumen,
                'uploaded_by' => $user->id,
            ]);

            // Update status dokumen belanja (BR-DOK-05)
            $belanja->syncStatusDokumen();

            // Catat riwayat (BR-DOK-07)
            $this->belanjaService->catatRiwayat(
                $belanja,
                'upload_dokumen',
                "Mengunggah {$namaDokumen}: {$file->getClientOriginalName()}",
                $user
            );

            return $dokumen;
        });
    }

    /**
     * Helper method upload dokumen bukti untuk controller.
     */
    public function uploadDokumen(
        int|Belanja $belanjaId,
        UploadedFile $file,
        \App\Enums\JenisDokumen|string $jenisDokumen,
        ?string $keterangan = null,
        int|User|null $userId = null,
        ?string $nomorDokumen = null,
        ?string $namaDokumen = null
    ): DokumenBukti {
        $belanjaModel = $belanjaId instanceof Belanja ? $belanjaId : Belanja::findOrFail($belanjaId);
        $userModel = $userId instanceof User ? $userId : User::findOrFail($userId ?? auth()->id());
        $jenisStr = $jenisDokumen instanceof \App\Enums\JenisDokumen ? $jenisDokumen->value : (string) $jenisDokumen;

        $docLabel = $namaDokumen ?: ($keterangan ?: (\App\Enums\JenisDokumen::tryFrom($jenisStr)?->label() ?? 'Dokumen Bukti'));

        return $this->upload(
            belanja: $belanjaModel,
            file: $file,
            jenisDokumen: $jenisStr,
            namaDokumen: $docLabel,
            user: $userModel,
            nomorDokumen: $nomorDokumen
        );
    }

    /**
     * Hapus dokumen bukti (BR-DOK-07, BR-VER-05).
     *
     * @throws \DomainException
     */
    public function delete(DokumenBukti $dokumen, User $user): bool
    {
        $belanja = $dokumen->belanja;

        // BR-VER-05: Terkunci setelah disetujui Camat
        if ($belanja->isDokumenLocked()) {
            throw new \DomainException('Dokumen tidak bisa dihapus karena belanja sudah disetujui Camat.');
        }

        return DB::transaction(function () use ($dokumen, $belanja, $user) {
            // Hapus file dari storage
            if ($dokumen->file_path && Storage::disk('public')->exists($dokumen->file_path)) {
                Storage::disk('public')->delete($dokumen->file_path);
            }

            $namaDokumen = $dokumen->nama_dokumen;
            $fileName = $dokumen->file_name;
            $result = $this->repository->delete($dokumen);

            // Update status dokumen (BR-DOK-05)
            $belanja->syncStatusDokumen();

            // Catat riwayat (BR-DOK-07)
            $this->belanjaService->catatRiwayat(
                $belanja,
                'hapus_dokumen',
                "Menghapus {$namaDokumen}: {$fileName}",
                $user
            );

            return $result;
        });
    }

    /**
     * Helper method hapus dokumen untuk controller.
     */
    public function deleteDokumen(int|DokumenBukti $dokumen, int|User|null $user = null): bool
    {
        $dokumenModel = $dokumen instanceof DokumenBukti ? $dokumen : DokumenBukti::with('belanja')->findOrFail($dokumen);
        $userModel = $user instanceof User ? $user : User::findOrFail($user ?? auth()->id());

        return $this->delete($dokumenModel, $userModel);
    }

    /**
     * Ganti/upload ulang dokumen (BR-DOK-06).
     */
    public function replace(
        DokumenBukti $existingDokumen,
        UploadedFile $file,
        User $user,
        ?string $nomorDokumen = null,
    ): DokumenBukti {
        $belanja = $existingDokumen->belanja;

        // Hapus file lama
        if ($existingDokumen->file_path && Storage::disk('public')->exists($existingDokumen->file_path)) {
            Storage::disk('public')->delete($existingDokumen->file_path);
        }

        return DB::transaction(function () use ($existingDokumen, $belanja, $file, $user, $nomorDokumen) {
            // Hapus record lama
            $jenisDokumen = $existingDokumen->jenis_dokumen->value;
            $namaDokumen = $existingDokumen->nama_dokumen;
            $this->repository->delete($existingDokumen);

            // Upload file baru
            return $this->upload(
                $belanja,
                $file,
                $jenisDokumen,
                $namaDokumen,
                $user,
                $nomorDokumen
            );
        });
    }
}
