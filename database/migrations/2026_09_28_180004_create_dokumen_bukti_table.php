<?php

declare(strict_types=1);

use App\Enums\JenisDokumen;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel dokumen bukti belanja — multi-file per belanja.
     * Satu belanja bisa memiliki banyak dokumen (nota, kwitansi, faktur, kontrak, lainnya).
     */
    public function up(): void
    {
        Schema::create('dokumen_bukti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('belanja_id')->constrained('belanja')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('jenis_dokumen')->default(JenisDokumen::LAINNYA->value)->index();
            $table->string('nama_dokumen');
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size');
            $table->string('file_type');
            $table->string('nomor_dokumen')->nullable()->index();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index(['belanja_id', 'jenis_dokumen']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_bukti');
    }
};
