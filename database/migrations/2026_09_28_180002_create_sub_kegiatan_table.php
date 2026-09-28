<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel sub kegiatan — level 3 hierarki.
     */
    public function up(): void
    {
        Schema::create('sub_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_rap_id')->constrained('kegiatan_rap')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('kode');
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->timestamps();

            $table->unique(['kegiatan_rap_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_kegiatan');
    }
};
