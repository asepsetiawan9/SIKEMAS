<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SeksiType;
use App\Models\Kegiatan;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanController extends Controller
{
    public function __construct(
        private readonly LaporanService $laporanService
    ) {}

    /**
     * Display report dashboard with filters and data preview.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('laporan.view'), 403, 'Akses tidak diizinkan untuk melihat laporan.');

        $filters = $request->only([
            'jenis_laporan',
            'tanggal_mulai',
            'tanggal_selesai',
            'kegiatan_id',
            'seksi',
            'kondisi',
            'lokasi',
            'tahun_anggaran',
        ]);

        $reportData = $this->laporanService->getLaporanData($filters);

        $kegiatanOptions = Kegiatan::select('id', 'nama', 'kode_rekening')
            ->orderBy('nama')
            ->get()
            ->map(fn ($k) => ['id' => $k->id, 'nama' => "{$k->kode_rekening} - {$k->nama}"]);

        $seksiOptions = array_map(fn ($case) => [
            'value' => $case->value,
            'label' => ucfirst($case->value),
        ], SeksiType::cases());

        return Inertia::render('Laporan/Index', [
            'reportData' => $reportData,
            'kegiatanOptions' => $kegiatanOptions,
            'seksiOptions' => $seksiOptions,
        ]);
    }

    /**
     * Export report to PDF.
     */
    public function exportPdf(Request $request): HttpResponse
    {
        abort_unless($request->user()->can('laporan.export'), 403, 'Akses tidak diizinkan untuk mengekspor laporan.');

        $filters = $request->all();
        $pdf = $this->laporanService->exportPdf($filters);

        $jenis = $filters['jenis_laporan'] ?? 'keuangan';
        $timestamp = now()->format('Ymd_His');
        $fileName = "Laporan_{$jenis}_{$timestamp}.pdf";

        return $pdf->download($fileName);
    }

    /**
     * Export report to Excel.
     */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('laporan.export'), 403, 'Akses tidak diizinkan untuk mengekspor laporan.');

        return $this->laporanService->exportExcel($request->all());
    }
}
