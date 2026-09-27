<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\JenisKibKir;
use App\Enums\KondisiAset;
use App\Models\Aset;
use App\Models\KibKir;
use App\Models\Pengaturan;
use App\Repositories\AsetRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AsetService
{
    public function __construct(
        protected AsetRepository $asetRepository
    ) {}

    /**
     * @return LengthAwarePaginator<Aset>
     */
    public function getList(
        int $perPage = 15,
        ?string $search = null,
        ?KondisiAset $kondisi = null,
        ?string $lokasi = null,
        ?int $tahun = null,
        bool $overdueOnly = false,
        ?string $jenisDokumen = null
    ): LengthAwarePaginator {
        return $this->asetRepository->paginate(
            perPage: $perPage,
            search: $search,
            kondisi: $kondisi,
            lokasi: $lokasi,
            tahun: $tahun,
            overdueOnly: $overdueOnly,
            jenisDokumen: $jenisDokumen
        );
    }

    /**
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
        return $this->asetRepository->getStatistics();
    }

    /**
     * @return list<string>
     */
    public function getDistinctLokasi(): array
    {
        return $this->asetRepository->getDistinctLokasi();
    }

    /**
     * @return list<int>
     */
    public function getDistinctTahun(): array
    {
        return $this->asetRepository->getDistinctTahun();
    }

    /**
     * Format kode barang: {golongan}.{sub}/{urut_4digit}/{tahun}
     * Contoh: 02.06/0012/2024 (BR-ASET-01)
     */
    public function generateKodeBarang(string $golongan, string $sub, int $tahun): string
    {
        $count = Aset::where('tahun_perolehan', $tahun)->count() + 1;
        $urut = str_pad((string) $count, 4, '0', STR_PAD_LEFT);

        return "{$golongan}.{$sub}/{$urut}/{$tahun}";
    }

    /**
     * BR-ASET-04: KIB untuk aset tetap (tanah, bangunan, kendaraan, peralatan).
     * KIR untuk barang habis pakai/persediaan atau barang ruangan.
     */
    public function determineJenisKibKir(Aset $aset): JenisKibKir
    {
        $kode = trim($aset->kode_barang);

        // Standard Golongan Aset BMD:
        // 01 = Tanah (KIB A)
        // 02 = Peralatan dan Mesin (KIB B)
        // 03 = Gedung dan Bangunan (KIB C)
        // 04 = Jalan, Irigasi dan Jaringan (KIB D)
        // 05 = Aset Tetap Lainnya (KIB E)
        if (preg_match('/^0[1-5]\./', $kode)) {
            return JenisKibKir::KIB;
        }

        // Golongan 06 ke atas atau barang khusus ruangan
        return JenisKibKir::KIR;
    }

    /**
     * Generate QR Code PNG image and save to public storage.
     * BR-ASET-03: QR Code berisi link ke detail aset.
     */
    public function generateQrCode(Aset $aset): string
    {
        $url = route('aset.show', $aset->id);

        $qrCode = QrCode::create($url)
            ->setSize(320)
            ->setMargin(10);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        // Sanitize file name from kode_barang
        $safeKode = preg_replace('/[^A-Za-z0-9_\-]/', '_', $aset->kode_barang) ?? 'aset_' . $aset->id;
        $filename = "{$safeKode}.png";
        $relativePath = "qrcodes/{$filename}";

        Storage::disk('public')->put($relativePath, $result->getString());

        $aset->updateQuietly([
            'qr_code_path' => $relativePath,
        ]);

        return $relativePath;
    }

    /**
     * Generate official KIB / KIR document in PDF format.
     */
    public function generateKibKir(Aset $aset, ?int $dibuatOlehId = null, ?JenisKibKir $jenis = null): KibKir
    {
        $jenis ??= $this->determineJenisKibKir($aset);
        $aset->loadMissing(['penanggungJawab']);

        $settings = [
            'nama_kecamatan' => Pengaturan::getValue('nama_kecamatan', 'Caringin'),
            'kabupaten' => Pengaturan::getValue('kabupaten', 'Kabupaten Garut'),
            'provinsi' => Pengaturan::getValue('provinsi', 'Jawa Barat'),
            'alamat_kantor' => Pengaturan::getValue('alamat_kantor', 'Jl. Raya Caringin, Garut, Jawa Barat'),
            'nama_camat' => Pengaturan::getValue('nama_camat', 'Drs. H. Asep Mulyana, M.Si.'),
            'tahun_anggaran_aktif' => Pengaturan::getValue('tahun_anggaran_aktif', (string) date('Y')),
        ];

        // Prepare Base64 QR code for direct PDF embedding
        $qrBase64 = null;
        if ($aset->qr_code_path && Storage::disk('public')->exists($aset->qr_code_path)) {
            $qrBase64 = base64_encode(Storage::disk('public')->get($aset->qr_code_path));
        }

        $viewName = $jenis === JenisKibKir::KIB ? 'pdf.kib' : 'pdf.kir';

        $pdf = Pdf::loadView($viewName, [
            'aset' => $aset,
            'settings' => $settings,
            'qrBase64' => $qrBase64,
        ])->setPaper('a4', 'portrait');

        $filename = strtolower($jenis->value) . "_{$aset->id}_" . time() . '.pdf';
        $relativePath = "kib_kir/{$filename}";

        // Delete existing PDF file if exists
        $existing = KibKir::where('aset_id', $aset->id)->first();
        if ($existing && $existing->file_pdf && Storage::disk('public')->exists($existing->file_pdf)) {
            Storage::disk('public')->delete($existing->file_pdf);
        }

        Storage::disk('public')->put($relativePath, $pdf->output());

        return KibKir::updateOrCreate(
            ['aset_id' => $aset->id],
            [
                'jenis' => $jenis,
                'file_pdf' => $relativePath,
                'dibuat_oleh' => $dibuatOlehId ?? Auth::id() ?? 1,
            ]
        );
    }

    /**
     * Create asset with automatic QR and KIB/KIR generation.
     *
     * @param array<string, mixed> $data
     */
    public function createAset(array $data, ?UploadedFile $foto = null, ?int $userId = null): Aset
    {
        return DB::transaction(function () use ($data, $foto, $userId) {
            if ($foto !== null) {
                $ext = $foto->getClientOriginalExtension();
                $safeName = 'foto_aset_' . time() . '_' . uniqid() . '.' . $ext;
                $data['foto_path'] = $foto->storeAs('aset_fotos', $safeName, 'public');
            }

            if (! isset($data['tanggal_verifikasi_fisik'])) {
                $data['tanggal_verifikasi_fisik'] = now()->toDateString();
            }

            $aset = $this->asetRepository->create($data);

            // BR-ASET-03: Generate QR code otomatis
            $this->generateQrCode($aset);

            // BR-ASET-04: Generate KIB / KIR dokumen otomatis
            $this->generateKibKir($aset, $userId);

            return $aset->fresh(['penanggungJawab', 'kibKir']);
        });
    }

    /**
     * Update asset with automatic QR regeneration and physical check tracking.
     *
     * @param array<string, mixed> $data
     */
    public function updateAset(Aset $aset, array $data, ?UploadedFile $foto = null): Aset
    {
        return DB::transaction(function () use ($aset, $data, $foto) {
            $kodeChanged = isset($data['kode_barang']) && $data['kode_barang'] !== $aset->kode_barang;

            if ($foto !== null) {
                if ($aset->foto_path && Storage::disk('public')->exists($aset->foto_path)) {
                    Storage::disk('public')->delete($aset->foto_path);
                }

                $ext = $foto->getClientOriginalExtension();
                $safeName = 'foto_aset_' . time() . '_' . uniqid() . '.' . $ext;
                $data['foto_path'] = $foto->storeAs('aset_fotos', $safeName, 'public');
            }

            $this->asetRepository->update($aset, $data);

            // BR-ASET-03: Jika kode_barang berubah, regenerate QR code
            if ($kodeChanged) {
                $this->generateQrCode($aset);
            }

            return $aset->fresh(['penanggungJawab', 'kibKir']);
        });
    }

    /**
     * Dedicated method for quick physical check / condition & location update.
     */
    public function updateKondisiDanLokasi(int $asetId, string $kondisi, string $lokasi, ?int $penanggungJawabId = null): Aset
    {
        $aset = $this->asetRepository->findById($asetId);
        if (! $aset) {
            throw new \InvalidArgumentException("Aset dengan ID {$asetId} tidak ditemukan.");
        }

        $data = [
            'kondisi' => $kondisi,
            'lokasi' => $lokasi,
            'tanggal_verifikasi_fisik' => now()->toDateString(), // BR-ASET-05
        ];

        if ($penanggungJawabId !== null) {
            $data['penanggung_jawab'] = $penanggungJawabId;
        }

        return $this->updateAset($aset, $data);
    }
}
