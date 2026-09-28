<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel riwayat proses — audit trail per belanja.
     * Mencatat setiap aksi: input, upload, verifikasi, tolak, perbaikan, dll.
     */
    public function up(): void
    {
        Schema::create('riwayat_proses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('belanja_id')->constrained('belanja')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('aksi')->index();
            $table->text('keterangan');
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index(['belanja_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_proses');
    }
};
