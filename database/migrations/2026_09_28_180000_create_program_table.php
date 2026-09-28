<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel program anggaran.
     * Level 1 hierarki: Program → Kegiatan → Sub Kegiatan → Belanja → Bukti
     */
    public function up(): void
    {
        Schema::create('program', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->year('tahun_anggaran')->index();
            $table->text('deskripsi')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index(['tahun_anggaran', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program');
    }
};
