<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Enums\SeksiType;
use App\Enums\SpjStatus;
use App\Enums\UserRole;
use App\Models\Aset;
use App\Models\Kegiatan;
use App\Models\Spj;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_kasi_can_access_own_spj_and_cannot_access_aset_create(): void
    {
        $kasi = User::factory()->create([
            'role' => UserRole::KASI,
            'seksi' => SeksiType::PEMERINTAHAN,
            'is_active' => true,
        ]);
        $kasi->assignRole(UserRole::KASI->value);

        // Kasi can access SPJ create page
        $this->actingAs($kasi)->get(route('spj.create'))->assertStatus(200);

        // Kasi CANNOT create asset -> 403 Forbidden
        $this->actingAs($kasi)->get(route('aset.create'))->assertStatus(403);
    }

    public function test_staf_keuangan_cannot_verify_spj(): void
    {
        $keuangan = User::factory()->create([
            'role' => UserRole::STAF_KEUANGAN,
            'is_active' => true,
        ]);
        $keuangan->assignRole(UserRole::STAF_KEUANGAN->value);

        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Test',
            'pagu' => 10000000,
            'tahun_anggaran' => 2026,
            'kode_rekening' => '01.02.03',
            'sumber_dana' => 'APBD',
            'kasi_id' => $keuangan->id,
        ]);

        $spj = Spj::create([
            'kegiatan_id' => $kegiatan->id,
            'nominal' => 2000000,
            'status' => SpjStatus::DIAJUKAN_VERIFIKASI,
            'file_bukti' => 'dummy.pdf',
            'nomor_spj' => 'SPJ/PEM/IX/2026/001',
            'tanggal_pengajuan' => now(),
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'diajukan_oleh' => $keuangan->id,
        ]);

        // Staf Keuangan attempting to verify SPJ -> 403 Forbidden
        $response = $this->actingAs($keuangan)->put(route('spj.verifikasi', $spj), [
            'approved' => true,
        ]);

        $response->assertStatus(403);
    }

    public function test_sekmat_can_verify_spj(): void
    {
        $sekmat = User::factory()->create([
            'role' => UserRole::SEKMAT,
            'is_active' => true,
        ]);
        $sekmat->assignRole(UserRole::SEKMAT->value);

        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Test Sekmat',
            'pagu' => 20000000,
            'tahun_anggaran' => 2026,
            'kode_rekening' => '01.02.04',
            'sumber_dana' => 'APBD',
            'kasi_id' => $sekmat->id,
        ]);

        $spj = Spj::create([
            'kegiatan_id' => $kegiatan->id,
            'nominal' => 5000000,
            'status' => SpjStatus::DIAJUKAN_VERIFIKASI,
            'file_bukti' => 'dummy.pdf',
            'nomor_spj' => 'SPJ/TRANTIB/IX/2026/002',
            'tanggal_pengajuan' => now(),
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'diajukan_oleh' => $sekmat->id,
        ]);

        // Sekmat can verify SPJ
        $response = $this->actingAs($sekmat)->put(route('spj.verifikasi', $spj), [
            'approved' => true,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(SpjStatus::DIVERIFIKASI, $spj->fresh()->status);
    }

    public function test_aset_cannot_be_deleted_by_any_role(): void
    {
        $keuangan = User::factory()->create([
            'role' => UserRole::STAF_KEUANGAN,
            'is_active' => true,
        ]);
        $keuangan->assignRole(UserRole::STAF_KEUANGAN->value);

        $aset = Aset::create([
            'kode_barang' => '02.06/0001/2024',
            'nama' => 'Laptop Kantor',
            'tahun_perolehan' => 2024,
            'nilai' => 15000000,
            'kondisi' => 'baik',
            'lokasi' => 'Ruang Keuangan',
            'penanggung_jawab' => $keuangan->id,
            'cara_perolehan' => 'pembelian',
        ]);

        // AsetPolicy delete is always false (BR-ASET-06)
        $this->assertFalse($keuangan->can('delete', $aset));
    }

    public function test_camat_can_view_all_menus_and_verify_but_cannot_create_spj_or_aset(): void
    {
        $camat = User::factory()->create([
            'role' => UserRole::CAMAT,
            'is_active' => true,
        ]);
        $camat->assignRole(UserRole::CAMAT->value);

        // Camat can view dashboard, reports, kegiatan, spj, and aset
        $this->actingAs($camat)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($camat)->get(route('laporan.index'))->assertStatus(200);
        $this->actingAs($camat)->get(route('kegiatan.index'))->assertStatus(200);
        $this->actingAs($camat)->get(route('spj.index'))->assertStatus(200);
        $this->actingAs($camat)->get(route('aset.index'))->assertStatus(200);

        // Camat CANNOT create SPJ or create asset -> 403 Forbidden
        $this->actingAs($camat)->get(route('spj.create'))->assertStatus(403);
        $this->actingAs($camat)->get(route('aset.create'))->assertStatus(403);
    }

    public function test_super_admin_has_full_access(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);
        $superAdmin->assignRole(UserRole::SUPER_ADMIN->value);

        // Super Admin can access dashboard, reports, kegiatan, aset create, spj index and spj create
        $this->actingAs($superAdmin)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($superAdmin)->get(route('laporan.index'))->assertStatus(200);
        $this->actingAs($superAdmin)->get(route('kegiatan.index'))->assertStatus(200);
        $this->actingAs($superAdmin)->get(route('aset.index'))->assertStatus(200);
        $this->actingAs($superAdmin)->get(route('aset.create'))->assertStatus(200);
        $this->actingAs($superAdmin)->get(route('spj.index'))->assertStatus(200);
        $this->actingAs($superAdmin)->get(route('spj.create'))->assertStatus(200);
    }
}
