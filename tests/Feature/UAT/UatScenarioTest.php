<?php

declare(strict_types=1);

namespace Tests\Feature\UAT;

use App\Enums\CaraPerolehan;
use App\Enums\KondisiAset;
use App\Enums\NotifikasiTipe;
use App\Enums\SeksiType;
use App\Enums\SpjStatus;
use App\Enums\StatusKegiatan;
use App\Enums\SumberDana;
use App\Enums\UserRole;
use App\Models\Aset;
use App\Models\Kegiatan;
use App\Models\Notifikasi;
use App\Models\Pengaturan;
use App\Models\Spj;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UatScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasi;
    protected User $stafKeuangan;
    protected User $sekmat;
    protected User $stafUmum;
    protected User $camat;
    protected Kegiatan $kegiatan;
    protected Aset $aset;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        Pengaturan::create([
            'key' => 'tahun_anggaran_aktif',
            'value' => '2026',
        ]);
        Pengaturan::create([
            'key' => 'nama_kecamatan',
            'value' => 'Caringin',
        ]);
        Pengaturan::create([
            'key' => 'kabupaten',
            'value' => 'Kabupaten Garut',
        ]);
        Pengaturan::create([
            'key' => 'nama_camat',
            'value' => 'Drs. H. Asep Mulyana, M.Si.',
        ]);

        // 1. Akun Kasi Pemerintahan
        $this->kasi = User::factory()->create([
            'name' => 'Kasi Pemerintahan UAT',
            'email' => 'kasi.pem.uat@sikemas.test',
            'role' => UserRole::KASI,
            'seksi' => SeksiType::PEMERINTAHAN,
            'is_active' => true,
        ]);
        $this->kasi->assignRole(UserRole::KASI->value);

        // 2. Akun Staf Keuangan
        $this->stafKeuangan = User::factory()->create([
            'name' => 'Staf Keuangan UAT',
            'email' => 'keuangan.uat@sikemas.test',
            'role' => UserRole::STAF_KEUANGAN,
            'seksi' => null,
            'is_active' => true,
        ]);
        $this->stafKeuangan->assignRole(UserRole::STAF_KEUANGAN->value);

        // 3. Akun Sekmat
        $this->sekmat = User::factory()->create([
            'name' => 'Sekretaris Kecamatan UAT',
            'email' => 'sekmat.uat@sikemas.test',
            'role' => UserRole::SEKMAT,
            'seksi' => null,
            'is_active' => true,
        ]);
        $this->sekmat->assignRole(UserRole::SEKMAT->value);

        // 4. Akun Staf Umum
        $this->stafUmum = User::factory()->create([
            'name' => 'Staf Umum UAT',
            'email' => 'umum.uat@sikemas.test',
            'role' => UserRole::STAF_UMUM,
            'seksi' => null,
            'is_active' => true,
        ]);
        $this->stafUmum->assignRole(UserRole::STAF_UMUM->value);

        // 5. Akun Camat
        $this->camat = User::factory()->create([
            'name' => 'Camat Caringin UAT',
            'email' => 'camat.uat@sikemas.test',
            'role' => UserRole::CAMAT,
            'seksi' => null,
            'is_active' => true,
        ]);
        $this->camat->assignRole(UserRole::CAMAT->value);

        // Setup Kegiatan Awal untuk Kasi Pemerintahan
        $this->kegiatan = Kegiatan::create([
            'nama' => 'Musrenbang Tingkat Kecamatan UAT',
            'kode_rekening' => '5.1.02.01.01.0001',
            'pagu' => 25000000,
            'tahun_anggaran' => 2026,
            'kasi_id' => $this->kasi->id,
            'sumber_dana' => SumberDana::APBD,
            'status' => StatusKegiatan::AKTIF,
        ]);

        // Setup Aset Awal
        $this->aset = Aset::factory()->create([
            'nama' => 'Laptop HP ProBook 450 G8 UAT',
            'kode_barang' => '02.06/0001/2026',
            'nomor_register' => 'REG-UAT-001',
            'tahun_perolehan' => 2026,
            'nilai' => 12500000,
            'kondisi' => KondisiAset::BAIK,
            'lokasi' => 'Ruang Kasi Pemerintahan',
            'penanggung_jawab' => $this->kasi->id,
            'cara_perolehan' => CaraPerolehan::PEMBELIAN,
            'tanggal_verifikasi_fisik' => now()->toDateString(),
        ]);
    }

    /**
     * Skenario UAT 1 - Role Kasi:
     * login -> lihat dashboard -> ajukan SPJ -> cek status -> terima notifikasi jika ditolak -> lihat catatan penolakan
     */
    public function test_uat_skenario_kasi(): void
    {
        // 1. Login & Dashboard Kasi
        $response = $this->actingAs($this->kasi)->get(route('dashboard'));
        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Dashboard/KasiDashboard')
            ->has('kegiatanList')
            ->has('kegiatanChart')
            ->has('spjStatusChart')
        );

        // 2. Kasi Ajukan SPJ
        $file = UploadedFile::fake()->create('bukti_kegiatan.pdf', 1024, 'application/pdf');
        $submitResponse = $this->actingAs($this->kasi)->post(route('spj.store'), [
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 5000000,
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'file_bukti' => $file,
        ]);

        $submitResponse->assertRedirect(route('spj.index'));
        $submitResponse->assertSessionHas('success');

        $spj = Spj::where('kegiatan_id', $this->kegiatan->id)->first();
        $this->assertNotNull($spj);
        $this->assertEquals(SpjStatus::DIAJUKAN_KASI, $spj->status);

        // 3. Cek Status di Antrean Kasi
        $listResponse = $this->actingAs($this->kasi)->get(route('spj.index'));
        $listResponse->assertOk();

        // 4. Simulasi Penolakan oleh Sekmat
        $spj->update([
            'status' => SpjStatus::DITOLAK,
            'catatan_verifikasi' => 'Kwitansi honor belum ditandatangani oleh penerima',
        ]);
        Notifikasi::create([
            'user_id' => $this->kasi->id,
            'judul' => 'SPJ Perlu Revisi',
            'pesan' => 'Pengajuan SPJ ditolak. Alasan: Kwitansi honor belum ditandatangani oleh penerima',
            'tipe' => NotifikasiTipe::ACTION,
            'url' => route('spj.show', $spj->id),
            'dibaca' => false,
        ]);

        // 5. Kasi Melihat Catatan Penolakan di Detail SPJ
        $detailResponse = $this->actingAs($this->kasi)->get(route('spj.show', $spj->id));
        $detailResponse->assertOk();
        $detailResponse->assertInertia(fn($page) => $page
            ->component('Spj/Show')
            ->where('spj.status', SpjStatus::DITOLAK->value)
            ->where('spj.catatan_verifikasi', 'Kwitansi honor belum ditandatangani oleh penerima')
        );

        // 6. Kasi Menerima Notifikasi
        $notif = Notifikasi::where('user_id', $this->kasi->id)->latest()->first();
        $this->assertNotNull($notif);
        $this->assertStringContainsString('Kwitansi honor', $notif->pesan);
    }

    /**
     * Skenario UAT 2 - Role Staf Keuangan:
     * login -> lihat pengajuan masuk -> konsolidasi -> ajukan verifikasi -> kelola kegiatan -> kelola aset -> export laporan
     */
    public function test_uat_skenario_staf_keuangan(): void
    {
        // Setup SPJ Diajukan Kasi
        $spj = Spj::create([
            'kegiatan_id' => $this->kegiatan->id,
            'diajukan_oleh' => $this->kasi->id,
            'nominal' => 3000000,
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'file_bukti' => 'spj/bukti_dummy.pdf',
            'status' => SpjStatus::DIAJUKAN_KASI,
            'tanggal_pengajuan' => now(),
        ]);

        // 1. Login & Dashboard Staf Keuangan
        $dashboardResponse = $this->actingAs($this->stafKeuangan)->get(route('dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertInertia(fn($page) => $page
            ->component('Dashboard/StafSekmatDashboard')
            ->where('role', 'staf_keuangan')
        );

        // 2. Lihat Pengajuan Masuk
        $indexResponse = $this->actingAs($this->stafKeuangan)->get(route('spj.index'));
        $indexResponse->assertOk();
        $indexResponse->assertInertia(fn($page) => $page
            ->component('Spj/KonsolidasiIndex')
        );

        // 3. Konsolidasi (Generate Nomor SPJ Resmi)
        $konsolidasiResponse = $this->actingAs($this->stafKeuangan)->put(route('spj.konsolidasi', $spj->id));
        $konsolidasiResponse->assertRedirect();
        $spj->refresh();
        $this->assertEquals(SpjStatus::DIKONSOLIDASI, $spj->status);
        $this->assertNotNull($spj->nomor_spj);
        $this->assertStringStartsWith('SPJ/PEMERINTAHAN/', $spj->nomor_spj);

        // 4. Ajukan Verifikasi ke Sekmat
        $ajukanResponse = $this->actingAs($this->stafKeuangan)->put(route('spj.ajukan-verifikasi', $spj->id));
        $ajukanResponse->assertRedirect();
        $spj->refresh();
        $this->assertEquals(SpjStatus::DIAJUKAN_VERIFIKASI, $spj->status);

        // 5. Kelola Kegiatan
        $kegiatanResponse = $this->actingAs($this->stafKeuangan)->get(route('kegiatan.index'));
        $kegiatanResponse->assertOk();

        // 6. Kelola Aset (Monitoring BMD)
        $asetResponse = $this->actingAs($this->stafKeuangan)->get(route('aset.index'));
        $asetResponse->assertOk();

        // 7. Export Laporan Keuangan (PDF & Excel)
        $pdfResponse = $this->actingAs($this->stafKeuangan)->get(route('laporan.export-pdf', [
            'tipe' => 'keuangan',
            'kegiatan_id' => $this->kegiatan->id,
        ]));
        $pdfResponse->assertOk();
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('Content-Type'));

        $excelResponse = $this->actingAs($this->stafKeuangan)->get(route('laporan.export-excel', [
            'tipe' => 'keuangan',
        ]));
        $excelResponse->assertOk();
        $this->assertStringContainsString('spreadsheet', $excelResponse->headers->get('Content-Type'));
    }

    /**
     * Skenario UAT 3 - Role Sekmat:
     * login -> lihat dashboard -> verifikasi SPJ (approve & reject) -> monitoring lintas seksi -> export laporan
     */
    public function test_uat_skenario_sekmat(): void
    {
        // 1. Login & Dashboard Sekmat
        $dashboardResponse = $this->actingAs($this->sekmat)->get(route('dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertInertia(fn($page) => $page
            ->component('Dashboard/StafSekmatDashboard')
            ->where('role', 'sekmat')
        );

        // 2. Verifikasi SPJ - Reject dengan Alasan Wajib
        $spjReject = Spj::create([
            'kegiatan_id' => $this->kegiatan->id,
            'diajukan_oleh' => $this->kasi->id,
            'dikonsolidasi_oleh' => $this->stafKeuangan->id,
            'nomor_spj' => 'SPJ/PEMERINTAHAN/IX/2026/088',
            'nominal' => 2000000,
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'file_bukti' => 'spj/bukti_reject.pdf',
            'status' => SpjStatus::DIAJUKAN_VERIFIKASI,
            'tanggal_pengajuan' => now(),
            'tanggal_konsolidasi' => now(),
        ]);

        $rejectResponse = $this->actingAs($this->sekmat)->put(route('spj.verifikasi', $spjReject->id), [
            'approved' => false,
            'catatan' => 'Daftar hadir peserta rapat tidak dilampirkan lengkap',
        ]);
        $rejectResponse->assertRedirect();
        $spjReject->refresh();
        $this->assertEquals(SpjStatus::DITOLAK, $spjReject->status);
        $this->assertEquals('Daftar hadir peserta rapat tidak dilampirkan lengkap', $spjReject->catatan_verifikasi);

        // 3. Verifikasi SPJ - Approve
        $spjApprove = Spj::create([
            'kegiatan_id' => $this->kegiatan->id,
            'diajukan_oleh' => $this->kasi->id,
            'dikonsolidasi_oleh' => $this->stafKeuangan->id,
            'nomor_spj' => 'SPJ/PEMERINTAHAN/IX/2026/089',
            'nominal' => 4000000,
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'file_bukti' => 'spj/bukti_approve.pdf',
            'status' => SpjStatus::DIAJUKAN_VERIFIKASI,
            'tanggal_pengajuan' => now(),
            'tanggal_konsolidasi' => now(),
        ]);

        $approveResponse = $this->actingAs($this->sekmat)->put(route('spj.verifikasi', $spjApprove->id), [
            'approved' => true,
        ]);
        $approveResponse->assertRedirect();
        $spjApprove->refresh();
        $this->assertEquals(SpjStatus::DIVERIFIKASI, $spjApprove->status);

        // 4. Monitoring Lintas Seksi & Export Laporan
        $laporanResponse = $this->actingAs($this->sekmat)->get(route('laporan.index'));
        $laporanResponse->assertOk();

        $pdfRekapAset = $this->actingAs($this->sekmat)->get(route('laporan.export-pdf', [
            'tipe' => 'aset',
        ]));
        $pdfRekapAset->assertOk();
        $this->assertEquals('application/pdf', $pdfRekapAset->headers->get('Content-Type'));
    }

    /**
     * Skenario UAT 4 - Role Staf Umum:
     * login -> lihat daftar aset -> update kondisi/lokasi -> cetak QR
     */
    public function test_uat_skenario_staf_umum(): void
    {
        // 1. Login Staf Umum (Otomatis redirect dari dashboard ke modul aset)
        $loginRedirect = $this->actingAs($this->stafUmum)->get(route('dashboard'));
        $loginRedirect->assertRedirect(route('aset.index'));

        // 2. Lihat Daftar Aset BMD
        $indexResponse = $this->actingAs($this->stafUmum)->get(route('aset.index'));
        $indexResponse->assertOk();
        $indexResponse->assertInertia(fn($page) => $page
            ->component('Aset/Index')
            ->has('assets')
            ->has('statistics')
        );

        // 3. Update Kondisi & Lokasi Fisik (Verifikasi Lapangan)
        $pastDate = now()->subDays(10)->toDateString();
        $this->aset->update(['tanggal_verifikasi_fisik' => $pastDate]);

        $updateResponse = $this->actingAs($this->stafUmum)->put(route('aset.update', $this->aset->id), [
            'nama' => $this->aset->nama,
            'kode_barang' => $this->aset->kode_barang,
            'nomor_register' => $this->aset->nomor_register,
            'merk_type' => $this->aset->merk_type,
            'tahun_perolehan' => $this->aset->tahun_perolehan,
            'nilai' => $this->aset->nilai,
            'kondisi' => KondisiAset::RUSAK_RINGAN->value,
            'lokasi' => 'Ruang Pelayanan Terpadu',
            'penanggung_jawab' => $this->kasi->id,
            'cara_perolehan' => CaraPerolehan::PEMBELIAN->value,
        ]);

        $updateResponse->assertRedirect();
        $this->aset->refresh();
        $this->assertEquals(KondisiAset::RUSAK_RINGAN, $this->aset->kondisi);
        $this->assertEquals('Ruang Pelayanan Terpadu', $this->aset->lokasi);
        // BR-ASET-05: tanggal_verifikasi_fisik otomatis hari ini
        $this->assertEquals(now()->toDateString(), $this->aset->tanggal_verifikasi_fisik->toDateString());

        // 4. Unduh Label Stiker QR Code PNG
        $qrResponse = $this->actingAs($this->stafUmum)->get(route('aset.download-qr', $this->aset->id));
        $qrResponse->assertOk();
        $this->assertEquals('image/png', $qrResponse->headers->get('Content-Type'));

        // 5. Unduh Dokumen KIB/KIR PDF
        $kibKirResponse = $this->actingAs($this->stafUmum)->get(route('aset.download-kibkir', $this->aset->id));
        $kibKirResponse->assertOk();
        $this->assertEquals('application/pdf', $kibKirResponse->headers->get('Content-Type'));
    }

    /**
     * Skenario UAT 5 - Role Camat:
     * login -> lihat dashboard eksekutif (read-only)
     */
    public function test_uat_skenario_camat(): void
    {
        // 1. Login Camat & Akses Dashboard Eksekutif
        $dashboardResponse = $this->actingAs($this->camat)->get(route('dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertInertia(fn($page) => $page
            ->component('Dashboard/CamatDashboard')
            ->has('stats.total_pagu')
            ->has('stats.total_realisasi')
            ->has('stats.persen_realisasi')
            ->has('seksiSummary')
            ->has('asetKondisiChart')
        );

        // 2. Read-Only Protection: Camat tidak boleh membuat SPJ
        $file = UploadedFile::fake()->create('test.pdf', 500, 'application/pdf');
        $forbiddenSpjResponse = $this->actingAs($this->camat)->post(route('spj.store'), [
            'kegiatan_id' => $this->kegiatan->id,
            'nominal' => 1000000,
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'file_bukti' => $file,
        ]);
        $forbiddenSpjResponse->assertForbidden();

        // 3. Read-Only Protection: Camat tidak boleh menghapus aset
        $forbiddenAsetDelete = $this->actingAs($this->camat)->delete(route('aset.destroy', $this->aset->id));
        $forbiddenAsetDelete->assertForbidden();

        // 4. Camat dapat memantau dan mengekspor laporan eksekutif
        $laporanResponse = $this->actingAs($this->camat)->get(route('laporan.index'));
        $laporanResponse->assertOk();
    }
}
