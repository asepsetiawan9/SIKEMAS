<?php

namespace App\Console\Commands;

use App\Models\LogAktivitas;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AuditSummaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sikemas:audit-summary {--days=14 : Jumlah hari ke belakang untuk rekapitulasi audit (default 14 hari)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rekapitulasi log aktivitas SIKEMAS selama 14 hari pertama go-live untuk mendeteksi anomali operasional';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $this->info("==================================================================");
        $this->info("  AUDIT MONITORING LOG AKTIVITAS SIKEMAS (Periode {$days} Hari Terakhir)");
        $this->info("==================================================================");

        $totalLogs = LogAktivitas::where('created_at', '>=', $startDate)->count();

        if ($totalLogs === 0) {
            $this->warn("Belum ada log aktivitas tercatat dalam {$days} hari terakhir.");
            return Command::SUCCESS;
        }

        $this->line("Total Catatan Aktivitas: <fg=green;options=bold>{$totalLogs}</>");
        $this->newLine();

        // 1. Distribusi per Aksi
        $this->info("--- Distribusi per Kategori Aksi ---");
        $actionCounts = LogAktivitas::where('created_at', '>=', $startDate)
            ->selectRaw('aksi, count(*) as total')
            ->groupBy('aksi')
            ->orderByDesc('total')
            ->get();

        $actionRows = $actionCounts->map(fn($item) => [$item->aksi, $item->total])->toArray();
        $this->table(['Nama Aksi', 'Frekuensi'], $actionRows);

        // 2. Deteksi Potensi Anomali
        $this->newLine();
        $this->info("--- Analisis Potensi Anomali & Critical Events ---");

        // Anomali: SPJ Ditolak
        $rejectedCount = LogAktivitas::where('created_at', '>=', $startDate)
            ->where('aksi', 'like', '%tolak%')
            ->count();
        if ($rejectedCount > 0) {
            $this->warn("⚠️  Terdapat {$rejectedCount} penolakan verifikasi SPJ. Perlu evaluasi pemahaman kelengkapan berkas Kasi.");
        } else {
            $this->line("✅ Tidak ada penolakan SPJ abnormal.");
        }

        // Aset Rusak Berat
        $rusakBeratCount = LogAktivitas::where('created_at', '>=', $startDate)
            ->where('aksi', 'like', '%rusak_berat%')
            ->orWhere(function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate)
                    ->where('keterangan', 'like', '%rusak_berat%');
            })
            ->count();
        if ($rusakBeratCount > 0) {
            $this->warn("⚠️  Terdapat {$rusakBeratCount} mutasi kondisi aset ke 'rusak_berat'. Segera tindak lanjuti usulan penghapusan BMD.");
        } else {
            $this->line("✅ Tidak ada pelaporan aset rusak berat darurat.");
        }

        // 3. 10 Aktivitas Terkini
        $this->newLine();
        $this->info("--- 10 Aktivitas Terakhir ---");
        $latestLogs = LogAktivitas::with('user')
            ->where('created_at', '>=', $startDate)
            ->latest()
            ->limit(10)
            ->get();

        $latestRows = $latestLogs->map(function ($log) {
            return [
                $log->created_at->format('Y-m-d H:i:s'),
                $log->user?->name ?? 'SYSTEM',
                $log->aksi,
                $log->tabel_terkait ?? '-',
                $log->ip_address ?? '-',
            ];
        })->toArray();

        $this->table(['Waktu', 'Pengguna', 'Aksi', 'Tabel', 'IP Address'], $latestRows);

        return Command::SUCCESS;
    }
}
