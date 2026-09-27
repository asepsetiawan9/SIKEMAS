<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapAsetExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data
    ) {}

    public function title(): string
    {
        return 'Rekapitulasi BMD';
    }

    public function headings(): array
    {
        return [
            ['PEMERINTAH KABUPATEN GARUT - KECAMATAN CARINGIN'],
            ['REKAPITULASI INVENTARIS BARANG MILIK DAERAH (BMD)'],
            ['Status Per: ' . now()->translatedFormat('d F Y')],
            [],
            [
                'No',
                'Kode Barang',
                'Nama Barang',
                'Merk / Tipe',
                'Tahun',
                'Kondisi',
                'Ruangan / Lokasi',
                'Nilai Perolehan (Rp)',
                'Penanggung Jawab',
            ],
        ];
    }

    public function array(): array
    {
        $rows = [];
        $asetList = $this->data['asetList'] ?? [];
        $totalNilai = 0.0;

        foreach ($asetList as $index => $aset) {
            $nilai = (float) $aset->nilai;
            $totalNilai += $nilai;
            $kondisi = $aset->kondisi instanceof \BackedEnum ? $aset->kondisi->value : (string) $aset->kondisi;

            $rows[] = [
                $index + 1,
                $aset->kode_barang,
                $aset->nama,
                $aset->merk_type ?? '-',
                $aset->tahun_perolehan,
                strtoupper(str_replace('_', ' ', $kondisi)),
                $aset->lokasi,
                $nilai,
                $aset->penanggungJawab?->name ?? '-',
            ];
        }

        $rows[] = [
            '',
            '',
            'TOTAL NILAI BMD',
            '',
            '',
            '',
            '',
            $totalNilai,
            '',
        ];

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
