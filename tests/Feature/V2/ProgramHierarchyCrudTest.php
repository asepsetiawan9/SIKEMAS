<?php

declare(strict_types=1);

namespace Tests\Feature\V2;

use App\Enums\UserRole;
use App\Models\KegiatanRap;
use App\Models\Program;
use App\Models\SubKegiatan;
use App\Models\User;
use Database\Seeders\BelanjaV2Seeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramHierarchyCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(BelanjaV2Seeder::class);

        $this->operator = User::where('email', 'operator1@simpelkan.test')->firstOrFail();
    }

    public function test_can_view_program_hierarchy_index(): void
    {
        $response = $this->actingAs($this->operator)->get(route('program.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Program/Index')
            ->has('tree')
            ->has('filters')
        );
    }

    public function test_can_get_dropdown_options(): void
    {
        $response = $this->actingAs($this->operator)->get(route('program.options', ['tahun' => 2026]));
        $response->assertOk();
        $response->assertJsonIsArray();
    }

    public function test_can_create_update_and_delete_program(): void
    {
        // 1. Create
        $response = $this->actingAs($this->operator)->post(route('program.store'), [
            'nama' => 'Program Uji Coba CRUD',
            'kode' => '9.99.01',
            'tahun_anggaran' => 2026,
            'keterangan' => 'Keterangan program uji coba',
        ]);
        $response->assertSessionHas('success');

        $program = Program::where('kode', '9.99.01')->firstOrFail();
        $this->assertSame('Program Uji Coba CRUD', $program->nama);

        // 2. Update
        $response = $this->actingAs($this->operator)->put(route('program.update', $program->id), [
            'nama' => 'Program Uji Coba Diperbarui',
            'kode' => '9.99.01',
            'tahun_anggaran' => 2026,
            'keterangan' => 'Keterangan update',
        ]);
        $response->assertSessionHas('success');
        $program->refresh();
        $this->assertSame('Program Uji Coba Diperbarui', $program->nama);

        // 3. Delete
        $response = $this->actingAs($this->operator)->delete(route('program.destroy', $program->id));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('program', ['id' => $program->id]);
    }

    public function test_can_create_update_and_delete_kegiatan(): void
    {
        $program = Program::firstOrFail();

        // 1. Create Kegiatan
        $response = $this->actingAs($this->operator)->post(route('program.kegiatan.store'), [
            'program_id' => $program->id,
            'nama' => 'Kegiatan Uji Coba',
            'kode' => '9.99.01.2.01',
            'keterangan' => 'Deskripsi kegiatan uji coba',
        ]);
        $response->assertSessionHas('success');

        $kegiatan = KegiatanRap::where('kode', '9.99.01.2.01')->firstOrFail();
        $this->assertSame('Kegiatan Uji Coba', $kegiatan->nama);

        // 2. Update Kegiatan
        $response = $this->actingAs($this->operator)->put(route('program.kegiatan.update', $kegiatan->id), [
            'program_id' => $program->id,
            'nama' => 'Kegiatan Uji Coba Diperbarui',
            'kode' => '9.99.01.2.01',
            'keterangan' => 'Deskripsi update',
        ]);
        $response->assertSessionHas('success');
        $kegiatan->refresh();
        $this->assertSame('Kegiatan Uji Coba Diperbarui', $kegiatan->nama);

        // 3. Delete Kegiatan
        $response = $this->actingAs($this->operator)->delete(route('program.kegiatan.destroy', $kegiatan->id));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('kegiatan_rap', ['id' => $kegiatan->id]);
    }

    public function test_can_create_update_and_delete_sub_kegiatan(): void
    {
        $kegiatan = KegiatanRap::firstOrFail();

        // 1. Create Sub Kegiatan
        $response = $this->actingAs($this->operator)->post(route('program.sub-kegiatan.store'), [
            'kegiatan_rap_id' => $kegiatan->id,
            'nama' => 'Sub Kegiatan Uji Coba',
            'kode' => '9.99.01.2.01.0001',
            'keterangan' => 'Deskripsi sub kegiatan uji coba',
        ]);
        $response->assertSessionHas('success');

        $sub = SubKegiatan::where('kode', '9.99.01.2.01.0001')->firstOrFail();
        $this->assertSame('Sub Kegiatan Uji Coba', $sub->nama);

        // 2. Update Sub Kegiatan
        $response = $this->actingAs($this->operator)->put(route('program.sub-kegiatan.update', $sub->id), [
            'kegiatan_rap_id' => $kegiatan->id,
            'nama' => 'Sub Kegiatan Uji Coba Diperbarui',
            'kode' => '9.99.01.2.01.0001',
            'keterangan' => 'Deskripsi sub update',
        ]);
        $response->assertSessionHas('success');
        $sub->refresh();
        $this->assertSame('Sub Kegiatan Uji Coba Diperbarui', $sub->nama);

        // 3. Delete Sub Kegiatan
        $response = $this->actingAs($this->operator)->delete(route('program.sub-kegiatan.destroy', $sub->id));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('sub_kegiatan', ['id' => $sub->id]);
    }
}
