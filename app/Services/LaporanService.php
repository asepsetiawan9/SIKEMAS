<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\KondisiAset;
use App\Enums\SeksiType;
use App\Enums\SpjStatus;
use App\Exports\LaporanKeuanganExport;
use App\Exports\RekapAsetExport;
use App\Models\Aset;
use App\Models\Kegiatan;
use App\Models\Pengaturan;
use App\Models\Spj;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfInstance;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanService
{
    /**
     * Get system settings array for reports.
     *
     * @return array<string, string>
     */
    public function getSettings(): array
    {
        return Pengaturan::all()->pluck('value', 'key')->toArray();
    }

    /**
     * Retrieve aggregated report data according to filters.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getLaporanData(array $filters = []): array
    {
        $jenisLaporan = $filters['jenis_laporan'] ?? 'keuangan';
        $activeYear = (int) ($filters['tahun_anggaran'] ?? Pengaturan::getValue('tahun_anggaran_aktif', '2026'));
        $settings = $this->getSettings();

        $tanggalMulai = !empty($filters['tanggal_mulai']) ? $filters['tanggal_mulai'] : null;
        $tanggalSelesai = !empty($filters['tanggal_selesai']) ? $filters['tanggal_selesai'] : null;
        $kegiatanId = !empty($filters['kegiatan_id']) ? (int) $filters['kegiatan_id'] : null;
        $seksi = !empty($filters['seksi']) ? (string) $filters['seksi'] : null;

        $periodeText = 'Semua Periode (' . $activeYear . ')';
        if ($tanggalMulai && $tanggalSelesai) {
            $periodeText = Carbon::parse($tanggalMulai)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($tanggalSelesai)->translatedFormat('d M Y');
        } elseif ($tanggalMulai) {
            $periodeText = 'Mulai ' . Carbon::parse($tanggalMulai)->translatedFormat('d M Y');
        } elseif ($tanggalSelesai) {
            $periodeText = 'Sampai ' . Carbon::parse($tanggalSelesai)->translatedFormat('d M Y');
        }

        // 1. Data Keuangan
        $kegiatanQuery = Kegiatan::where('tahun_anggaran', $activeYear)->with(['kasi', 'spj']);

        if ($kegiatanId) {
            $kegiatanQuery->where('id', $kegiatanId);
        }

        if ($seksi) {
            $kegiatanQuery->whereHas('kasi', function ($q) use ($seksi) {
                $q->where('seksi', $seksi);
            });
        }

        $kegiatanCollection = $kegiatanQuery->get();

        $kegiatanList = $kegiatanCollection->map(function (Kegiatan $keg) {
            $pagu = (float) $keg->pagu;
            $realisasi = (float) $keg->spj->where('status', SpjStatus::DIVERIFIKASI)->sum('nominal');
            $sisa = max(0.0, $pagu - $realisasi);
            $persen = $pagu > 0 ? round(($realisasi / $pagu) * 100, 1) : 0.0;

            return [
                'id' => $keg->id,
                'kode_rekening' => $keg->kode_rekening,
                'nama' => $keg->nama,
                'kasi_nama' => $keg->kasi?->name ?? 'Belum ditentukan',
                'seksi' => $keg->kasi?->seksi instanceof \BackedEnum ? $keg->kasi->seksi->value : (string) ($keg->kasi?->seksi ?? '-'),
                'pagu' => $pagu,
                'realisasi' => $realisasi,
                'sisa_pagu' => $sisa,
                'persen' => $persen,
                'status' => $keg->status instanceof \BackedEnum ? $keg->status->value : (string) $keg->status,
            ];
        });

        // Query SPJ for Financial Details
        $spjQuery = Spj::where('periode_tahun', $activeYear)->with(['kegiatan', 'diajukanOleh', 'diverifikasiOleh']);

        if ($kegiatanId) {
            $spjQuery->where('kegiatan_id', $kegiatanId);
        }

        if ($seksi) {
            $spjQuery->whereHas('kegiatan.kasi', function ($q) use ($seksi) {
                $q->where('seksi', $seksi);
            });
        }

        if ($tanggalMulai) {
            $spjQuery->whereDate('tanggal_pengajuan', '>=', $tanggalMulai);
        }

        if ($tanggalSelesai) {
            $spjQuery->whereDate('tanggal_pengajuan', '<=', $tanggalSelesai);
        }

        $spjList = $spjQuery->latest('tanggal_pengajuan')->get();

        $totalPagu = (float) $kegiatanList->sum('pagu');
        $totalRealisasi = (float) $kegiatanList->sum('realisasi');

        $keuanganSummary = [
            'total_pagu' => $totalPagu,
            'total_realisasi' => $totalRealisasi,
            'sisa_pagu' => max(0.0, $totalPagu - $totalRealisasi),
            'persen_realisasi' => $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 1) : 0.0,
            'total_kegiatan' => $kegiatanList->count(),
            'total_spj' => $spjList->count(),
        ];

        // 2. Data Aset BMD
        $asetQuery = Aset::with(['penanggungJawab']);

        if (!empty($filters['kondisi'])) {
            $asetQuery->where('kondisi', $filters['kondisi']);
        }

        if (!empty($filters['lokasi'])) {
            $asetQuery->where('lokasi', 'like', '%' . $filters['lokasi'] . '%');
        }

        if (!empty($filters['tahun_perolehan'])) {
            $asetQuery->where('tahun_perolehan', (int) $filters['tahun_perolehan']);
        }

        $asetList = $asetQuery->orderBy('kode_barang')->get();

        $asetSummary = [
            'total_aset' => $asetList->count(),
            'total_nilai' => (float) $asetList->sum('nilai'),
            'baik' => $asetList->where('kondisi', KondisiAset::BAIK)->count(),
            'rusak_ringan' => $asetList->where('kondisi', KondisiAset::RUSAK_RINGAN)->count(),
            'rusak_berat' => $asetList->where('kondisi', KondisiAset::RUSAK_BERAT)->count(),
        ];

        return [
            'filters' => [
                'jenis_laporan' => $jenisLaporan,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'kegiatan_id' => $kegiatanId,
                'seksi' => $seksi,
                'tahun_anggaran' => $activeYear,
                'kondisi' => $filters['kondisi'] ?? null,
                'lokasi' => $filters['lokasi'] ?? null,
            ],
            'activeYear' => $activeYear,
            'periodeText' => $periodeText,
            'settings' => $settings,
            'keuangan' => [
                'summary' => $keuanganSummary,
                'kegiatanList' => $kegiatanList,
                'spjList' => $spjList,
            ],
            'aset' => [
                'summary' => $asetSummary,
                'asetList' => $asetList,
            ],
        ];
    }

    /**
     * Generate PDF document according to filters.
     */
    public function exportPdf(array $filters): DomPdfInstance
    {
        $data = $this->getLaporanData($filters);
        $jenisLaporan = $data['filters']['jenis_laporan'];

        if ($jenisLaporan === 'aset') {
            $viewData = [
                'asetList' => $data['aset']['asetList'],
                'summary' => $data['aset']['summary'],
                'settings' => $data['settings'],
                'periodeText' => $data['periodeText'],
            ];
            $pdf = Pdf::loadView('pdf.rekap_aset', $viewData);
        } else {
            $viewData = [
                'kegiatanList' => $data['keuangan']['kegiatanList'],
                'spjList' => $data['keuangan']['spjList'],
                'summary' => $data['keuangan']['summary'],
                'settings' => $data['settings'],
                'activeYear' => $data['activeYear'],
                'periodeText' => $data['periodeText'],
            ];
            $pdf = Pdf::loadView('pdf.laporan_keuangan', $viewData);
        }

        $pdf->setPaper('a4', 'landscape');
        return $pdf;
    }

    /**
     * Generate Excel document according to filters.
     */
    public function exportExcel(array $filters): BinaryFileResponse
    {
        $data = $this->getLaporanData($filters);
        $jenisLaporan = $data['filters']['jenis_laporan'];
        $dateStr = now()->format('Ymd_His');

        if ($jenisLaporan === 'aset') {
            $exportData = [
                'asetList' => $data['aset']['asetList'],
                'summary' => $data['aset']['summary'],
                'settings' => $data['settings'],
            ];
            $fileName = "Rekapitulasi_Aset_BMD_Caringin_{$dateStr}.xlsx";
            return Excel::download(new RekapAsetExport($exportData), $fileName);
        }

        $exportData = [
            'kegiatanList' => $data['keuangan']['kegiatanList'],
            'spjList' => $data['keuangan']['spjList'],
            'summary' => $data['keuangan']['summary'],
            'settings' => $data['settings'],
            'activeYear' => $data['activeYear'],
            'periodeText' => $data['periodeText'],
        ];
        $fileName = "Laporan_Realisasi_Keuangan_Caringin_{$dateStr}.xlsx";
        return Excel::download(new LaporanKeuanganExport($exportData), $fileName);
    }
}
