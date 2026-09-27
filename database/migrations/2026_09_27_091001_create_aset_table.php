<?php

declare(strict_types=1);

use App\Enums\CaraPerolehan;
use App\Enums\KondisiAset;
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
        Schema::create('aset', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang')->unique();
            $table->string('nama');
            $table->year('tahun_perolehan')->index();
            $table->decimal('nilai', 15, 2);
            $table->string('kondisi')->default(KondisiAset::BAIK->value)->index();
            $table->string('lokasi')->index();
            $table->foreignId('penanggung_jawab')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('qr_code_path')->nullable();
            $table->string('merk_type')->nullable();
            $table->string('nomor_register')->nullable();
            $table->string('ukuran')->nullable();
            $table->string('bahan')->nullable();
            $table->string('cara_perolehan')->default(CaraPerolehan::PEMBELIAN->value);
            $table->date('tanggal_verifikasi_fisik')->nullable();
            $table->string('foto_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aset');
    }
};
