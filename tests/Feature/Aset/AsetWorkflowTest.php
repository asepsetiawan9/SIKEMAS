<?php

declare(strict_types=1);

namespace Tests\Feature\Aset;

use App\Enums\CaraPerolehan;
use App\Enums\JenisKibKir;
use App\Enums\KondisiAset;
use App\Enums\NotifikasiTipe;
use App\Enums\UserRole;
use App\Models\Aset;
use App\Models\KibKir;
use App\Models\LogAktivitas;
use App\Models\Notifikasi;
use App\Models\Pengaturan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AsetWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $stafKeuangan;
    protected User $stafUmum;
    protected User $sekmat;
    protected User $kasi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        Pengaturan::create(['key' => 'nama_kecamatan', 'value' => 'Caringin']);
        Pengaturan::create(['key' => 'kabupaten', 'value' => 'Kabupaten Garut']);
        Pengaturan::create(['key' => 'tahun_anggaran_aktif', 'value' => '2026']);
        Pengaturan::create(['key' => 'nama_camat', 'value' => 'Drs. H. Asep Mulyana, M.Si.']);

        $this->stafKeuangan = User::factory()->create([
            'name' => 'Staf Keuangan',
            'role' => UserRole::STAF_KEUANGAN,
            'is_active' => true,
        ]);
        $this->stafKeuangan->assignRole(UserRole::STAF_KEUANGAN->value);

        $this->stafUmum = User::factory()->create([
            'name' => 'Staf Umum',
            'role' => UserRole::STAF_UMUM,
            'is_active' => true,
        ]);
        $this->stafUmum->assignRole(UserRole::STAF_UMUM->value);

        $this->sekmat = User::factory()->create([
            'name' => 'Sekretaris Kecamatan',
            'role' => UserRole::SEKMAT,
            'is_active' => true,
        ]);
        $this->sekmat->assignRole(UserRole::SEKMAT->value);

        $this->kasi = User::factory()->create([
            'name' => 'Kasi Pemerintahan',
            'role' => UserRole::KASI,
            'is_active' => true,
        ]);
        $this->kasi->assignRole(UserRole::KASI->value);
    }

    public function test_staf_keuangan_can_create_aset_and_auto_generates_qr_and_kib(): void
    {
        $foto = UploadedFile::fake()->image('laptop.jpg', 600, 600);

        $response = $this->actingAs($this->stafKeuangan)->post(route('aset.store'), [
            'kode_barang' => '02.06/0001/2026',
            'nama' => 'Laptop ASUS ExpertBook',
            'tahun_perolehan' => 2026,
            'nilai' => 14500000,
            'kondisi' => 'baik',
            'lokasi' => 'Ruang Keuangan',
            'penanggung_jawab' => $this->stafKeuangan->id,
            'cara_perolehan' => 'pembelian',
            'merk_type' => 'ASUS Core i5 RAM 16GB',
            'nomor_register' => 'REG-2026-001',
            'foto' => $foto,
        ]);

        $aset = Aset::where('kode_barang', '02.06/0001/2026')->first();
        $this->assertNotNull($aset);
        $response->assertRedirect(route('aset.show', $aset->id));

        // 1. Assert QR Code file exists (BR-ASET-03)
        $this->assertNotNull($aset->qr_code_path);
        Storage::disk('public')->assertExists($aset->qr_code_path);

        // 2. Assert KIB/KIR document generated (BR-ASET-04)
        $kibKir = KibKir::where('aset_id', $aset->id)->first();
        $this->assertNotNull($kibKir);
        $this->assertEquals(JenisKibKir::KIB, $kibKir->jenis);
        Storage::disk('public')->assertExists($kibKir->file_pdf);

        // 3. Assert Foto uploaded
        $this->assertNotNull($aset->foto_path);
        Storage::disk('public')->assertExists($aset->foto_path);

        // 4. Assert Activity log created
        $this->assertDatabaseHas('log_aktivitas', [
            'tabel_terkait' => 'aset',
            'record_id' => $aset->id,
            'aksi' => 'create_aset',
        ]);

        // 5. Assert Notification sent to Staf Umum
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->stafUmum->id,
            'judul' => 'Aset Baru',
            'tipe' => NotifikasiTipe::INFO->value,
        ]);
    }

    public function test_kode_barang_must_match_format_regex_br_aset_01(): void
    {
        // Format salah: tanpa slash / titik
        $response = $this->actingAs($this->stafKeuangan)->post(route('aset.store'), [
            'kode_barang' => '0206-0001-2026',
            'nama' => 'Meja Rapat',
            'tahun_perolehan' => 2026,
            'nilai' => 5000000,
            'kondisi' => 'baik',
            'lokasi' => 'Ruang Rapat',
            'penanggung_jawab' => $this->stafKeuangan->id,
            'cara_perolehan' => 'pembelian',
        ]);

        $response->assertSessionHasErrors(['kode_barang']);
        $this->assertDatabaseCount('aset', 0);
    }

    public function test_kode_barang_must_be_unique(): void
    {
        Aset::factory()->create([
            'kode_barang' => '02.06/0001/2026',
            'penanggung_jawab' => $this->stafKeuangan->id,
        ]);

        $response = $this->actingAs($this->stafKeuangan)->post(route('aset.store'), [
            'kode_barang' => '02.06/0001/2026',
            'nama' => 'Laptop Duplikat',
            'tahun_perolehan' => 2026,
            'nilai' => 10000000,
            'kondisi' => 'baik',
            'lokasi' => 'Ruang Pelayanan',
            'penanggung_jawab' => $this->stafKeuangan->id,
            'cara_perolehan' => 'pembelian',
        ]);

        $response->assertSessionHasErrors(['kode_barang']);
    }

    public function test_update_kondisi_or_lokasi_updates_tanggal_verifikasi_fisik_br_aset_05(): void
    {
        $aset = Aset::factory()->create([
            'kode_barang' => '02.06/0005/2025',
            'kondisi' => KondisiAset::BAIK,
            'lokasi' => 'Gudang Lama',
            'penanggung_jawab' => $this->stafKeuangan->id,
            'tanggal_verifikasi_fisik' => '2025-01-01',
        ]);

        // Staf umum memindahkan ke Ruang Pelayanan dan mengubah kondisi ke rusak ringan
        $response = $this->actingAs($this->stafUmum)->put(route('aset.update', $aset->id), [
            'kondisi' => 'rusak_ringan',
            'lokasi' => 'Ruang Pelayanan Baru',
            'penanggung_jawab' => $this->stafUmum->id,
        ]);

        $response->assertRedirect(route('aset.show', $aset->id));
        $aset->refresh();

        $this->assertEquals(KondisiAset::RUSAK_RINGAN, $aset->kondisi);
        $this->assertEquals('Ruang Pelayanan Baru', $aset->lokasi);
        // BR-ASET-05: tanggal verifikasi fisik terupdate ke hari ini
        $this->assertEquals(now()->toDateString(), $aset->tanggal_verifikasi_fisik->toDateString());

        // Assert audit trail tercatat di log aktivitas
        $this->assertDatabaseHas('log_aktivitas', [
            'tabel_terkait' => 'aset',
            'record_id' => $aset->id,
            'aksi' => 'update_aset',
        ]);
    }

    public function test_updating_kondisi_to_rusak_berat_triggers_warning_notification_br_aset_02(): void
    {
        $aset = Aset::factory()->create([
            'kode_barang' => '02.06/0010/2024',
            'kondisi' => KondisiAset::BAIK,
            'penanggung_jawab' => $this->stafKeuangan->id,
        ]);

        $this->actingAs($this->stafUmum)->put(route('aset.update', $aset->id), [
            'kondisi' => 'rusak_berat',
            'lokasi' => $aset->lokasi,
            'penanggung_jawab' => $aset->penanggung_jawab,
        ]);

        $aset->refresh();
        $this->assertEquals(KondisiAset::RUSAK_BERAT, $aset->kondisi);

        // BR-ASET-02: Kirim notifikasi warning ke Staf Keuangan dan Sekmat
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->stafKeuangan->id,
            'judul' => 'Peringatan Aset Rusak Berat',
            'tipe' => NotifikasiTipe::WARNING->value,
        ]);

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->sekmat->id,
            'judul' => 'Peringatan Aset Rusak Berat',
            'tipe' => NotifikasiTipe::WARNING->value,
        ]);
    }

    public function test_updating_kode_barang_regenerates_qr_code_br_aset_03(): void
    {
        $aset = Aset::factory()->create([
            'kode_barang' => '02.06/0001/2026',
            'penanggung_jawab' => $this->stafKeuangan->id,
        ]);

        // Initial QR Code
        app(\App\Services\AsetService::class)->generateQrCode($aset);
        $aset->refresh();
        $oldQrPath = $aset->qr_code_path;
        Storage::disk('public')->assertExists($oldQrPath);

        // Staf keuangan memperbarui nomor urut kode barang
        $this->actingAs($this->stafKeuangan)->put(route('aset.update', $aset->id), [
            'kode_barang' => '02.06/0099/2026',
            'nama' => $aset->nama,
            'tahun_perolehan' => $aset->tahun_perolehan,
            'nilai' => $aset->nilai,
            'kondisi' => $aset->kondisi->value,
            'lokasi' => $aset->lokasi,
            'penanggung_jawab' => $aset->penanggung_jawab,
            'cara_perolehan' => $aset->cara_perolehan->value,
        ]);

        $aset->refresh();
        $this->assertEquals('02.06/0099/2026', $aset->kode_barang);
        // BR-ASET-03: QR code ter-regenerate dengan path baru
        $this->assertNotEquals($oldQrPath, $aset->qr_code_path);
        Storage::disk('public')->assertExists($aset->qr_code_path);
    }

    public function test_aset_cannot_be_deleted_br_aset_06(): void
    {
        $aset = Aset::factory()->create([
            'kode_barang' => '02.06/0088/2026',
            'penanggung_jawab' => $this->stafKeuangan->id,
        ]);

        // Attempting to delete should be forbidden by Policy (BR-ASET-06)
        $response = $this->actingAs($this->stafKeuangan)->delete(route('aset.destroy', $aset->id));
        $response->assertForbidden();

        $this->assertDatabaseHas('aset', ['id' => $aset->id]);
    }

    public function test_can_download_qr_code_png(): void
    {
        $aset = Aset::factory()->create([
            'kode_barang' => '02.06/0050/2026',
            'penanggung_jawab' => $this->stafKeuangan->id,
        ]);

        $response = $this->actingAs($this->stafUmum)->get(route('aset.download-qr', $aset->id));
        $response->assertOk();
        $response->assertHeader('content-type', 'image/png');
    }

    public function test_can_download_kib_kir_pdf(): void
    {
        $aset = Aset::factory()->create([
            'kode_barang' => '02.06/0055/2026',
            'penanggung_jawab' => $this->stafKeuangan->id,
        ]);

        $response = $this->actingAs($this->stafKeuangan)->get(route('aset.download-kibkir', $aset->id));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_can_update_aset_via_post_with_method_spoofing_bug_01(): void
    {
        $aset = Aset::factory()->create([
            'kode_barang' => '02.06/0060/2026',
            'penanggung_jawab' => $this->stafKeuangan->id,
            'lokasi' => 'Ruang Bendahara',
        ]);

        $foto = UploadedFile::fake()->image('updated_foto.jpg', 600, 600);

        $response = $this->actingAs($this->stafKeuangan)->post(route('aset.update', $aset->id), [
            '_method' => 'put',
            'nama' => 'Aset Updated via POST',
            'lokasi' => 'Ruang Pelayanan Terpadu',
            'foto' => $foto,
        ]);

        $response->assertRedirect(route('aset.show', $aset->id));
        $aset->refresh();
        $this->assertEquals('Aset Updated via POST', $aset->nama);
        $this->assertEquals('Ruang Pelayanan Terpadu', $aset->lokasi);
        $this->assertNotNull($aset->foto_path);
    }

    public function test_can_filter_aset_by_tab_kib_kir_ux_01(): void
    {
        $responseKib = $this->actingAs($this->stafKeuangan)->get(route('aset.index', ['tab' => 'kib']));
        $responseKib->assertOk();

        $responseKir = $this->actingAs($this->stafKeuangan)->get(route('aset.index', ['tab' => 'kir']));
        $responseKir->assertOk();
    }
}
