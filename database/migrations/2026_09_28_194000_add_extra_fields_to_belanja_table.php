<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('belanja', function (Blueprint $table) {
            if (! Schema::hasColumn('belanja', 'penerima')) {
                $table->string('penerima')->nullable()->after('tanggal_belanja');
            }
            if (! Schema::hasColumn('belanja', 'nomor_bukti_manual')) {
                $table->string('nomor_bukti_manual')->nullable()->after('penerima');
            }
            if (! Schema::hasColumn('belanja', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('nomor_bukti_manual');
            }
        });
    }

    public function down(): void
    {
        Schema::table('belanja', function (Blueprint $table) {
            $table->dropColumn(['penerima', 'nomor_bukti_manual', 'keterangan']);
        });
    }
};
