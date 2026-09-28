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

    // === V2: Program & Hierarki RAP ===
    Route::prefix('program')->name('program.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ProgramController::class, 'index'])->name('index');
        Route::get('/options', [\App\Http\Controllers\ProgramController::class, 'options'])->name('options');
        Route::post('/', [\App\Http\Controllers\ProgramController::class, 'storeProgram'])->name('store');
        Route::put('/{program}', [\App\Http\Controllers\ProgramController::class, 'updateProgram'])->name('update');
        Route::delete('/{program}', [\App\Http\Controllers\ProgramController::class, 'destroyProgram'])->name('destroy');

        Route::post('/kegiatan', [\App\Http\Controllers\ProgramController::class, 'storeKegiatan'])->name('kegiatan.store');
        Route::put('/kegiatan/{kegiatan}', [\App\Http\Controllers\ProgramController::class, 'updateKegiatan'])->name('kegiatan.update');
        Route::delete('/kegiatan/{kegiatan}', [\App\Http\Controllers\ProgramController::class, 'destroyKegiatan'])->name('kegiatan.destroy');

        Route::post('/sub-kegiatan', [\App\Http\Controllers\ProgramController::class, 'storeSubKegiatan'])->name('sub-kegiatan.store');
        Route::put('/sub-kegiatan/{subKegiatan}', [\App\Http\Controllers\ProgramController::class, 'updateSubKegiatan'])->name('sub-kegiatan.update');
        Route::delete('/sub-kegiatan/{subKegiatan}', [\App\Http\Controllers\ProgramController::class, 'destroySubKegiatan'])->name('sub-kegiatan.destroy');
    });

    // === V2: Belanja & Bukti Belanja (Core SPJ) ===
    Route::prefix('belanja')->name('belanja.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BelanjaController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\BelanjaController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\BelanjaController::class, 'store'])->name('store');
        Route::get('/{belanja}', [\App\Http\Controllers\BelanjaController::class, 'show'])->name('show');
        Route::get('/{belanja}/edit', [\App\Http\Controllers\BelanjaController::class, 'edit'])->name('edit');
        Route::put('/{belanja}', [\App\Http\Controllers\BelanjaController::class, 'update'])->name('update');
        Route::delete('/{belanja}', [\App\Http\Controllers\BelanjaController::class, 'destroy'])->name('destroy');
        Route::post('/{belanja}/ajukan', [\App\Http\Controllers\BelanjaController::class, 'ajukan'])->name('ajukan');

        // Multi-file Dokumen Bukti
        Route::post('/{belanja}/dokumen', [\App\Http\Controllers\DokumenBuktiController::class, 'store'])->name('dokumen.store');
    });

    // Dokumen Bukti delete & download
    Route::delete('/dokumen-bukti/{dokumen}', [\App\Http\Controllers\DokumenBuktiController::class, 'destroy'])->name('dokumen-bukti.destroy');
    Route::get('/dokumen-bukti/{dokumen}/download', [\App\Http\Controllers\DokumenBuktiController::class, 'download'])->name('dokumen-bukti.download');

    // === V2: Verifikasi Alur RAP/SPJ ===
    Route::prefix('verifikasi')->name('verifikasi.')->group(function () {
        // Sekmat antrean & actions
        Route::get('/sekmat', [\App\Http\Controllers\VerifikasiController::class, 'sekmatIndex'])->name('sekmat.index');
        Route::post('/sekmat/{belanja}/setujui', [\App\Http\Controllers\VerifikasiController::class, 'verifikasiSekmat'])->name('sekmat.setujui');
        Route::post('/sekmat/{belanja}/kembalikan', [\App\Http\Controllers\VerifikasiController::class, 'kembalikanSekmat'])->name('sekmat.kembalikan');

        // Camat antrean & actions
        Route::get('/camat', [\App\Http\Controllers\VerifikasiController::class, 'camatIndex'])->name('camat.index');
        Route::post('/camat/{belanja}/setujui', [\App\Http\Controllers\VerifikasiController::class, 'setujuiCamat'])->name('camat.setujui');
        Route::post('/camat/{belanja}/kembalikan', [\App\Http\Controllers\VerifikasiController::class, 'kembalikanCamat'])->name('camat.kembalikan');
    });

    // === Aset / BMD (Menu navigasi disembunyikan dari UI, route backend tetap aktif) ===
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
