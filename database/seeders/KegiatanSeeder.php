<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StatusKegiatan;
use App\Enums\SumberDana;
use App\Models\Kegiatan;
use App\Models\User;
use Illuminate\Database\Seeder;

class KegiatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kasiPem = User::where('email', 'kasi.pem@sikemas.test')->firstOrFail();
        $kasiTrantib = User::where('email', 'kasi.trantib@sikemas.test')->firstOrFail();
        $kasiPmd = User::where('email', 'kasi.pmd@sikemas.test')->firstOrFail();
        $kasiKessos = User::where('email', 'kasi.kessos@sikemas.test')->firstOrFail();
        $kasiPelayanan = User::where('email', 'kasi.pelayanan@sikemas.test')->firstOrFail();

        $items = [
            [
                'nama' => 'Musrenbang Tingkat Kecamatan',
                'pagu' => 25000000.00,
                'tahun_anggaran' => 2026,
                'kode_rekening' => '5.1.02.01.01.0001',
                'sumber_dana' => SumberDana::APBD,
                'status' => StatusKegiatan::AKTIF,
                'deskripsi' => 'Penyelenggaraan Musyawarah Perencanaan Pembangunan tingkat Kecamatan Caringin tahun 2026.',
                'periode_mulai' => '2026-02-01',
                'periode_selesai' => '2026-03-31',
                'kasi_id' => $kasiPem->id,
            ],
            [
                'nama' => 'Operasi Yustisi Gabungan',
                'pagu' => 15000000.00,
                'tahun_anggaran' => 2026,
                'kode_rekening' => '5.1.02.02.01.0002',
                'sumber_dana' => SumberDana::APBD,
                'status' => StatusKegiatan::AKTIF,
                'deskripsi' => 'Operasi penegakan ketertiban umum dan perlindungan masyarakat bersama Satpol PP.',
                'periode_mulai' => '2026-03-01',
                'periode_selesai' => '2026-11-30',
                'kasi_id' => $kasiTrantib->id,
            ],
            [
                'nama' => 'Pelatihan UMKM Desa',
                'pagu' => 30000000.00,
                'tahun_anggaran' => 2026,
                'kode_rekening' => '5.1.02.03.01.0003',
                'sumber_dana' => SumberDana::DAK,
                'status' => StatusKegiatan::AKTIF,
                'deskripsi' => 'Pemberdayaan masyarakat desa dan pelatihan kewirausahaan UMKM lokal.',
                'periode_mulai' => '2026-04-01',
                'periode_selesai' => '2026-08-31',
                'kasi_id' => $kasiPmd->id,
            ],
            [
                'nama' => 'Bantuan Sosial PKH',
                'pagu' => 50000000.00,
                'tahun_anggaran' => 2026,
                'kode_rekening' => '5.1.02.04.01.0004',
                'sumber_dana' => SumberDana::APBD,
                'status' => StatusKegiatan::AKTIF,
                'deskripsi' => 'Fasilitasi pendataan dan monitoring penyaluran bantuan sosial Program Keluarga Harapan.',
                'periode_mulai' => '2026-01-15',
                'periode_selesai' => '2026-12-15',
                'kasi_id' => $kasiKessos->id,
            ],
            [
                'nama' => 'Pelayanan KTP-el dan KK',
                'pagu' => 10000000.00,
                'tahun_anggaran' => 2026,
                'kode_rekening' => '5.1.02.05.01.0005',
                'sumber_dana' => SumberDana::DAU,
                'status' => StatusKegiatan::AKTIF,
                'deskripsi' => 'Peningkatan sarana dan layanan administrasi kependudukan (KTP-el dan Kartu Keluarga).',
                'periode_mulai' => '2026-01-01',
                'periode_selesai' => '2026-12-31',
                'kasi_id' => $kasiPelayanan->id,
            ],
        ];

        foreach ($items as $data) {
            Kegiatan::updateOrCreate(
                ['kode_rekening' => $data['kode_rekening'], 'tahun_anggaran' => $data['tahun_anggaran']],
                $data
            );
        }
    }
}
