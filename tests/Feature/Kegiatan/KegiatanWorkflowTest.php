<?php

declare(strict_types=1);

namespace Tests\Feature\Kegiatan;

use App\Enums\SeksiType;
use App\Enums\SpjStatus;
use App\Enums\StatusKegiatan;
use App\Enums\SumberDana;
use App\Enums\UserRole;
use App\Models\Kegiatan;
use App\Models\LogAktivitas;
use App\Models\Spj;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KegiatanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $stafKeuangan;
    protected User $kasi;
    protected User $stafUmum;
    protected User $sekmat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        // Staf Keuangan
        $this->stafKeuangan = User::factory()->create([
            'name' => 'Staf Keuangan Caringin',
            'role' => UserRole::STAF_KEUANGAN,
            'is_active' => true,
        ]);
        $this->stafKeuangan->assignRole(UserRole::STAF_KEUANGAN->value);

        // Kasi Pem
        $this->kasi = User::factory()->create([
            'name' => 'Kasi Pemerintahan',
            'role' => UserRole::KASI,
            'seksi' => SeksiType::PEMERINTAHAN,
            'is_active' => true,
        ]);
        $this->kasi->assignRole(UserRole::KASI->value);

        // Staf Umum
        $this->stafUmum = User::factory()->create([
            'name' => 'Staf Umum',
            'role' => UserRole::STAF_UMUM,
            'is_active' => true,
        ]);
        $this->stafUmum->assignRole(UserRole::STAF_UMUM->value);

        // Sekmat
        $this->sekmat = User::factory()->create([
            'name' => 'Sekmat Caringin',
            'role' => UserRole::SEKMAT,
            'is_active' => true,
        ]);
        $this->sekmat->assignRole(UserRole::SEKMAT->value);
    }

    public function test_staf_keuangan_can_view_kegiatan_index_with_required_props(): void
    {
        Kegiatan::create([
            'nama' => 'Kegiatan Administrasi Umum',
            'pagu' => 50000000,
            'tahun_anggaran' => 2026,
            'kode_rekening' => '1.01.01.2.01.0001',
            'sumber_dana' => SumberDana::APBD->value,
            'status' => StatusKegiatan::AKTIF->value,
            'kasi_id' => $this->kasi->id,
        ]);

        $response = $this->actingAs($this->stafKeuangan)->get(route('kegiatan.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Kegiatan/Index')
            ->has('kegiatanList.data', 1)
            ->has('kasiList')
            ->has('sumberDanaOptions')
            ->has('statusOptions')
            ->where('canManage', true)
        );
    }

    public function test_staf_keuangan_can_store_new_kegiatan_successfully(): void
    {
        $payload = [
            'nama' => 'Pembinaan Lembaga Kemasyarakatan Desa',
            'pagu' => 75000000,
            'tahun_anggaran' => 2026,
            'kode_rekening' => '1.01.01.2.02.0005',
            'sumber_dana' => SumberDana::APBD->value,
            'kasi_id' => $this->kasi->id,
            'status' => StatusKegiatan::AKTIF->value,
            'deskripsi' => 'Fasilitasi pelatihan pengurus RT/RW se-Kecamatan Caringin',
        ];

        $response = $this->actingAs($this->stafKeuangan)->post(route('kegiatan.store'), $payload);

        $response->assertRedirect(route('kegiatan.index'));
        $response->assertSessionHas('success', 'Kegiatan anggaran berhasil ditambahkan.');

        $this->assertDatabaseHas('kegiatan', [
            'nama' => 'Pembinaan Lembaga Kemasyarakatan Desa',
            'kode_rekening' => '1.01.01.2.02.0005',
            'kasi_id' => $this->kasi->id,
        ]);

        // Audit log created
        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $this->stafKeuangan->id,
            'aksi' => 'create_kegiatan',
            'tabel_terkait' => 'kegiatan',
        ]);
    }

    public function test_store_kegiatan_validates_required_fields(): void
    {
        $response = $this->actingAs($this->stafKeuangan)->post(route('kegiatan.store'), []);

        $response->assertSessionHasErrors([
            'nama',
            'pagu',
            'tahun_anggaran',
            'kode_rekening',
            'sumber_dana',
            'kasi_id',
        ]);
    }

    public function test_unauthorized_user_cannot_store_kegiatan(): void
    {
        $payload = [
            'nama' => 'Kegiatan Ilegal',
            'pagu' => 10000000,
            'tahun_anggaran' => 2026,
            'kode_rekening' => '1.01.01.2.99.9999',
            'sumber_dana' => SumberDana::APBD->value,
            'kasi_id' => $this->kasi->id,
        ];

        // Kasi cannot create kegiatan
        $responseKasi = $this->actingAs($this->kasi)->post(route('kegiatan.store'), $payload);
        $responseKasi->assertForbidden();

        // Staf Umum cannot create kegiatan
        $responseUmum = $this->actingAs($this->stafUmum)->post(route('kegiatan.store'), $payload);
        $responseUmum->assertForbidden();
    }

    public function test_staf_keuangan_can_update_existing_kegiatan(): void
    {
        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Awal',
            'pagu' => 20000000,
            'tahun_anggaran' => 2026,
            'kode_rekening' => '1.01.01.2.01.0010',
            'sumber_dana' => SumberDana::DAU->value,
            'status' => StatusKegiatan::AKTIF->value,
            'kasi_id' => $this->kasi->id,
        ]);

        $updatePayload = [
            'nama' => 'Kegiatan Diperbarui',
            'pagu' => 35000000,
            'tahun_anggaran' => 2026,
            'kode_rekening' => '1.01.01.2.01.0010',
            'sumber_dana' => SumberDana::DAK->value,
            'status' => StatusKegiatan::SELESAI->value,
            'kasi_id' => $this->kasi->id,
            'deskripsi' => 'Kegiatan telah disesuaikan dan selesai',
        ];

        $response = $this->actingAs($this->stafKeuangan)->put(route('kegiatan.update', $kegiatan), $updatePayload);

        $response->assertRedirect(route('kegiatan.index'));
        $response->assertSessionHas('success', 'Kegiatan anggaran berhasil diperbarui.');

        $this->assertDatabaseHas('kegiatan', [
            'id' => $kegiatan->id,
            'nama' => 'Kegiatan Diperbarui',
            'pagu' => 35000000,
            'sumber_dana' => SumberDana::DAK->value,
            'status' => StatusKegiatan::SELESAI->value,
        ]);

        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $this->stafKeuangan->id,
            'aksi' => 'update_kegiatan',
            'tabel_terkait' => 'kegiatan',
            'record_id' => $kegiatan->id,
        ]);
    }

    public function test_staf_keuangan_can_delete_kegiatan_without_spj(): void
    {
        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Hapus',
            'pagu' => 15000000,
            'tahun_anggaran' => 2026,
            'kode_rekening' => '1.01.01.2.01.0099',
            'sumber_dana' => SumberDana::APBD->value,
            'status' => StatusKegiatan::AKTIF->value,
            'kasi_id' => $this->kasi->id,
        ]);

        $response = $this->actingAs($this->stafKeuangan)->delete(route('kegiatan.destroy', $kegiatan));

        $response->assertRedirect(route('kegiatan.index'));
        $response->assertSessionHas('success', 'Kegiatan anggaran berhasil dihapus.');

        $this->assertDatabaseMissing('kegiatan', [
            'id' => $kegiatan->id,
        ]);
    }

    public function test_staf_keuangan_cannot_delete_kegiatan_with_existing_spj(): void
    {
        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Berspj',
            'pagu' => 50000000,
            'tahun_anggaran' => 2026,
            'kode_rekening' => '1.01.01.2.01.0020',
            'sumber_dana' => SumberDana::APBD->value,
            'status' => StatusKegiatan::AKTIF->value,
            'kasi_id' => $this->kasi->id,
        ]);

        Spj::create([
            'nomor_pengajuan' => 'SPJ-TEST-001',
            'kegiatan_id' => $kegiatan->id,
            'diajukan_oleh' => $this->kasi->id,
            'nominal' => 10000000,
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'tanggal_pengajuan' => now(),
            'file_bukti' => 'spj/bukti-001.pdf',
            'status' => SpjStatus::DIAJUKAN_KASI->value,
        ]);

        $response = $this->actingAs($this->stafKeuangan)->delete(route('kegiatan.destroy', $kegiatan));

        $response->assertRedirect(route('kegiatan.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('kegiatan', [
            'id' => $kegiatan->id,
        ]);
    }
}
