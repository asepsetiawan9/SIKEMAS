<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\JenisBelanja;
use App\Enums\UserRole;
use App\Models\Belanja;
use App\Models\KegiatanRap;
use App\Models\Program;
use App\Models\SubKegiatan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BelanjaV2Seeder extends Seeder
{
    /**
     * Seed data v2.0: role operator, program/kegiatan/sub kegiatan, belanja contoh.
     */
    public function run(): void
    {
        // ── 1. Role & Permission Operator ──
        $this->seedOperatorRole();

        // ── 2. Akun Operator ──
        $this->seedOperatorUsers();

        // ── 3. Data Program → Kegiatan → Sub Kegiatan → Belanja ──
        $this->seedProgramHierarchy();
    }

    private function seedOperatorRole(): void
    {
        $role = Role::firstOrCreate(
            ['name' => UserRole::OPERATOR->value, 'guard_name' => 'web']
        );

        // Permission set untuk operator
        $operatorPermissions = [
            'program.view',
            'program.create',
            'program.edit',
            'belanja.view',
            'belanja.create',
            'belanja.edit',
            'belanja.delete',
            'dokumen.upload',
            'dokumen.delete',
            'belanja.ajukan',
            'laporan.view',
        ];

        foreach ($operatorPermissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        $role->syncPermissions($operatorPermissions);

        // Tambahkan permission verifikasi ke Sekmat & Camat
        $verifikasiPerms = [
            'program.view', 'belanja.view', 'belanja.verifikasi', 'belanja.setujui', 'laporan.view', 'laporan.export',
        ];
        foreach ($verifikasiPerms as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        $sekmatRole = Role::where('name', UserRole::SEKMAT->value)->first();
        if ($sekmatRole) {
            $sekmatRole->givePermissionTo(['program.view', 'belanja.view', 'belanja.verifikasi', 'laporan.view', 'laporan.export']);
        }

        $camatRole = Role::where('name', UserRole::CAMAT->value)->first();
        if ($camatRole) {
            $camatRole->givePermissionTo(['program.view', 'belanja.view', 'belanja.setujui', 'laporan.view', 'laporan.export']);
        }
    }

    private function seedOperatorUsers(): void
    {
        // Operator 1
        $operator1 = User::firstOrCreate(
            ['email' => 'operator1@simpelkan.test'],
            [
                'name' => 'Operator SPJ 1',
                'password' => Hash::make('password'),
                'jabatan' => 'Operator SPJ',
                'role' => UserRole::OPERATOR->value,
                'is_active' => true,
            ]
        );
        $operator1->assignRole(UserRole::OPERATOR->value);

        // Operator 2
        $operator2 = User::firstOrCreate(
            ['email' => 'operator2@simpelkan.test'],
            [
                'name' => 'Operator SPJ 2',
                'password' => Hash::make('password'),
                'jabatan' => 'Operator SPJ',
                'role' => UserRole::OPERATOR->value,
                'is_active' => true,
            ]
        );
        $operator2->assignRole(UserRole::OPERATOR->value);
    }

    private function seedProgramHierarchy(): void
    {
        $operator = User::where('email', 'operator1@simpelkan.test')->first();
        if (! $operator) {
            return;
        }

        // ── Program 1: Penunjang Urusan ──
        $program1 = Program::firstOrCreate(
            ['kode' => '7.01.06.01'],
            [
                'nama' => 'Program Penunjang Urusan Pemerintahan Daerah Kabupaten/Kota',
                'tahun_anggaran' => 2026,
                'deskripsi' => 'Program penunjang urusan pemerintahan daerah untuk mendukung operasional kecamatan.',
                'created_by' => $operator->id,
            ]
        );

        // Kegiatan 1.1
        $kegiatan1 = KegiatanRap::firstOrCreate(
            ['program_id' => $program1->id, 'kode' => '7.01.06.01.2.01'],
            [
                'nama' => 'Perencanaan, Penganggaran, dan Evaluasi Kinerja Perangkat Daerah',
                'deskripsi' => 'Kegiatan perencanaan dan evaluasi kinerja.',
            ]
        );

        // Sub Kegiatan 1.1.1
        $subKegiatan1 = SubKegiatan::firstOrCreate(
            ['kegiatan_rap_id' => $kegiatan1->id, 'kode' => '7.01.01.2.01.0002'],
            [
                'nama' => 'Koordinasi dan Penyusunan Dokumen RKA-SKPD',
                'deskripsi' => 'Koordinasi penyusunan dokumen anggaran.',
            ]
        );

        // Belanja contoh di Sub Kegiatan 1.1.1
        Belanja::firstOrCreate(
            ['sub_kegiatan_id' => $subKegiatan1->id, 'uraian' => 'Photo Copy B/W'],
            [
                'jenis_belanja' => JenisBelanja::CETAK->value,
                'spesifikasi' => '800 lembar',
                'harga_satuan' => 300,
                'volume' => 800,
                'satuan' => 'lembar',
                'total_nilai' => 240000,
                'tanggal_belanja' => '2026-09-15',
                'created_by' => $operator->id,
            ]
        );

        Belanja::firstOrCreate(
            ['sub_kegiatan_id' => $subKegiatan1->id, 'uraian' => 'Jamuan ringan box/snack'],
            [
                'jenis_belanja' => JenisBelanja::MAMIN->value,
                'spesifikasi' => '4 × 3 orang',
                'harga_satuan' => 18500,
                'volume' => 12,
                'satuan' => 'box',
                'total_nilai' => 222000,
                'tanggal_belanja' => '2026-09-15',
                'created_by' => $operator->id,
            ]
        );

        Belanja::firstOrCreate(
            ['sub_kegiatan_id' => $subKegiatan1->id, 'uraian' => 'Uang harian perjalanan dinas dalam kabupaten'],
            [
                'jenis_belanja' => JenisBelanja::PERDIN->value,
                'spesifikasi' => 'Perjalanan dinas dalam kabupaten selama 8 jam atau lebih',
                'harga_satuan' => 150000,
                'volume' => 3,
                'satuan' => 'orang',
                'total_nilai' => 450000,
                'tanggal_belanja' => '2026-09-20',
                'created_by' => $operator->id,
            ]
        );

        // Kegiatan 1.2
        $kegiatan2 = KegiatanRap::firstOrCreate(
            ['program_id' => $program1->id, 'kode' => '7.01.06.01.2.02'],
            [
                'nama' => 'Administrasi Keuangan Perangkat Daerah',
                'deskripsi' => 'Kegiatan administrasi keuangan kecamatan.',
            ]
        );

        $subKegiatan2 = SubKegiatan::firstOrCreate(
            ['kegiatan_rap_id' => $kegiatan2->id, 'kode' => '7.01.01.2.02.0001'],
            [
                'nama' => 'Penyediaan Gaji dan Tunjangan ASN',
                'deskripsi' => 'Pengelolaan gaji dan tunjangan.',
            ]
        );

        Belanja::firstOrCreate(
            ['sub_kegiatan_id' => $subKegiatan2->id, 'uraian' => 'Cetak slip gaji ASN'],
            [
                'jenis_belanja' => JenisBelanja::CETAK->value,
                'spesifikasi' => '50 lembar × 12 bulan',
                'harga_satuan' => 500,
                'volume' => 600,
                'satuan' => 'lembar',
                'total_nilai' => 300000,
                'tanggal_belanja' => '2026-09-01',
                'created_by' => $operator->id,
            ]
        );

        // ── Program 2: Pelayanan Terpadu ──
        $program2 = Program::firstOrCreate(
            ['kode' => '7.01.02.01'],
            [
                'nama' => 'Program Penyelenggaraan Pemerintahan dan Pelayanan Publik',
                'tahun_anggaran' => 2026,
                'deskripsi' => 'Program pelayanan publik kecamatan.',
                'created_by' => $operator->id,
            ]
        );

        $kegiatan3 = KegiatanRap::firstOrCreate(
            ['program_id' => $program2->id, 'kode' => '7.01.02.01.2.01'],
            [
                'nama' => 'Pelaksanaan Urusan Pemerintahan yang Dilimpahkan',
                'deskripsi' => 'Urusan yang dilimpahkan ke kecamatan.',
            ]
        );

        $subKegiatan3 = SubKegiatan::firstOrCreate(
            ['kegiatan_rap_id' => $kegiatan3->id, 'kode' => '7.01.02.01.2.01.0001'],
            [
                'nama' => 'Koordinasi/Sinergi Perencanaan dan Pelaksanaan Kegiatan',
                'deskripsi' => 'Koordinasi pelaksanaan kegiatan pelayanan.',
            ]
        );

        Belanja::firstOrCreate(
            ['sub_kegiatan_id' => $subKegiatan3->id, 'uraian' => 'ATK untuk pelayanan umum'],
            [
                'jenis_belanja' => JenisBelanja::ATK->value,
                'spesifikasi' => 'Kertas A4 80gsm, tinta printer, amplop',
                'harga_satuan' => 55000,
                'volume' => 10,
                'satuan' => 'paket',
                'total_nilai' => 550000,
                'tanggal_belanja' => '2026-09-10',
                'created_by' => $operator->id,
            ]
        );
    }
}
