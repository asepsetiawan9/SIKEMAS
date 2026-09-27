<?php

declare(strict_types=1);

use App\Enums\StatusKegiatan;
use App\Enums\SumberDana;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->decimal('pagu', 15, 2);
            $table->year('tahun_anggaran')->index();
            $table->string('kode_rekening');
            $table->string('sumber_dana')->default(SumberDana::APBD->value);
            $table->string('status')->default(StatusKegiatan::AKTIF->value)->index();
            $table->text('deskripsi')->nullable();
            $table->date('periode_mulai')->nullable();
            $table->date('periode_selesai')->nullable();
            $table->foreignId('kasi_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kegiatan');
    }
};
