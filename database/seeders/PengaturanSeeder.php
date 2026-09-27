<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Pengaturan;
use Illuminate\Database\Seeder;

class PengaturanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'nama_kecamatan',
                'value' => 'Caringin',
                'keterangan' => 'Nama Kecamatan Pengguna Sistem',
            ],
            [
                'key' => 'kabupaten',
                'value' => 'Kabupaten Garut',
                'keterangan' => 'Kabupaten Wilayah Kerja',
            ],
            [
                'key' => 'provinsi',
                'value' => 'Jawa Barat',
                'keterangan' => 'Provinsi Wilayah Kerja',
            ],
            [
                'key' => 'tahun_anggaran_aktif',
                'value' => '2026',
                'keterangan' => 'Tahun Anggaran Berjalan Aktif',
            ],
            [
                'key' => 'nama_camat',
                'value' => 'Drs. H. Asep Mulyana, M.Si.',
                'keterangan' => 'Nama Lengkap dan Gelar Camat Caringin',
            ],
            [
                'key' => 'alamat_kantor',
                'value' => 'Jl. Raya Caringin No. XX, Kec. Caringin, Kab. Garut',
                'keterangan' => 'Alamat Resmi Kantor Kecamatan Caringin',
            ],
        ];

        foreach ($settings as $setting) {
            Pengaturan::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
