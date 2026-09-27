<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\KondisiAset;
use App\Http\Requests\Aset\StoreAsetRequest;
use App\Http\Requests\Aset\UpdateAsetRequest;
use App\Models\Aset;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Services\AsetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AsetController extends Controller
{
    public function __construct(
        protected AsetService $asetService
    ) {}

    /**
     * Display a listing of assets.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Aset::class);

        $search = $request->query('search');
        $kondisiParam = $request->query('kondisi');
        $kondisi = $kondisiParam ? KondisiAset::tryFrom((string) $kondisiParam) : null;
        $lokasi = $request->query('lokasi');
        $tahun = $request->query('tahun') ? (int) $request->query('tahun') : null;
        $overdueOnly = $request->boolean('overdue');
        $tab = $request->query('tab');
        $jenisDokumen = $request->query('jenis_dokumen');
        if ($tab === 'kir') {
            $jenisDokumen = 'kir';
        } elseif ($tab === 'kib') {
            $jenisDokumen = 'kib';
        }

        $assets = $this->asetService->getList(
            perPage: 15,
            search: $search ? (string) $search : null,
            kondisi: $kondisi,
            lokasi: $lokasi ? (string) $lokasi : null,
            tahun: $tahun,
            overdueOnly: $overdueOnly,
            jenisDokumen: $jenisDokumen
        );

        $statistics = $this->asetService->getStatistics();
        $distinctLokasi = $this->asetService->getDistinctLokasi();
        $distinctTahun = $this->asetService->getDistinctTahun();

        return Inertia::render('Aset/Index', [
            'assets' => $assets,
            'statistics' => $statistics,
            'distinctLokasi' => $distinctLokasi,
            'distinctTahun' => $distinctTahun,
            'filters' => [
                'search' => $search ?? '',
                'kondisi' => $kondisiParam ?? '',
                'lokasi' => $lokasi ?? '',
                'tahun' => $tahun ? (string) $tahun : '',
                'overdue' => $overdueOnly,
                'tab' => $tab ?? '',
                'jenis_dokumen' => $jenisDokumen ?? '',
            ],
        ]);
    }

    /**
     * Show the form for creating a new asset.
     */
    public function create(): Response
    {
        Gate::authorize('create', Aset::class);

        $users = User::select('id', 'name', 'nip', 'jabatan')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Aset/Form', [
            'isEdit' => false,
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created asset.
     */
    public function store(StoreAsetRequest $request): RedirectResponse
    {
        Gate::authorize('create', Aset::class);

        $validated = $request->validated();
        $foto = $request->file('foto');

        $aset = $this->asetService->createAset(
            data: $validated,
            foto: $foto,
            userId: (int) $request->user()->id
        );

        return redirect()
            ->route('aset.show', $aset->id)
            ->with('success', "Aset '{$aset->nama}' berhasil didaftarkan beserta QR Code & dokumen KIB/KIR.");
    }

    /**
     * Display the specified asset.
     */
    public function show(Aset $aset): Response
    {
        Gate::authorize('view', $aset);

        $aset->loadMissing(['penanggungJawab', 'kibKir', 'kibKirs.dibuatOleh']);

        // Pastikan QR code dan KIB/KIR sudah tersedia
        if (! $aset->qr_code_path || ! Storage::disk('public')->exists($aset->qr_code_path)) {
            $this->asetService->generateQrCode($aset);
            $aset->refresh();
        }

        if (! $aset->kibKir) {
            $this->asetService->generateKibKir($aset);
            $aset->refresh();
        }

        $history = LogAktivitas::with('user:id,name,nip')
            ->where('tabel_terkait', 'aset')
            ->where('record_id', $aset->id)
            ->latest('id')
            ->get();

        $users = User::select('id', 'name', 'nip', 'jabatan')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Aset/Detail', [
            'aset' => $aset,
            'history' => $history,
            'users' => $users,
        ]);
    }

    /**
     * Show the form for editing the asset.
     */
    public function edit(Aset $aset): Response
    {
        Gate::authorize('update', $aset);

        $aset->loadMissing(['penanggungJawab', 'kibKir']);

        $users = User::select('id', 'name', 'nip', 'jabatan')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Aset/Form', [
            'isEdit' => true,
            'aset' => $aset,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified asset.
     */
    public function update(UpdateAsetRequest $request, Aset $aset): RedirectResponse
    {
        Gate::authorize('update', $aset);

        $validated = $request->validated();
        $foto = $request->file('foto');

        $this->asetService->updateAset(
            aset: $aset,
            data: $validated,
            foto: $foto
        );

        return redirect()
            ->route('aset.show', $aset->id)
            ->with('success', "Informasi aset {$aset->kode_barang} berhasil diperbarui.");
    }

    /**
     * Download QR code image as PNG.
     */
    public function downloadQr(Aset $aset): BinaryFileResponse
    {
        Gate::authorize('generateQr', $aset);

        if (! $aset->qr_code_path || ! Storage::disk('public')->exists($aset->qr_code_path)) {
            $this->asetService->generateQrCode($aset);
            $aset->refresh();
        }

        $fullPath = Storage::disk('public')->path($aset->qr_code_path);
        $safeKode = preg_replace('/[^A-Za-z0-9_\-]/', '_', $aset->kode_barang) ?? 'aset_' . $aset->id;
        $fileName = "QR_{$safeKode}.png";

        return response()->download($fullPath, $fileName, [
            'Content-Type' => 'image/png',
        ]);
    }

    /**
     * Generate or regenerate KIB/KIR document.
     */
    public function generateKibKir(Request $request, Aset $aset): RedirectResponse
    {
        Gate::authorize('generateKibKir', $aset);

        $this->asetService->generateKibKir($aset, (int) $request->user()->id);

        return back()->with('success', 'Dokumen KIB/KIR berhasil di-generate ulang.');
    }

    /**
     * Download or view generated KIB/KIR PDF document.
     */
    public function downloadKibKir(Aset $aset): BinaryFileResponse
    {
        Gate::authorize('view', $aset);

        $kibKir = $aset->kibKir;
        if (! $kibKir || ! Storage::disk('public')->exists($kibKir->file_pdf)) {
            $kibKir = $this->asetService->generateKibKir($aset);
        }

        $fullPath = Storage::disk('public')->path($kibKir->file_pdf);
        $safeKode = preg_replace('/[^A-Za-z0-9_\-]/', '_', $aset->kode_barang) ?? 'aset_' . $aset->id;
        $fileName = strtolower($kibKir->jenis->value) . "_{$safeKode}.pdf";

        return response()->download($fullPath, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Attempt to delete asset. Rejected per BR-ASET-06.
     */
    public function destroy(Aset $aset): void
    {
        Gate::authorize('delete', $aset);
    }
}
