<?php

declare(strict_types=1);

use App\Http\Controllers\AsetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SpjController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard or login
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    // === Dashboard ===
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // === Profile ===
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // === SPJ Digital ===
    Route::prefix('spj')->name('spj.')->group(function () {
        Route::get('/', [SpjController::class, 'index'])->name('index');
        Route::get('/create', [SpjController::class, 'create'])->name('create');
        Route::post('/', [SpjController::class, 'store'])->name('store');
        Route::get('/{spj}', [SpjController::class, 'show'])->name('show');
        Route::get('/{spj}/bukti', [SpjController::class, 'previewBukti'])->name('bukti');
        Route::put('/{spj}/konsolidasi', [SpjController::class, 'konsolidasi'])->name('konsolidasi');
        Route::put('/{spj}/ajukan-verifikasi', [SpjController::class, 'ajukanVerifikasi'])->name('ajukan-verifikasi');
        Route::put('/{spj}/verifikasi', [SpjController::class, 'verifikasi'])->name('verifikasi');
        Route::post('/{spj}/revisi-bukti', [SpjController::class, 'revisiBukti'])->name('revisi-bukti');
    });

    // Fallback direct storage stream for uploaded media
    Route::get('/storage/{folder}/{filename}', [SpjController::class, 'streamStorageFile'])
        ->where('folder', 'bukti_spj|spj_dokumen|aset_foto')
        ->where('filename', '.*')
        ->name('storage.stream');

    // === Kegiatan Anggaran ===
    Route::prefix('kegiatan')->name('kegiatan.')->group(function () {
        Route::get('/', [KegiatanController::class, 'index'])->name('index');
        Route::post('/', [KegiatanController::class, 'store'])->name('store');
        Route::put('/{kegiatan}', [KegiatanController::class, 'update'])->name('update');
        Route::delete('/{kegiatan}', [KegiatanController::class, 'destroy'])->name('destroy');
    });

    // === Aset / BMD ===
    Route::prefix('aset')->name('aset.')->group(function () {
        Route::get('/', [AsetController::class, 'index'])->name('index');
        Route::get('/create', [AsetController::class, 'create'])->name('create');
        Route::post('/', [AsetController::class, 'store'])->name('store');
        Route::get('/{aset}', [AsetController::class, 'show'])->name('show');
        Route::get('/{aset}/edit', [AsetController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{aset}', [AsetController::class, 'update'])->name('update');
        Route::delete('/{aset}', [AsetController::class, 'destroy'])->name('destroy');
        Route::get('/{aset}/qr', [AsetController::class, 'downloadQr'])->name('download-qr');
        Route::get('/{aset}/kib-kir', [AsetController::class, 'generateKibKir'])->name('generate-kibkir');
        Route::get('/{aset}/kib-kir/download', [AsetController::class, 'downloadKibKir'])->name('download-kibkir');
    });


    // === Laporan ===
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [LaporanController::class, 'index'])->name('index');
        Route::get('/export/pdf', [LaporanController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/export/excel', [LaporanController::class, 'exportExcel'])->name('export-excel');
    });

    // === Notifikasi ===
    Route::prefix('notifikasi')->name('notifikasi.')->group(function () {
        Route::get('/', [NotifikasiController::class, 'index'])->name('index');
        Route::get('/recent', [NotifikasiController::class, 'recent'])->name('recent');
        Route::post('/{notifikasi}/read', [NotifikasiController::class, 'markAsRead'])->name('mark-read');
        Route::post('/read-all', [NotifikasiController::class, 'markAllAsRead'])->name('mark-all-read');
    });
});

require __DIR__.'/auth.php';
