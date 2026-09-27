<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('seksi', 'kessos')
            ->orWhere('email', 'kasi.kessos@sikemas.test')
            ->update([
                'name' => 'Kasi Kesra',
                'email' => 'kasi.kesra@sikemas.test',
                'seksi' => 'kesra',
                'jabatan' => 'Kepala Seksi Kesejahteraan Rakyat',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('seksi', 'kesra')
            ->orWhere('email', 'kasi.kesra@sikemas.test')
            ->update([
                'name' => 'Kasi Kessos',
                'email' => 'kasi.kessos@sikemas.test',
                'seksi' => 'kessos',
                'jabatan' => 'Kepala Seksi Kesejahteraan Sosial',
            ]);
    }
};
