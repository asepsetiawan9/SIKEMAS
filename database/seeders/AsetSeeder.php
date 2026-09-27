<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CaraPerolehan;
use App\Enums\KondisiAset;
use App\Models\Aset;
use App\Models\User;
use Illuminate\Database\Seeder;

class AsetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUmum = User::where('email', 'umum@sikemas.test')->firstOrFail();
        $kasiPmd = User::where('email', 'kasi.pmd@sikemas.test')->firstOrFail();
        $camat = User::where('email', 'camat@sikemas.test')->firstOrFail();

        $items = [
            [
                'nama' => 'Laptop HP ProBook 450 G8',
                'kode_barang' => '02.06/0001/2023',
                'tahun_perolehan' => 2023,
                'nilai' => 12500000.00,
                'kondisi' => KondisiAset::BAIK,
                'lokasi' => 'Ruang Kasi PMD',
                'penanggung_jawab' => $kasiPmd->id,
                'merk_type' => 'HP ProBook 450 G8 Intel Core i5',
                'nomor_register' => 'REG-HP-001-2023',
                'ukuran' => '15.6 inch',
                'bahan' => 'Aluminium & Polikarbonat',
                'cara_perolehan' => CaraPerolehan::PEMBELIAN,
                'tanggal_verifikasi_fisik' => '2026-01-10',
            ],
            [
                'nama' => 'Printer Epson L3210',
                'kode_barang' => '02.06/0002/2023',
                'tahun_perolehan' => 2023,
                'nilai' => 3200000.00,
                'kondisi' => KondisiAset::RUSAK_RINGAN,
                'lokasi' => 'Ruang TU',
                'penanggung_jawab' => $adminUmum->id,
                'merk_type' => 'Epson L3210 All-in-One Ink Tank',
                'nomor_register' => 'REG-EPSON-002-2023',
                'ukuran' => '375 x 347 x 179 mm',
                'bahan' => 'Plastik ABS',
                'cara_perolehan' => CaraPerolehan::PEMBELIAN,
                'tanggal_verifikasi_fisik' => '2026-02-15',
            ],
            [
                'nama' => 'Meja Kerja Kayu Jati',
                'kode_barang' => '02.04/0001/2022',
                'tahun_perolehan' => 2022,
                'nilai' => 2800000.00,
                'kondisi' => KondisiAset::BAIK,
                'lokasi' => 'Ruang Camat',
                'penanggung_jawab' => $camat->id,
                'merk_type' => 'Custom Mebel Jati Jepara',
                'nomor_register' => 'REG-MEJA-001-2022',
                'ukuran' => '160 x 80 x 75 cm',
                'bahan' => 'Kayu Jati Solid',
                'cara_perolehan' => CaraPerolehan::PEMBELIAN,
                'tanggal_verifikasi_fisik' => '2026-01-05',
            ],
            [
                'nama' => 'Kendaraan Dinas Roda 2 Honda Scoopy',
                'kode_barang' => '02.03/0001/2024',
                'tahun_perolehan' => 2024,
                'nilai' => 22000000.00,
                'kondisi' => KondisiAset::BAIK,
                'lokasi' => 'Garasi Kantor',
                'penanggung_jawab' => $adminUmum->id,
                'merk_type' => 'Honda Scoopy Prestige 110cc',
                'nomor_register' => 'D 1234 CAR',
                'ukuran' => '1.864 x 683 x 1.075 mm',
                'bahan' => 'Besi / Logam / Fiber',
                'cara_perolehan' => CaraPerolehan::PEMBELIAN,
                'tanggal_verifikasi_fisik' => '2026-03-01',
            ],
            [
                'nama' => 'AC Daikin 1.5PK Inverter',
                'kode_barang' => '02.06/0003/2022',
                'tahun_perolehan' => 2022,
                'nilai' => 7500000.00,
                'kondisi' => KondisiAset::RUSAK_BERAT,
                'lokasi' => 'Aula Kecamatan',
                'penanggung_jawab' => $adminUmum->id,
                'merk_type' => 'Daikin FTKQ35UVM4 Flash Inverter 1.5 PK',
                'nomor_register' => 'REG-AC-003-2022',
                'ukuran' => '285 x 770 x 223 mm',
                'bahan' => 'Logam / Tembaga / Plastik',
                'cara_perolehan' => CaraPerolehan::PEMBELIAN,
                'tanggal_verifikasi_fisik' => '2025-11-20',
            ],
        ];

        $asetService = app(\App\Services\AsetService::class);

        foreach ($items as $data) {
            $aset = Aset::updateOrCreate(
                ['kode_barang' => $data['kode_barang']],
                $data
            );

            if (! $aset->qr_code_path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($aset->qr_code_path)) {
                $asetService->generateQrCode($aset);
            }

            if (! $aset->kibKir) {
                $asetService->generateKibKir($aset, $adminUmum->id);
            }
        }
    }
}

