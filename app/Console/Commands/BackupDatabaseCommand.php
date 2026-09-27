<?php

namespace App\Console\Commands;

use App\Models\LogAktivitas;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sikemas:backup-database {--retention=30 : Hari retensi backup (default 30 hari)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Melakukan backup database SIKEMAS ke folder backup/ dengan retensi otomatis 30 hari';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai proses backup database SIKEMAS...');

        $backupDir = base_path('backup');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $fileName = "sikemas_backup_{$timestamp}.sql";
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

        $dbDriver = config('database.default');
        $connection = config("database.connections.{$dbDriver}");

        $success = false;
        $errorOutput = null;

        if ($dbDriver === 'mysql') {
            $host = $connection['host'] ?? '127.0.0.1';
            $port = $connection['port'] ?? 3306;
            $database = $connection['database'] ?? 'sikemas';
            $username = $connection['username'] ?? 'root';
            $password = $connection['password'] ?? '';

            // Coba mysqldump binary terlebih dahulu
            $passwordOption = $password !== '' ? "-p\"{$password}\"" : '';
            $dumpCommand = sprintf(
                'mysqldump --host=%s --port=%s --user=%s %s --single-transaction --quick --skip-lock-tables %s > "%s" 2>&1',
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($username),
                $passwordOption,
                escapeshellarg($database),
                $filePath
            );

            @exec($dumpCommand, $outputLines, $returnVar);

            if ($returnVar === 0 && File::exists($filePath) && File::size($filePath) > 0) {
                $success = true;
                $this->info("Backup via mysqldump berhasil dibuat: {$fileName}");
            } else {
                $errorOutput = implode("\n", $outputLines);
                // Fallback ke native PDO dumper jika mysqldump CLI tidak tersedia
                $this->line("mysqldump binary tidak tersedia atau menghasilkan error, beralih ke PDO native dumper...");
                $success = $this->dumpViaPdo($filePath);
            }
        } elseif ($dbDriver === 'sqlite') {
            // Backup SQLite database file
            $sourceDb = $connection['database'];
            if (File::exists($sourceDb)) {
                File::copy($sourceDb, $filePath);
                $success = true;
            }
        } else {
            $success = $this->dumpViaPdo($filePath);
        }

        if (!$success || !File::exists($filePath)) {
            $this->error('Gagal membuat file backup database.');
            if ($errorOutput) {
                $this->error("Detail: {$errorOutput}");
            }
            Log::error('Backup database SIKEMAS gagal.', ['error' => $errorOutput]);
            return Command::FAILURE;
        }

        $fileSize = File::size($filePath);
        $formattedSize = round($fileSize / 1024, 2) . ' KB';
        $this->info("Backup tersimpan: {$filePath} ({$formattedSize})");

        // Pembersihan retensi 30 hari
        $retentionDays = (int) $this->option('retention');
        $deletedCount = $this->cleanOldBackups($backupDir, $retentionDays);
        if ($deletedCount > 0) {
            $this->warn("Menghapus {$deletedCount} file backup lama (> {$retentionDays} hari).");
        }

        // Catat ke LogAktivitas
        try {
            LogAktivitas::create([
                'user_id' => null,
                'aksi' => 'backup_database',
                'tabel_terkait' => 'database',
                'id_terkait' => null,
                'data_sebelum' => null,
                'data_sesudah' => [
                    'filename' => $fileName,
                    'size_bytes' => $fileSize,
                    'size_formatted' => $formattedSize,
                    'retention_days' => $retentionDays,
                    'deleted_old_backups' => $deletedCount,
                ],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'SIKEMAS Automated Backup Command',
            ]);
        } catch (Throwable $e) {
            Log::warning('Gagal mencatat log aktivitas backup: ' . $e->getMessage());
        }

        Log::info("Backup database SIKEMAS berhasil: {$fileName} ({$formattedSize})");
        return Command::SUCCESS;
    }

    /**
     * Fallback PDO Database Exporter jika mysqldump CLI tidak ada.
     */
    protected function dumpViaPdo(string $filePath): bool
    {
        try {
            $pdo = DB::connection()->getPdo();
            $driver = DB::connection()->getDriverName();

            $handle = fopen($filePath, 'w');
            if (!$handle) {
                return false;
            }

            fwrite($handle, "-- SIKEMAS Database Backup (PDO Native)\n");
            fwrite($handle, "-- Tanggal: " . Carbon::now()->toIso8601String() . "\n");
            fwrite($handle, "-- Driver: {$driver}\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            if ($driver === 'mysql') {
                $tables = DB::select('SHOW TABLES');
                $tableKey = 'Tables_in_' . DB::connection()->getDatabaseName();

                foreach ($tables as $tableObj) {
                    $tableName = $tableObj->$tableKey ?? current((array) $tableObj);

                    // Create table statement
                    $createRow = DB::select("SHOW CREATE TABLE `{$tableName}`");
                    if (!empty($createRow)) {
                        $createStatement = $createRow[0]->{'Create Table'} ?? '';
                        fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                        fwrite($handle, $createStatement . ";\n\n");
                    }

                    // Dump rows
                    $rows = DB::table($tableName)->get();
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $columns = array_map(fn($col) => "`{$col}`", array_keys($rowArray));
                        $values = array_map(function ($val) use ($pdo) {
                            if ($val === null) {
                                return 'NULL';
                            }
                            return $pdo->quote((string) $val);
                        }, array_values($rowArray));

                        $sql = sprintf(
                            "INSERT INTO `%s` (%s) VALUES (%s);\n",
                            $tableName,
                            implode(', ', $columns),
                            implode(', ', $values)
                        );
                        fwrite($handle, $sql);
                    }
                    fwrite($handle, "\n");
                }

                fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            } else {
                // SQLite generic fallback
                fwrite($handle, "-- SQLite generic dump\n");
            }

            fclose($handle);
            return true;
        } catch (Throwable $e) {
            $this->error('Error saat dump PDO: ' . $e->getMessage());
            Log::error('PDO dump failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus file backup yang lebih tua dari batas retensi.
     */
    protected function cleanOldBackups(string $backupDir, int $retentionDays): int
    {
        $deleted = 0;
        $cutoff = Carbon::now()->subDays($retentionDays)->timestamp;
        $files = File::files($backupDir);

        foreach ($files as $file) {
            if (str_starts_with($file->getFilename(), 'sikemas_backup_') && $file->getMTime() < $cutoff) {
                File::delete($file->getRealPath());
                $deleted++;
            }
        }

        return $deleted;
    }
}
