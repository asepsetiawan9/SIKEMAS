<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SeksiType;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@sikemas.test',
                'role' => UserRole::SUPER_ADMIN,
                'seksi' => null,
                'nip' => '198001011999011000',
                'jabatan' => 'Super Administrator / Full Access',
                'no_hp' => '081234567800',
            ],
            [
                'name' => 'Admin Umum',
                'email' => 'umum@sikemas.test',
                'role' => UserRole::STAF_UMUM,
                'seksi' => null,
                'nip' => '198501012010011001',
                'jabatan' => 'Staf Pengurus Barang / Kasubag Umum',
                'no_hp' => '081234567801',
            ],
            [
                'name' => 'Admin Keuangan',
                'email' => 'keuangan@sikemas.test',
                'role' => UserRole::STAF_KEUANGAN,
                'seksi' => null,
                'nip' => '198601012010011002',
                'jabatan' => 'Bendahara Pengeluaran / Staf Keuangan',
                'no_hp' => '081234567802',
            ],
            [
                'name' => 'Kasubag Keuangan',
                'email' => 'kasubag.keuangan@sikemas.test',
                'role' => UserRole::STAF_KEUANGAN,
                'seksi' => null,
                'nip' => '198402122008011002',
                'jabatan' => 'Kasubag Perencanaan dan Keuangan',
                'no_hp' => '081234567810',
            ],
            [
                'name' => 'Kasi Pemerintahan',
                'email' => 'kasi.pem@sikemas.test',
                'role' => UserRole::KASI,
                'seksi' => SeksiType::PEMERINTAHAN,
                'nip' => '197801012005011001',
                'jabatan' => 'Kepala Seksi Tata Pemerintahan',
                'no_hp' => '081234567803',
            ],
            [
                'name' => 'Kasi Trantib',
                'email' => 'kasi.trantib@sikemas.test',
                'role' => UserRole::KASI,
                'seksi' => SeksiType::TRANTIB,
                'nip' => '197901012005011002',
                'jabatan' => 'Kepala Seksi Ketentraman & Ketertiban',
                'no_hp' => '081234567804',
            ],
            [
                'name' => 'Kasi PMD',
                'email' => 'kasi.pmd@sikemas.test',
                'role' => UserRole::KASI,
                'seksi' => SeksiType::PMD,
                'nip' => '198001012005011003',
                'jabatan' => 'Kepala Seksi Pemberdayaan Masyarakat Desa',
                'no_hp' => '081234567805',
            ],
            [
                'name' => 'Kasi Kessos',
                'email' => 'kasi.kessos@sikemas.test',
                'role' => UserRole::KASI,
                'seksi' => SeksiType::KESSOS,
                'nip' => '198101012005011004',
                'jabatan' => 'Kepala Seksi Kesejahteraan Sosial',
                'no_hp' => '081234567806',
            ],
            [
                'name' => 'Kasi Pelayanan',
                'email' => 'kasi.pelayanan@sikemas.test',
                'role' => UserRole::KASI,
                'seksi' => SeksiType::PELAYANAN,
                'nip' => '198201012005011005',
                'jabatan' => 'Kepala Seksi Pelayanan Umum',
                'no_hp' => '081234567807',
            ],
            [
                'name' => 'Sekretaris Kecamatan',
                'email' => 'sekmat@sikemas.test',
                'role' => UserRole::SEKMAT,
                'seksi' => null,
                'nip' => '197501012000011001',
                'jabatan' => 'Sekretaris Kecamatan',
                'no_hp' => '081234567808',
            ],
            [
                'name' => 'Camat Caringin',
                'email' => 'camat@sikemas.test',
                'role' => UserRole::CAMAT,
                'seksi' => null,
                'nip' => '197001011998011001',
                'jabatan' => 'Camat Caringin',
                'no_hp' => '081234567809',
            ],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role' => $data['role'],
                    'seksi' => $data['seksi'],
                    'nip' => $data['nip'],
                    'jabatan' => $data['jabatan'],
                    'no_hp' => $data['no_hp'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$data['role']->value]);
        }
    }
}
