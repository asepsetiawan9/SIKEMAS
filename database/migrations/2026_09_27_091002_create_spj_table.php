<?php

declare(strict_types=1);

use App\Enums\SpjStatus;
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
        Schema::create('spj', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('nominal', 15, 2);
            $table->string('status')->default(SpjStatus::DRAFT->value)->index();
            $table->string('file_bukti');
            $table->string('nomor_spj')->nullable()->unique();
            $table->date('tanggal_pengajuan');
            $table->date('tanggal_konsolidasi')->nullable();
            $table->date('tanggal_verifikasi')->nullable();
            $table->unsignedTinyInteger('periode_bulan')->index();
            $table->year('periode_tahun')->index();
            $table->string('jenis_belanja')->nullable();
            $table->foreignId('diajukan_oleh')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('dikonsolidasi_oleh')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spj');
    }
};
