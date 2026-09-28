<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel kegiatan RAP — level 2 hierarki.
     * Nama tabel `kegiatan_rap` untuk menghindari bentrok dengan tabel `kegiatan` lama.
     */
    public function up(): void
    {
        Schema::create('kegiatan_rap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('program')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('kode');
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->timestamps();

            $table->unique(['program_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_rap');
    }
};
