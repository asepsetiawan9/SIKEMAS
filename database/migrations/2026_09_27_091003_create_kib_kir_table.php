<?php

declare(strict_types=1);

use App\Enums\JenisKibKir;
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
        Schema::create('kib_kir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('aset')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('jenis')->default(JenisKibKir::KIB->value)->index();
            $table->string('file_pdf');
            $table->foreignId('dibuat_oleh')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kib_kir');
    }
};
