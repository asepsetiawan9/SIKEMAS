<?php

declare(strict_types=1);

namespace Tests\Feature\Laporan;

use App\Enums\KondisiAset;
use App\Enums\SeksiType;
use App\Enums\SpjStatus;
use App\Enums\StatusKegiatan;
use App\Enums\UserRole;
use App\Models\Aset;
use App\Models\Kegiatan;
use App\Models\Pengaturan;
use App\Models\Spj;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LaporanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasi;
    protected User $stafKeuangan;
    protected User $sekmat;
    protected User $camat;
    protected Kegiatan $kegiatan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        Pengaturan::create([
            'key' => 'tahun_anggaran_aktif',
            'value' => '2026',
        ]);

        $this->kasi = User::factory()->create([
            'role' => UserRole::KASI,
            'seksi' => SeksiType::PEMERINTAHAN,
            'is_active' => true,
        ]);
        $this->kasi->assignRole(UserRole::KASI->value);

        $this->stafKeuangan = User::factory()->create([
            'role' => UserRole::STAF_KEUANGAN,
            'is_active' => true,
        ]);
        $this->stafKeuangan->assignRole(UserRole::STAF_KEUANGAN->value);

        $this->sekmat = User::factory()->create([
            'role' => UserRole::SEKMAT,
            'is_active' => true,
        ]);
        $this->sekmat->assignRole(UserRole::SEKMAT->value);

        $this->camat = User::factory()->create([
            'role' => UserRole::CAMAT,
            'is_active' => true,
        ]);
        $this->camat->assignRole(UserRole::CAMAT->value);

        $this->kegiatan = Kegiatan::create([
            'nama' => 'Pemberdayaan Masyarakat Desa',
            'pagu' => 30000000,
            'tahun_anggaran' => 2026,
            'kode_rekening' => '02.01/001/2026',
            'status' => StatusKegiatan::AKTIF,
            'kasi_id' => $this->kasi->id,
        ]);

        Spj::create([
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 10000000,
            'status' => SpjStatus::DIVERIFIKASI,
            'file_bukti' => 'bukti.pdf',
            'nomor_spj' => 'SPJ/PEMERINTAHAN/IX/2026/001',
            'tanggal_pengajuan' => now(),
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'diajukan_oleh' => $this->kasi->id,
        ]);

        Aset::create([
            'kode_barang' => '02.06/0001/2026',
            'nama' => 'Laptop ASUS ExpertBook',
            'tahun_perolehan' => 2026,
            'nilai' => 12500000,
            'kondisi' => KondisiAset::BAIK,
            'lokasi' => 'Ruang Camat',
            'penanggung_jawab' => $this->stafKeuangan->id,
            'cara_perolehan' => \App\Enums\CaraPerolehan::PEMBELIAN,
        ]);
    }

    public function test_unauthorized_user_cannot_access_laporan_page(): void
    {
        $response = $this->actingAs($this->kasi)->get('/laporan');

        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_laporan_index_with_data_preview(): void
    {
        $response = $this->actingAs($this->stafKeuangan)->get('/laporan');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Laporan/Index')
            ->has('reportData.keuangan.summary')
            ->has('reportData.keuangan.kegiatanList', 1)
            ->has('reportData.keuangan.spjList', 1)
            ->has('reportData.aset.summary')
            ->has('reportData.aset.asetList', 1)
            ->where('reportData.keuangan.summary.total_realisasi', 10000000)
            ->where('reportData.aset.summary.total_aset', 1)
        );
    }

    public function test_authorized_user_can_export_keuangan_pdf(): void
    {
        $response = $this->actingAs($this->sekmat)->get('/laporan/export/pdf?jenis_laporan=keuangan');

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_authorized_user_can_export_aset_pdf(): void
    {
        $response = $this->actingAs($this->sekmat)->get('/laporan/export/pdf?jenis_laporan=aset');

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_authorized_user_can_export_excel(): void
    {
        $response = $this->actingAs($this->stafKeuangan)->get('/laporan/export/excel?jenis_laporan=keuangan');

        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_user_can_filter_laporan_data_by_kegiatan(): void
    {
        $response = $this->actingAs($this->camat)->get("/laporan?kegiatan_id={$this->kegiatan->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Laporan/Index')
            ->where('reportData.keuangan.summary.total_kegiatan', 1)
            ->where('reportData.keuangan.kegiatanList.0.id', $this->kegiatan->id)
        );
    }

    public function test_unauthorized_user_cannot_export_reports(): void
    {
        $response = $this->actingAs($this->kasi)->get('/laporan/export/pdf');

        $response->assertForbidden();
    }
}
