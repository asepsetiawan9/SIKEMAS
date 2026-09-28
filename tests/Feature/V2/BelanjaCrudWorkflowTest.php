<?php

declare(strict_types=1);

namespace Tests\Feature\V2;

use App\Enums\JenisBelanja;
use App\Enums\JenisDokumen;
use App\Enums\StatusDokumen;
use App\Enums\StatusVerifikasi;
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

class BelanjaCrudWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;
    protected SubKegiatan $subKegiatan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(BelanjaV2Seeder::class);

        $this->operator = User::where('email', 'operator1@simpelkan.test')->firstOrFail();
        $this->subKegiatan = SubKegiatan::firstOrFail();
        Storage::fake('public');
    }

    public function test_can_view_belanja_index_with_filters(): void
    {
        $response = $this->actingAs($this->operator)->get(route('belanja.index', [
            'search' => 'Photo Copy',
            'jenis_belanja' => 'cetak',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Belanja/Index')
            ->has('belanjaList')
            ->has('filters')
            ->has('programTree')
        );
    }

    public function test_can_view_belanja_create_page(): void
    {
        $response = $this->actingAs($this->operator)->get(route('belanja.create'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Belanja/Create')
            ->has('programTree')
            ->has('jenisBelanjaOptions')
        );
    }

    public function test_can_store_show_update_and_delete_belanja(): void
    {
        // 1. Store
        $response = $this->actingAs($this->operator)->post(route('belanja.store'), [
            'sub_kegiatan_id' => $this->subKegiatan->id,
            'uraian' => 'Pembelian Kertas HVS F4 75gsm',
            'jenis_belanja' => JenisBelanja::ATK->value,
            'nominal' => 275000,
            'tanggal_belanja' => '2026-09-28',
            'penerima' => 'Toko Alat Tulis Sejahtera',
            'nomor_bukti_manual' => 'INV-2026-001',
            'keterangan' => 'Kebutuhan administrasi umum',
        ]);

        $belanja = Belanja::where('uraian', 'Pembelian Kertas HVS F4 75gsm')->firstOrFail();
        $response->assertRedirect(route('belanja.show', $belanja->id));
        $this->assertEquals(275000, $belanja->total_nilai);
        $this->assertEquals('Toko Alat Tulis Sejahtera', $belanja->penerima);
        $this->assertSame(StatusVerifikasi::DRAFT, $belanja->status_verifikasi);

        // 2. Show Detail
        $detailResponse = $this->actingAs($this->operator)->get(route('belanja.show', $belanja->id));
        $detailResponse->assertOk();
        $detailResponse->assertInertia(fn ($page) => $page
            ->component('Belanja/Detail')
            ->has('belanja')
            ->has('checklist')
        );

        // 3. Edit & Update
        $editResponse = $this->actingAs($this->operator)->get(route('belanja.edit', $belanja->id));
        $editResponse->assertOk();

        $updateResponse = $this->actingAs($this->operator)->put(route('belanja.update', $belanja->id), [
            'sub_kegiatan_id' => $this->subKegiatan->id,
            'uraian' => 'Pembelian Kertas HVS F4 75gsm (Revisi)',
            'jenis_belanja' => JenisBelanja::ATK->value,
            'nominal' => 300000,
            'tanggal_belanja' => '2026-09-28',
            'penerima' => 'Toko Alat Tulis Sejahtera Baru',
            'nomor_bukti_manual' => 'INV-2026-001-REV',
            'keterangan' => 'Kebutuhan administrasi umum revisi',
        ]);

        $updateResponse->assertRedirect(route('belanja.show', $belanja->id));
        $belanja->refresh();
        $this->assertSame('Pembelian Kertas HVS F4 75gsm (Revisi)', $belanja->uraian);
        $this->assertEquals(300000, $belanja->total_nilai);
        $this->assertSame('Toko Alat Tulis Sejahtera Baru', $belanja->penerima);

        // 4. Delete (while in draft)
        $deleteResponse = $this->actingAs($this->operator)->delete(route('belanja.destroy', $belanja->id));
        $deleteResponse->assertRedirect(route('belanja.index'));
        $this->assertDatabaseMissing('belanja', ['id' => $belanja->id]);
    }

    public function test_can_upload_and_delete_dokumen_bukti(): void
    {
        $belanja = Belanja::where('status_verifikasi', StatusVerifikasi::DRAFT->value)->firstOrFail();

        // 1. Upload Nota
        $fileNota = UploadedFile::fake()->create('nota_transaksi.pdf', 500, 'application/pdf');
        $uploadResponse = $this->actingAs($this->operator)->post(route('belanja.dokumen.store', $belanja->id), [
            'jenis_dokumen' => JenisDokumen::NOTA->value,
            'file' => $fileNota,
            'keterangan' => 'Nota resmi toko',
        ]);
        $uploadResponse->assertSessionHas('success');

        $dokumen = DokumenBukti::where('belanja_id', $belanja->id)->firstOrFail();
        $this->assertSame(JenisDokumen::NOTA, $dokumen->jenis_dokumen);
        Storage::disk('public')->assertExists($dokumen->file_path);

        // Download check
        $downloadResponse = $this->actingAs($this->operator)->get(route('dokumen-bukti.download', $dokumen->id));
        $downloadResponse->assertOk();

        // 2. Upload Kwitansi
        $fileKwitansi = UploadedFile::fake()->create('kwitansi_lunas.jpg', 300, 'image/jpeg');
        $this->actingAs($this->operator)->post(route('belanja.dokumen.store', $belanja->id), [
            'jenis_dokumen' => JenisDokumen::KWITANSI->value,
            'file' => $fileKwitansi,
            'keterangan' => 'Kwitansi lunas bermaterai',
        ]);

        $belanja->refresh();
        $this->assertSame(StatusDokumen::LENGKAP, $belanja->status_dokumen);

        // 3. Delete Dokumen
        $deleteDocResponse = $this->actingAs($this->operator)->delete(route('dokumen-bukti.destroy', $dokumen->id));
        $deleteDocResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('dokumen_bukti', ['id' => $dokumen->id]);
        Storage::disk('public')->assertMissing($dokumen->file_path);
    }

    public function test_cannot_delete_belanja_once_submitted_or_verified(): void
    {
        $belanja = Belanja::firstOrFail();
        $belanja->update(['status_verifikasi' => StatusVerifikasi::DIAJUKAN->value]);

        $response = $this->actingAs($this->operator)->delete(route('belanja.destroy', $belanja->id));
        $response->assertStatus(403); // BelanjaPolicy::delete prevents non-editable status
    }
}
