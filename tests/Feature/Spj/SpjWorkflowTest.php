<?php

declare(strict_types=1);

namespace Tests\Feature\Spj;

use App\Enums\NotifikasiTipe;
use App\Enums\SeksiType;
use App\Enums\SpjStatus;
use App\Enums\StatusKegiatan;
use App\Enums\UserRole;
use App\Models\Kegiatan;
use App\Models\LogAktivitas;
use App\Models\Notifikasi;
use App\Models\Pengaturan;
use App\Models\Spj;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpjWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasi;
    protected User $otherKasi;
    protected User $stafKeuangan;
    protected User $sekmat;
    protected Kegiatan $kegiatan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        Pengaturan::create([
            'key' => 'tahun_anggaran_aktif',
            'value' => '2026',
        ]);

        // 1. Create Kasi Pemerintahan
        $this->kasi = User::factory()->create([
            'name' => 'Kasi Pemerintahan',
            'role' => UserRole::KASI,
            'seksi' => SeksiType::PEMERINTAHAN,
            'is_active' => true,
        ]);
        $this->kasi->assignRole(UserRole::KASI->value);

        // 2. Create Other Kasi (PMD)
        $this->otherKasi = User::factory()->create([
            'name' => 'Kasi PMD',
            'role' => UserRole::KASI,
            'seksi' => SeksiType::PMD,
            'is_active' => true,
        ]);
        $this->otherKasi->assignRole(UserRole::KASI->value);

        // 3. Create Staf Keuangan
        $this->stafKeuangan = User::factory()->create([
            'name' => 'Admin Keuangan',
            'role' => UserRole::STAF_KEUANGAN,
            'is_active' => true,
        ]);
        $this->stafKeuangan->assignRole(UserRole::STAF_KEUANGAN->value);

        // 4. Create Sekmat
        $this->sekmat = User::factory()->create([
            'name' => 'Sekretaris Kecamatan',
            'role' => UserRole::SEKMAT,
            'is_active' => true,
        ]);
        $this->sekmat->assignRole(UserRole::SEKMAT->value);

        // 5. Create Active Kegiatan for Kasi Pemerintahan
        $this->kegiatan = Kegiatan::create([
            'nama' => 'Musrenbang Tingkat Kecamatan',
            'pagu' => 25000000,
            'tahun_anggaran' => 2026,
            'kode_rekening' => '4.01.01.2.01.01',
            'sumber_dana' => 'APBD',
            'status' => StatusKegiatan::AKTIF,
            'kasi_id' => $this->kasi->id,
        ]);
    }

    public function test_kasi_can_submit_valid_spj_and_triggers_notifications_and_audit_log(): void
    {
        $file = UploadedFile::fake()->create('bukti_nota.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->kasi)->post(route('spj.store'), [
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 5000000,
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'jenis_belanja' => 'Belanja ATK dan Konsumsi Musrenbang',
            'file_bukti' => $file,
        ]);

        $response->assertRedirect(route('spj.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('spj', [
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 5000000,
            'status' => SpjStatus::DIAJUKAN_KASI->value,
            'diajukan_oleh' => $this->kasi->id,
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
        ]);

        // Notification to Staf Keuangan must exist
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->stafKeuangan->id,
            'judul' => 'Pengajuan SPJ Baru',
            'tipe' => NotifikasiTipe::ACTION->value,
        ]);

        // Audit log in log_aktivitas must exist
        $this->assertDatabaseHas('log_aktivitas', [
            'tabel_terkait' => 'spj',
            'aksi' => 'create_spj',
        ]);
    }

    public function test_br_spj_01_kasi_cannot_submit_spj_for_other_kasi_kegiatan(): void
    {
        $file = UploadedFile::fake()->create('bukti_nota.pdf', 500, 'application/pdf');

        // Other Kasi attempting to submit for Kasi Pemerintahan's activity
        $response = $this->actingAs($this->otherKasi)->post(route('spj.store'), [
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 2000000,
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'file_bukti' => $file,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, Spj::count());
    }

    public function test_br_spj_02_nominal_cannot_exceed_sisa_pagu(): void
    {
        $file = UploadedFile::fake()->create('bukti_nota.pdf', 500, 'application/pdf');

        // Pagu is 25,000,000. Attempting to submit 26,000,000 -> Exceeded!
        $response = $this->actingAs($this->kasi)->post(route('spj.store'), [
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 26000000,
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'file_bukti' => $file,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, Spj::count());
    }

    public function test_br_keu_04_cannot_submit_spj_for_inactive_kegiatan(): void
    {
        $this->kegiatan->update(['status' => StatusKegiatan::SELESAI]);
        $file = UploadedFile::fake()->create('bukti_nota.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->kasi)->post(route('spj.store'), [
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 2000000,
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'file_bukti' => $file,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, Spj::count());
    }

    public function test_br_spj_10_kasi_cannot_submit_new_spj_if_has_pending_rejected_spj(): void
    {
        // Existing rejected SPJ belonging to this Kasi
        Spj::create([
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 1000000,
            'status' => SpjStatus::DITOLAK,
            'file_bukti' => 'dummy.pdf',
            'nomor_spj' => 'SPJ/PEMERINTAHAN/IX/2026/001',
            'tanggal_pengajuan' => now(),
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'diajukan_oleh' => $this->kasi->id,
            'catatan_verifikasi' => 'Nota belum bertanda tangan basah.',
        ]);

        $file = UploadedFile::fake()->create('bukti_nota.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->kasi)->post(route('spj.store'), [
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 2000000,
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'file_bukti' => $file,
        ]);

        $response->assertSessionHas('error');
        // Only the pre-existing rejected SPJ should exist
        $this->assertEquals(1, Spj::count());
    }

    public function test_staf_keuangan_can_konsolidasi_and_generates_standard_nomor_spj(): void
    {
        $spj = Spj::create([
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 5000000,
            'status' => SpjStatus::DIAJUKAN_KASI,
            'file_bukti' => 'dummy.pdf',
            'tanggal_pengajuan' => now(),
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'diajukan_oleh' => $this->kasi->id,
        ]);

        $response = $this->actingAs($this->stafKeuangan)->put(route('spj.konsolidasi', $spj));
        $response->assertSessionHas('success');

        $fresh = $spj->fresh();
        $this->assertEquals(SpjStatus::DIKONSOLIDASI, $fresh->status);
        $this->assertEquals('SPJ/PEMERINTAHAN/IX/2026/001', $fresh->nomor_spj);
        $this->assertEquals($this->stafKeuangan->id, $fresh->dikonsolidasi_oleh);

        // Audit log status change
        $this->assertDatabaseHas('log_aktivitas', [
            'record_id' => $spj->id,
            'aksi' => 'update_status_spj',
        ]);
    }

    public function test_staf_keuangan_can_ajukan_verifikasi_and_notifies_sekmat(): void
    {
        $spj = Spj::create([
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 5000000,
            'status' => SpjStatus::DIKONSOLIDASI,
            'nomor_spj' => 'SPJ/PEMERINTAHAN/IX/2026/001',
            'file_bukti' => 'dummy.pdf',
            'tanggal_pengajuan' => now(),
            'tanggal_konsolidasi' => now(),
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'diajukan_oleh' => $this->kasi->id,
            'dikonsolidasi_oleh' => $this->stafKeuangan->id,
        ]);

        $response = $this->actingAs($this->stafKeuangan)->put(route('spj.ajukan-verifikasi', $spj));
        $response->assertSessionHas('success');

        $this->assertEquals(SpjStatus::DIAJUKAN_VERIFIKASI, $spj->fresh()->status);

        // Notification to Sekmat
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->sekmat->id,
            'judul' => 'SPJ Menunggu Verifikasi',
            'tipe' => NotifikasiTipe::ACTION->value,
        ]);
    }

    public function test_sekmat_rejects_spj_with_mandatory_notes_and_revisable_by_staf_keuangan(): void
    {
        $spj = Spj::create([
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 5000000,
            'status' => SpjStatus::DIAJUKAN_VERIFIKASI,
            'nomor_spj' => 'SPJ/PEMERINTAHAN/IX/2026/001',
            'file_bukti' => 'dummy.pdf',
            'tanggal_pengajuan' => now(),
            'tanggal_konsolidasi' => now(),
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'diajukan_oleh' => $this->kasi->id,
            'dikonsolidasi_oleh' => $this->stafKeuangan->id,
        ]);

        // 1. Sekmat attempts to reject WITHOUT note -> Validation Error (BR-SPJ-05)
        $invalidResponse = $this->actingAs($this->sekmat)->put(route('spj.verifikasi', $spj), [
            'approved' => false,
            'catatan' => 'Pendek', // Less than 10 chars
        ]);
        $invalidResponse->assertSessionHasErrors(['catatan']);

        // 2. Sekmat rejects WITH valid note (>= 10 chars)
        $validResponse = $this->actingAs($this->sekmat)->put(route('spj.verifikasi', $spj), [
            'approved' => false,
            'catatan' => 'Kuitansi pembelian ATK belum ditandatangani bendahara pengeluaran.',
        ]);
        $validResponse->assertSessionHas('success');

        $fresh = $spj->fresh();
        $this->assertEquals(SpjStatus::DITOLAK, $fresh->status);
        $this->assertNotNull($fresh->catatan_verifikasi);

        // Notifications sent to Kasi and Staf Keuangan
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->kasi->id,
            'judul' => 'SPJ Ditolak',
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->stafKeuangan->id,
            'judul' => 'SPJ Ditolak',
        ]);

        // 3. Staf Keuangan can revise rejected SPJ back to 'dikonsolidasi' (BR-SPJ-03)
        $revisionResponse = $this->actingAs($this->stafKeuangan)->put(route('spj.konsolidasi', $spj));
        $revisionResponse->assertSessionHas('success');
        $this->assertEquals(SpjStatus::DIKONSOLIDASI, $spj->fresh()->status);
    }

    public function test_sekmat_approves_spj_updates_realisasi_and_warns_if_pagu_above_80_percent(): void
    {
        // Pagu is 25,000,000. SPJ is 22,000,000 (88% > 80% threshold -> BR-KEU-03!)
        $spj = Spj::create([
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 22000000,
            'status' => SpjStatus::DIAJUKAN_VERIFIKASI,
            'nomor_spj' => 'SPJ/PEMERINTAHAN/IX/2026/001',
            'file_bukti' => 'dummy.pdf',
            'tanggal_pengajuan' => now(),
            'tanggal_konsolidasi' => now(),
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'diajukan_oleh' => $this->kasi->id,
            'dikonsolidasi_oleh' => $this->stafKeuangan->id,
        ]);

        $response = $this->actingAs($this->sekmat)->put(route('spj.verifikasi', $spj), [
            'approved' => true,
        ]);

        $response->assertSessionHas('success');

        $fresh = $spj->fresh();
        $this->assertEquals(SpjStatus::DIVERIFIKASI, $fresh->status);
        $this->assertEquals($this->sekmat->id, $fresh->diverifikasi_oleh);

        // Realisasi and Sisa Pagu updated real-time (BR-KEU-02)
        $this->assertEquals(22000000, $this->kegiatan->fresh()->total_realisasi);
        $this->assertEquals(3000000, $this->kegiatan->fresh()->sisa_pagu);

        // Warning notification sent to Staf Keuangan and Sekmat because realisasi > 80% (BR-KEU-03)
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->stafKeuangan->id,
            'judul' => 'Peringatan Pagu',
            'tipe' => NotifikasiTipe::WARNING->value,
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->sekmat->id,
            'judul' => 'Peringatan Pagu',
            'tipe' => NotifikasiTipe::WARNING->value,
        ]);
    }

    public function test_br_spj_06_verified_spj_is_immutable_cannot_be_deleted(): void
    {
        $spj = Spj::create([
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 5000000,
            'status' => SpjStatus::DIVERIFIKASI,
            'nomor_spj' => 'SPJ/PEMERINTAHAN/IX/2026/001',
            'file_bukti' => 'dummy.pdf',
            'tanggal_pengajuan' => now(),
            'tanggal_konsolidasi' => now(),
            'tanggal_verifikasi' => now(),
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'diajukan_oleh' => $this->kasi->id,
            'dikonsolidasi_oleh' => $this->stafKeuangan->id,
            'diverifikasi_oleh' => $this->sekmat->id,
        ]);

        // Policy denies deletion for any role
        $this->assertFalse($this->kasi->can('delete', $spj));
        $this->assertFalse($this->stafKeuangan->can('delete', $spj));
        $this->assertFalse($this->sekmat->can('delete', $spj));

        // Policy denies modification
        $this->assertFalse($this->kasi->can('update', $spj));
        $this->assertFalse($this->stafKeuangan->can('update', $spj));
    }

    public function test_kasi_or_staf_keuangan_can_revisi_bukti_on_rejected_spj_flow_01(): void
    {
        $spj = Spj::create([
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 5000000,
            'status' => SpjStatus::DITOLAK,
            'nomor_spj' => 'SPJ/PEMERINTAHAN/IX/2026/001',
            'file_bukti' => 'old_bukti.pdf',
            'tanggal_pengajuan' => now(),
            'tanggal_konsolidasi' => now(),
            'tanggal_verifikasi' => now(),
            'catatan_verifikasi' => 'Lampiran kwitansi belum lengkap.',
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'diajukan_oleh' => $this->kasi->id,
            'dikonsolidasi_oleh' => $this->stafKeuangan->id,
            'diverifikasi_oleh' => $this->sekmat->id,
        ]);

        $newFile = UploadedFile::fake()->create('revisi_bukti.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->kasi)->post(route('spj.revisi-bukti', $spj), [
            'file_bukti' => $newFile,
            'catatan' => 'Kwitansi bertanda tangan lengkap telah dilampirkan ulang.',
        ]);

        $response->assertSessionHas('success');
        $spj->refresh();
        $this->assertNotEquals('old_bukti.pdf', $spj->file_bukti);
        Storage::disk('public')->assertExists($spj->file_bukti);

        // Assert audit log created
        $this->assertDatabaseHas('log_aktivitas', [
            'tabel_terkait' => 'spj',
            'record_id' => $spj->id,
            'aksi' => 'revisi_bukti',
        ]);
    }
}
