<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\KondisiAset;
use App\Enums\SeksiType;
use App\Enums\SpjStatus;
use App\Enums\StatusKegiatan;
use App\Models\Aset;
use App\Models\Kegiatan;
use App\Models\Pengaturan;
use App\Models\Spj;
use App\Models\User;
use Illuminate\Support\Str;

class DashboardService
{
    /**
     * Get active fiscal year from system settings or fallback to current year.
     */
    public function getActiveYear(): int
    {
        $yearSetting = Pengaturan::getValue('tahun_anggaran_aktif', '2026');
        return (int) $yearSetting;
    }

    /**
     * Data bundle for Kasi Dashboard.
     *
     * @return array<string, mixed>
     */
    public function getKasiDashboard(User $kasi): array
    {
        $activeYear = $this->getActiveYear();

        $kegiatanCollection = Kegiatan::where('kasi_id', $kasi->id)
            ->where('tahun_anggaran', $activeYear)
            ->with(['spj'])
            ->get();

        $kegiatanList = $kegiatanCollection->map(function (Kegiatan $kegiatan) {
            $pagu = (float) $kegiatan->pagu;
            $realisasi = (float) $kegiatan->spj
                ->where('status', SpjStatus::DIVERIFIKASI)
                ->sum('nominal');
            $sisa = max(0.0, $pagu - $realisasi);
            $persen = $pagu > 0 ? round(($realisasi / $pagu) * 100, 1) : 0.0;

            return [
                'id' => $kegiatan->id,
                'nama' => $kegiatan->nama,
                'kode_rekening' => $kegiatan->kode_rekening,
                'pagu' => $pagu,
                'realisasi' => $realisasi,
                'sisa_pagu' => $sisa,
                'persen' => $persen,
                'status' => $kegiatan->status instanceof \BackedEnum ? $kegiatan->status->value : (string) $kegiatan->status,
            ];
        });

        // Chart data: Pagu vs Realisasi per kegiatan
        $kegiatanChart = $kegiatanList->map(function ($k) {
            return [
                'name' => Str::limit($k['nama'], 18),
                'fullName' => $k['nama'],
                'pagu' => $k['pagu'],
                'realisasi' => $k['realisasi'],
                'sisa' => $k['sisa_pagu'],
            ];
        })->values()->all();

        // SPJ Query for Kasi
        $allSpj = Spj::where('diajukan_oleh', $kasi->id)
            ->where('periode_tahun', $activeYear)
            ->get();

        $diverifikasiCount = $allSpj->where('status', SpjStatus::DIVERIFIKASI)->count();
        $ditolakCount = $allSpj->where('status', SpjStatus::DITOLAK)->count();
        $menungguCount = $allSpj->filter(function (Spj $s) {
            return in_array($s->status, [
                SpjStatus::DRAFT,
                SpjStatus::DIAJUKAN_KASI,
                SpjStatus::DIKONSOLIDASI,
                SpjStatus::DIAJUKAN_VERIFIKASI,
            ], true);
        })->count();

        $spjStatusChart = [
            ['name' => 'Diverifikasi', 'value' => $diverifikasiCount, 'color' => '#22c55e'],
            ['name' => 'Sedang Diproses', 'value' => $menungguCount, 'color' => '#3b82f6'],
            ['name' => 'Ditolak', 'value' => $ditolakCount, 'color' => '#ef4444'],
        ];

        $recentSpj = Spj::where('diajukan_oleh', $kasi->id)
            ->with(['kegiatan'])
            ->latest()
            ->take(5)
            ->get();

        $totalPagu = (float) $kegiatanList->sum('pagu');
        $totalRealisasi = (float) $kegiatanList->sum('realisasi');

        return [
            'kegiatanList' => $kegiatanList,
            'kegiatanChart' => $kegiatanChart,
            'spjStatusChart' => $spjStatusChart,
            'recentSpj' => $recentSpj,
            'stats' => [
                'total_kegiatan' => $kegiatanList->count(),
                'total_pagu' => $totalPagu,
                'total_realisasi' => $totalRealisasi,
                'sisa_pagu' => max(0.0, $totalPagu - $totalRealisasi),
                'persen_realisasi' => $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 1) : 0.0,
                'total_spj_diajukan' => $allSpj->count(),
                'spj_ditolak' => $ditolakCount,
                'spj_diverifikasi' => $diverifikasiCount,
                'spj_menunggu' => $menungguCount,
            ],
        ];
    }

    /**
     * Data bundle for Staf Keuangan & Sekmat Dashboard.
     *
     * @return array<string, mixed>
     */
    public function getStafSekmatDashboard(string $role = 'staf_keuangan'): array
    {
        $activeYear = $this->getActiveYear();

        $kegiatanCollection = Kegiatan::where('tahun_anggaran', $activeYear)
            ->with(['kasi', 'spj'])
            ->get();

        $totalPagu = (float) $kegiatanCollection->sum('pagu');
        $totalRealisasi = (float) Spj::where('status', SpjStatus::DIVERIFIKASI)
            ->where('periode_tahun', $activeYear)
            ->sum('nominal');

        $persenRealisasi = $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 1) : 0.0;

        $kegiatanSummary = $kegiatanCollection->map(function (Kegiatan $k) {
            $pagu = (float) $k->pagu;
            $realisasi = (float) $k->spj
                ->where('status', SpjStatus::DIVERIFIKASI)
                ->sum('nominal');
            $sisa = max(0.0, $pagu - $realisasi);
            $persen = $pagu > 0 ? round(($realisasi / $pagu) * 100, 1) : 0.0;

            return [
                'id' => $k->id,
                'nama' => $k->nama,
                'kode_rekening' => $k->kode_rekening,
                'kasi_nama' => $k->kasi?->name ?? 'Belum ditentukan',
                'pagu' => $pagu,
                'realisasi' => $realisasi,
                'sisa_pagu' => $sisa,
                'persen' => $persen,
                'status' => $k->status instanceof \BackedEnum ? $k->status->value : (string) $k->status,
                'is_over_80' => $persen >= 80.0,
            ];
        });

        // Warnings for realisasi > 80% (BR-KEU-03)
        $warningsPagu = $kegiatanSummary->filter(fn ($item) => $item['is_over_80'])->values()->all();

        // Chart: Realisasi Anggaran per Kegiatan
        $realisasiKegiatanChart = $kegiatanSummary->map(function ($k) {
            return [
                'name' => Str::limit($k['nama'], 16),
                'fullName' => $k['nama'],
                'pagu' => $k['pagu'],
                'realisasi' => $k['realisasi'],
                'sisa' => $k['sisa_pagu'],
                'persen' => $k['persen'],
            ];
        })->values()->all();

        // SPJ distribution chart
        $spjQuery = Spj::where('periode_tahun', $activeYear)->get();
        $spjStatusChart = [
            ['name' => 'Diajukan Kasi', 'value' => $spjQuery->where('status', SpjStatus::DIAJUKAN_KASI)->count(), 'color' => '#3b82f6'],
            ['name' => 'Dikonsolidasi', 'value' => $spjQuery->where('status', SpjStatus::DIKONSOLIDASI)->count(), 'color' => '#6366f1'],
            ['name' => 'Antrean Verifikasi', 'value' => $spjQuery->where('status', SpjStatus::DIAJUKAN_VERIFIKASI)->count(), 'color' => '#f59e0b'],
            ['name' => 'Diverifikasi', 'value' => $spjQuery->where('status', SpjStatus::DIVERIFIKASI)->count(), 'color' => '#22c55e'],
            ['name' => 'Ditolak', 'value' => $spjQuery->where('status', SpjStatus::DITOLAK)->count(), 'color' => '#ef4444'],
        ];

        // Aset condition chart
        $asetCounts = [
            'baik' => Aset::where('kondisi', KondisiAset::BAIK)->count(),
            'rusak_ringan' => Aset::where('kondisi', KondisiAset::RUSAK_RINGAN)->count(),
            'rusak_berat' => Aset::where('kondisi', KondisiAset::RUSAK_BERAT)->count(),
        ];

        $asetKondisiChart = [
            ['name' => 'Baik', 'value' => $asetCounts['baik'], 'color' => '#22c55e'],
            ['name' => 'Rusak Ringan', 'value' => $asetCounts['rusak_ringan'], 'color' => '#f59e0b'],
            ['name' => 'Rusak Berat', 'value' => $asetCounts['rusak_berat'], 'color' => '#ef4444'],
        ];

        $recentSpj = Spj::with(['kegiatan', 'diajukanOleh'])
            ->latest()
            ->take(8)
            ->get();

        return [
            'role' => $role,
            'stats' => [
                'total_pagu' => $totalPagu,
                'total_realisasi' => $totalRealisasi,
                'sisa_pagu' => max(0.0, $totalPagu - $totalRealisasi),
                'persen_realisasi' => $persenRealisasi,
                'pending_konsolidasi' => $spjQuery->where('status', SpjStatus::DIAJUKAN_KASI)->count(),
                'pending_verifikasi' => $spjQuery->where('status', SpjStatus::DIAJUKAN_VERIFIKASI)->count(),
                'diverifikasi' => $spjQuery->where('status', SpjStatus::DIVERIFIKASI)->count(),
                'ditolak' => $spjQuery->where('status', SpjStatus::DITOLAK)->count(),
                'total_kegiatan' => $kegiatanCollection->count(),
                'total_aset' => Aset::count(),
                'total_nilai_aset' => (float) Aset::sum('nilai'),
            ],
            'kegiatanList' => $kegiatanSummary,
            'warningsPagu' => $warningsPagu,
            'realisasiKegiatanChart' => $realisasiKegiatanChart,
            'spjStatusChart' => $spjStatusChart,
            'asetKondisiChart' => $asetKondisiChart,
            'recentSpj' => $recentSpj,
        ];
    }

    /**
     * Data bundle for Camat Executive Dashboard.
     *
     * @return array<string, mixed>
     */
    public function getCamatDashboard(): array
    {
        $activeYear = $this->getActiveYear();

        $kegiatanCollection = Kegiatan::where('tahun_anggaran', $activeYear)
            ->with(['kasi', 'spj'])
            ->get();

        $totalPagu = (float) $kegiatanCollection->sum('pagu');
        $totalRealisasi = (float) Spj::where('status', SpjStatus::DIVERIFIKASI)
            ->where('periode_tahun', $activeYear)
            ->sum('nominal');

        $persenRealisasi = $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 1) : 0.0;

        $kegiatanSummary = $kegiatanCollection->map(function (Kegiatan $k) {
            $pagu = (float) $k->pagu;
            $realisasi = (float) $k->spj
                ->where('status', SpjStatus::DIVERIFIKASI)
                ->sum('nominal');
            $persen = $pagu > 0 ? round(($realisasi / $pagu) * 100, 1) : 0.0;

            return [
                'id' => $k->id,
                'nama' => $k->nama,
                'kasi_nama' => $k->kasi?->name ?? 'Belum ditentukan',
                'seksi' => $k->kasi?->seksi instanceof \BackedEnum ? $k->kasi->seksi->value : (string) ($k->kasi?->seksi ?? '-'),
                'pagu' => $pagu,
                'realisasi' => $realisasi,
                'persen' => $persen,
            ];
        });

        // Chart: Pagu vs Realisasi per kegiatan
        $kegiatanChart = $kegiatanSummary->map(function ($k) {
            return [
                'name' => Str::limit($k['nama'], 16),
                'fullName' => $k['nama'],
                'pagu' => $k['pagu'],
                'realisasi' => $k['realisasi'],
                'persen' => $k['persen'],
            ];
        })->values()->all();

        // Rekap per Seksi
        $seksiGrouped = [];
        foreach (SeksiType::cases() as $seksi) {
            $kegiatanSeksi = $kegiatanCollection->filter(function ($k) use ($seksi) {
                return $k->kasi && ($k->kasi->seksi === $seksi || $k->kasi->seksi?->value === $seksi->value);
            });

            $paguSeksi = (float) $kegiatanSeksi->sum('pagu');
            $realisasiSeksi = (float) $kegiatanSeksi->reduce(function ($carry, $k) {
                return $carry + $k->spj->where('status', SpjStatus::DIVERIFIKASI)->sum('nominal');
            }, 0.0);

            $seksiGrouped[] = [
                'seksi' => $seksi->value,
                'label' => ucfirst($seksi->value),
                'pagu' => $paguSeksi,
                'realisasi' => $realisasiSeksi,
                'persen' => $paguSeksi > 0 ? round(($realisasiSeksi / $paguSeksi) * 100, 1) : 0.0,
            ];
        }

        $asetCounts = [
            'total_aset' => Aset::count(),
            'total_nilai' => (float) Aset::sum('nilai'),
            'baik' => Aset::where('kondisi', KondisiAset::BAIK)->count(),
            'rusak_ringan' => Aset::where('kondisi', KondisiAset::RUSAK_RINGAN)->count(),
            'rusak_berat' => Aset::where('kondisi', KondisiAset::RUSAK_BERAT)->count(),
        ];

        $asetKondisiChart = [
            ['name' => 'Baik', 'value' => $asetCounts['baik'], 'color' => '#22c55e'],
            ['name' => 'Rusak Ringan', 'value' => $asetCounts['rusak_ringan'], 'color' => '#f59e0b'],
            ['name' => 'Rusak Berat', 'value' => $asetCounts['rusak_berat'], 'color' => '#ef4444'],
        ];

        return [
            'stats' => [
                'total_pagu' => $totalPagu,
                'total_realisasi' => $totalRealisasi,
                'sisa_pagu' => max(0.0, $totalPagu - $totalRealisasi),
                'persen_realisasi' => $persenRealisasi,
                'aset' => $asetCounts,
            ],
            'kegiatanSummary' => $kegiatanSummary,
            'kegiatanChart' => $kegiatanChart,
            'seksiSummary' => $seksiGrouped,
            'asetKondisiChart' => $asetKondisiChart,
        ];
    }
}
