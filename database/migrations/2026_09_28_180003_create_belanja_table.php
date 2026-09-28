<?php

declare(strict_types=1);

use App\Enums\JenisBelanja;
use App\Enums\StatusDokumen;
use App\Enums\StatusVerifikasi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel belanja — CORE ENTITY v2.0 (pengganti tabel `spj` lama).
     * Setiap belanja bisa memiliki banyak dokumen bukti (one-to-many ke dokumen_bukti).
     */
    public function up(): void
    {
        Schema::create('belanja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_kegiatan_id')->constrained('sub_kegiatan')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('jenis_belanja')->default(JenisBelanja::LAINNYA->value)->index();
            $table->string('uraian');
            $table->text('spesifikasi')->nullable();
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('volume', 10, 2)->default(1);
            $table->string('satuan')->nullable();
            $table->decimal('total_nilai', 15, 2);
            $table->date('tanggal_belanja')->index();

            // Status dokumen (otomatis dihitung)
            $table->string('status_dokumen')->default(StatusDokumen::BELUM_LENGKAP->value)->index();

            // Status verifikasi (state machine)
            $table->string('status_verifikasi')->default(StatusVerifikasi::DRAFT->value)->index();

            // Catatan verifikasi / penolakan
            $table->text('catatan_sekmat')->nullable();
            $table->text('catatan_camat')->nullable();

            // Timestamps verifikasi
            $table->dateTime('diajukan_pada')->nullable();
            $table->dateTime('diverifikasi_sekmat_pada')->nullable();
            $table->dateTime('disetujui_camat_pada')->nullable();

            // Relasi user
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();

            $table->timestamps();

            // Composite indexes untuk pencarian cepat
            $table->index(['status_verifikasi', 'status_dokumen']);
            $table->index(['tanggal_belanja', 'jenis_belanja']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('belanja');
    }
};
