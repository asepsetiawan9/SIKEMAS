<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanKeuanganExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data
    ) {}

    public function title(): string
    {
        return 'Realisasi Keuangan';
    }

    public function headings(): array
    {
        return [
            ['PEMERINTAH KABUPATEN GARUT - KECAMATAN CARINGIN'],
            ['LAPORAN REALISASI ANGGARAN & SPJ KEUANGAN TAHUN ' . ($this->data['activeYear'] ?? 2026)],
            ['Periode: ' . ($this->data['periodeText'] ?? 'Semua Periode')],
            [],
            [
                'No',
                'Kode Rekening',
                'Nama Kegiatan',
                'Seksi / Kasi Pengampu',
                'Pagu Anggaran (Rp)',
                'Realisasi SPJ (Rp)',
                'Sisa Pagu (Rp)',
                '% Serapan',
            ],
        ];
    }

    public function array(): array
    {
        $rows = [];
        $kegiatanList = $this->data['kegiatanList'] ?? [];
        $totalPagu = 0.0;
        $totalRealisasi = 0.0;

        foreach ($kegiatanList as $index => $keg) {
            $pagu = (float) $keg['pagu'];
            $realisasi = (float) $keg['realisasi'];
            $sisa = (float) $keg['sisa_pagu'];
            $persen = (float) $keg['persen'];

            $totalPagu += $pagu;
            $totalRealisasi += $realisasi;

            $rows[] = [
                $index + 1,
                $keg['kode_rekening'],
                $keg['nama'],
                $keg['kasi_nama'] . ' (' . ucfirst((string) $keg['seksi']) . ')',
                $pagu,
                $realisasi,
                $sisa,
                $persen . '%',
            ];
        }

        $sisaTotal = max(0.0, $totalPagu - $totalRealisasi);
        $persenTotal = $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 1) : 0.0;

        $rows[] = [
            '',
            '',
            'TOTAL KESELURUHAN',
            '',
            $totalPagu,
            $totalRealisasi,
            $sisaTotal,
            $persenTotal . '%',
        ];

        // SPJ Details section if any
        $spjList = $this->data['spjList'] ?? [];
        if (!empty($spjList)) {
            $rows[] = [];
            $rows[] = ['RINCIAN DOKUMEN SPJ TERKAIT:'];
            $rows[] = [
                'No',
                'Nomor SPJ',
                'Kegiatan',
                'Tanggal Pengajuan',
                'Nominal (Rp)',
                'Status',
                'Diajukan Oleh',
            ];

            foreach ($spjList as $idx => $spj) {
                $statusStr = $spj->status instanceof \BackedEnum ? $spj->status->value : (string) $spj->status;
                $rows[] = [
                    $idx + 1,
                    $spj->nomor_spj ?? 'Belum Bernomor',
                    $spj->kegiatan?->nama ?? '-',
                    $spj->tanggal_pengajuan ? $spj->tanggal_pengajuan->format('d/m/Y') : '-',
                    (float) $spj->nominal,
                    strtoupper($statusStr),
                    $spj->diajukanOleh?->name ?? '-',
                ];
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 13]],
            2 => ['font' => ['bold' => true, 'size' => 11]],
            3 => ['font' => ['italic' => true, 'size' => 9]],
            5 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']]],
        ];
    }
}
