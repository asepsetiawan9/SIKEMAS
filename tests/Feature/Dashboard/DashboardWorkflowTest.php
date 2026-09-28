<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\SeksiType;
use App\Enums\StatusKegiatan;
use App\Enums\UserRole;
use App\Models\Kegiatan;
use App\Models\Pengaturan;
use App\Models\User;
use Database\Seeders\BelanjaV2Seeder;
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
    protected User $operator;
    protected Kegiatan $kegiatan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(BelanjaV2Seeder::class);

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

        $this->sekmat = User::where('role', UserRole::SEKMAT->value)->first()
            ?? User::factory()->create(['role' => UserRole::SEKMAT, 'is_active' => true]);
        $this->sekmat->assignRole(UserRole::SEKMAT->value);

        $this->camat = User::where('role', UserRole::CAMAT->value)->first()
            ?? User::factory()->create(['role' => UserRole::CAMAT, 'is_active' => true]);
        $this->camat->assignRole(UserRole::CAMAT->value);

        $this->stafUmum = User::factory()->create([
            'role' => UserRole::STAF_UMUM,
            'is_active' => true,
        ]);
        $this->stafUmum->assignRole(UserRole::STAF_UMUM->value);

        $this->operator = User::where('email', 'operator1@simpelkan.test')->firstOrFail();
    }

    public function test_operator_dashboard_loads_metrics_and_recent_belanja(): void
    {
        $response = $this->actingAs($this->operator)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('stats')
            ->has('recentBelanja')
            ->where('userRole', 'operator')
        );
    }

    public function test_sekmat_dashboard_loads_metrics_and_role(): void
    {
        $response = $this->actingAs($this->sekmat)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('stats')
            ->has('recentBelanja')
            ->where('userRole', 'sekmat')
        );
    }

    public function test_camat_dashboard_loads_metrics_and_role(): void
    {
        $response = $this->actingAs($this->camat)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('stats')
            ->has('recentBelanja')
            ->where('userRole', 'camat')
        );
    }

    public function test_staf_keuangan_dashboard_loads_metrics_and_role(): void
    {
        $response = $this->actingAs($this->stafKeuangan)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('stats')
            ->has('recentBelanja')
            ->where('userRole', 'staf_keuangan')
        );
    }

    public function test_staf_umum_is_redirected_to_aset_module(): void
    {
        $response = $this->actingAs($this->stafUmum)->get('/dashboard');

        $response->assertRedirect(route('aset.index'));
    }
}
