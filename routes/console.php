<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal Backup Database Harian jam 02:00 WIB (Kecamatan Caringin)
Schedule::command('sikemas:backup-database')
    ->dailyAt('02:00')
    ->timezone('Asia/Jakarta')
    ->appendOutputTo(storage_path('logs/backup.log'));
