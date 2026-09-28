<?php

declare(strict_types=1);

namespace Tests\Feature\V2;

use App\Enums\JenisBelanja;
use App\Enums\JenisDokumen;
use App\Enums\StatusVerifikasi;
use App\Enums\UserRole;
use App\Models\Belanja;
use App\Models\DokumenBukti;
use App\Models\SubKegiatan;
use App\Models\User;
use Database\Seeders\BelanjaV2Seeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerifikasiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;
    protected User $sekmat;
    protected User $camat;
    protected Belanja $belanja;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(BelanjaV2Seeder::class);

        $this->operator = User::where('email', 'operator1@simpelkan.test')->firstOrFail();
        $this->sekmat = User::where('role', UserRole::SEKMAT->value)->first()
            ?? User::factory()->create(['role' => UserRole::SEKMAT, 'is_active' => true]);
        $this->sekmat->assignRole(UserRole::SEKMAT->value);

        $this->camat = User::where('role', UserRole::CAMAT->value)->first()
            ?? User::factory()->create(['role' => UserRole::CAMAT, 'is_active' => true]);
        $this->camat->assignRole(UserRole::CAMAT->value);

        Storage::fake('public');

        $sub = SubKegiatan::firstOrFail();
        $this->belanja = Belanja::create([
            'sub_kegiatan_id' => $sub->id,
            'uraian' => 'Konsumsi Rapat Koordinasi Wilayah',
            'jenis_belanja' => JenisBelanja::MAMIN->value,
            'harga_satuan' => 25000,
            'volume' => 20,
            'satuan' => 'box',
            'total_nilai' => 500000,
            'tanggal_belanja' => '2026-09-28',
            'created_by' => $this->operator->id,
            'status_verifikasi' => StatusVerifikasi::DRAFT->value,
            'status_dokumen' => 'belum_lengkap',
        ]);

        // Lampirkan 1 dokumen bukti
        DokumenBukti::create([
            'belanja_id' => $this->belanja->id,
            'jenis_dokumen' => JenisDokumen::NOTA->value,
            'nama_dokumen' => 'Nota Warung Makan',
            'file_path' => 'nota.pdf',
            'file_name' => 'nota.pdf',
            'file_size' => 1024,
            'file_type' => 'application/pdf',
            'uploaded_by' => $this->operator->id,
        ]);
    }

    public function test_complete_verifikasi_lifecycle_and_rejection(): void
    {
        // 1. Operator Ajukan Verifikasi
        $ajukanResponse = $this->actingAs($this->operator)->post(route('belanja.ajukan', $this->belanja->id));
        $ajukanResponse->assertSessionHas('success');
        $this->belanja->refresh();
        $this->assertSame(StatusVerifikasi::DIAJUKAN, $this->belanja->status_verifikasi);

        // 2. Sekmat lihat antrean
        $sekmatIndex = $this->actingAs($this->sekmat)->get(route('verifikasi.sekmat.index'));
        $sekmatIndex->assertOk();
        $sekmatIndex->assertInertia(fn ($page) => $page
            ->component('Verifikasi/SekmatIndex')
            ->has('antrean')
        );

        // 3. Sekmat Kembalikan dengan catatan (BR-VER-02)
        $kembalikanSekmat = $this->actingAs($this->sekmat)->post(route('verifikasi.sekmat.kembalikan', $this->belanja->id), [
            'catatan' => 'Mohon lampirkan kwitansi bermaterai dan daftar hadir rapat.',
        ]);
        $kembalikanSekmat->assertSessionHas('success');
        $this->belanja->refresh();
        $this->assertSame(StatusVerifikasi::DIKEMBALIKAN_SEKMAT, $this->belanja->status_verifikasi);
        $this->assertSame('Mohon lampirkan kwitansi bermaterai dan daftar hadir rapat.', $this->belanja->catatan_sekmat);

        // 4. Operator ajukan ulang setelah perbaikan
        $ajukanUlang = $this->actingAs($this->operator)->post(route('belanja.ajukan', $this->belanja->id));
        $ajukanUlang->assertSessionHas('success');
        $this->belanja->refresh();
        $this->assertSame(StatusVerifikasi::DIAJUKAN, $this->belanja->status_verifikasi);

        // 5. Sekmat Verifikasi (Approve)
        $verifSekmat = $this->actingAs($this->sekmat)->post(route('verifikasi.sekmat.setujui', $this->belanja->id), [
            'catatan' => 'Dokumen sudah sesuai dan lengkap.',
        ]);
        $verifSekmat->assertSessionHas('success');
        $this->belanja->refresh();
        $this->assertSame(StatusVerifikasi::DIVERIFIKASI_SEKMAT, $this->belanja->status_verifikasi);

        // 6. Camat lihat antrean
        $camatIndex = $this->actingAs($this->camat)->get(route('verifikasi.camat.index'));
        $camatIndex->assertOk();
        $camatIndex->assertInertia(fn ($page) => $page
            ->component('Verifikasi/CamatIndex')
            ->has('antrean')
        );

        // 7. Camat Setujui (FINAL BR-VER-04)
        $setujuiCamat = $this->actingAs($this->camat)->post(route('verifikasi.camat.setujui', $this->belanja->id), [
            'catatan' => 'Disetujui untuk diarsipkan.',
        ]);
        $setujuiCamat->assertSessionHas('success');
        $this->belanja->refresh();
        $this->assertSame(StatusVerifikasi::DISETUJUI_CAMAT, $this->belanja->status_verifikasi);
        $this->assertTrue($this->belanja->isDokumenLocked());

        // 8. Pastikan dokumen terkunci dari penghapusan (BR-VER-05)
        $dokumen = $this->belanja->dokumenBukti->first();
        $deleteDoc = $this->actingAs($this->operator)->delete(route('dokumen-bukti.destroy', $dokumen->id));
        $deleteDoc->assertStatus(403);
    }
}
