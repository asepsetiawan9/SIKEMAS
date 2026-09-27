<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

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

class DashboardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasi;
    protected User $stafKeuangan;
    protected User $sekmat;
    protected User $camat;
    protected User $stafUmum;
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

        $this->stafUmum = User::factory()->create([
            'role' => UserRole::STAF_UMUM,
            'is_active' => true,
        ]);
        $this->stafUmum->assignRole(UserRole::STAF_UMUM->value);

        $this->kegiatan = Kegiatan::create([
            'nama' => 'Penyusunan LPPD Kecamatan',
            'pagu' => 20000000,
            'tahun_anggaran' => 2026,
            'kode_rekening' => '01.01/001/2026',
            'status' => StatusKegiatan::AKTIF,
            'kasi_id' => $this->kasi->id,
        ]);
    }

    public function test_kasi_dashboard_loads_kasi_specific_data_and_charts(): void
    {
        Spj::create([
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 5000000,
            'status' => SpjStatus::DIVERIFIKASI,
            'file_bukti' => 'bukti.pdf',
            'nomor_spj' => 'SPJ/PEMERINTAHAN/IX/2026/001',
            'tanggal_pengajuan' => now(),
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'diajukan_oleh' => $this->kasi->id,
        ]);

        $response = $this->actingAs($this->kasi)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/KasiDashboard')
            ->has('kegiatanList', 1)
            ->has('kegiatanChart', 1)
            ->has('spjStatusChart')
            ->where('stats.total_realisasi', 5000000)
            ->where('stats.sisa_pagu', 15000000)
            ->where('stats.total_spj_diajukan', 1)
        );
    }

    public function test_staf_keuangan_dashboard_loads_operational_data_and_charts(): void
    {
        Aset::create([
            'kode_barang' => '02.06/0001/2026',
            'nama' => 'Komputer Kerja Staf',
            'tahun_perolehan' => 2026,
            'nilai' => 15000000,
            'kondisi' => KondisiAset::BAIK,
            'lokasi' => 'Ruang Keuangan',
            'penanggung_jawab' => $this->stafKeuangan->id,
            'cara_perolehan' => \App\Enums\CaraPerolehan::PEMBELIAN,
        ]);

        $response = $this->actingAs($this->stafKeuangan)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/StafSekmatDashboard')
            ->where('role', 'staf_keuangan')
            ->has('realisasiKegiatanChart')
            ->has('spjStatusChart')
            ->has('asetKondisiChart')
            ->where('stats.total_aset', 1)
        );
    }

    public function test_sekmat_dashboard_shows_verifikasi_stats_and_warnings_above_80_percent(): void
    {
        // Add SPJ spent 18 million out of 20 million (90%)
        Spj::create([
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 18000000,
            'status' => SpjStatus::DIVERIFIKASI,
            'file_bukti' => 'bukti_90.pdf',
            'tanggal_pengajuan' => now(),
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'diajukan_oleh' => $this->kasi->id,
        ]);

        $response = $this->actingAs($this->sekmat)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/StafSekmatDashboard')
            ->where('role', 'sekmat')
            ->has('warningsPagu', 1)
            ->where('warningsPagu.0.is_over_80', true)
        );
    }

    public function test_camat_dashboard_loads_executive_metrics_and_seksi_breakdown(): void
    {
        $response = $this->actingAs($this->camat)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/CamatDashboard')
            ->has('stats.total_pagu')
            ->has('stats.total_realisasi')
            ->has('stats.persen_realisasi')
            ->has('seksiSummary')
            ->has('asetKondisiChart')
        );
    }

    public function test_staf_umum_is_redirected_to_aset_module(): void
    {
        $response = $this->actingAs($this->stafUmum)->get('/dashboard');

        $response->assertRedirect(route('aset.index'));
    }
}
