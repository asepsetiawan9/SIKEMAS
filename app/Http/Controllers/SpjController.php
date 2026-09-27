<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SpjStatus;
use App\Enums\StatusKegiatan;
use App\Enums\UserRole;
use App\Http\Requests\StoreSpjRequest;
use App\Http\Requests\VerifikasiSpjRequest;
use App\Models\Kegiatan;
use App\Models\LogAktivitas;
use App\Models\Pengaturan;
use App\Models\Spj;
use App\Repositories\SpjRepository;
use App\Services\SpjService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SpjController extends Controller
{
    public function __construct(
        protected SpjService $spjService,
        protected SpjRepository $spjRepository
    ) {}

    /**
     * Display a listing of SPJ.
     * Directs to role-appropriate index or global archive.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Spj::class);

        $user = $request->user();
        $userRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        $tab = $request->query('tab', 'default');

        // Filter parameters
        $filters = [
            'status' => $request->query('status'),
            'kegiatan_id' => $request->query('kegiatan_id'),
            'bulan' => $request->query('bulan'),
            'tahun' => $request->query('tahun'),
            'search' => $request->query('search'),
        ];

        // Kasi can only see own submissions in archive/list
        if ($userRole === UserRole::KASI->value) {
            $filters['kasi_id'] = $user->id;
        }

        // Available kegiatan for filter dropdown
        $kegiatanList = Kegiatan::select('id', 'nama', 'pagu')->get();

        // If tab is 'arsip', role is camat (when tab is default/arsip), or role is super_admin (not requesting specific tab)
        if ($tab === 'arsip' || ($userRole === UserRole::CAMAT->value && ! in_array($tab, ['verifikasi', 'konsolidasi'], true)) || ($userRole === UserRole::SUPER_ADMIN->value && ! in_array($tab, ['verifikasi', 'konsolidasi'], true))) {
            $spjs = $this->spjService->getList(15, $filters);

            return Inertia::render('Spj/Arsip', [
                'spjs' => $spjs,
                'filters' => $filters,
                'kegiatans' => $kegiatanList,
                'canCreateSpj' => $userRole === UserRole::KASI->value || $userRole === UserRole::SUPER_ADMIN->value,
            ]);
        }

        // Sekmat, Camat, or Super Admin with tab=verifikasi: Antrean Verifikasi (Spj/VerifikasiIndex)
        if ($userRole === UserRole::SEKMAT->value || (($userRole === UserRole::CAMAT->value || $userRole === UserRole::SUPER_ADMIN->value) && $tab === 'verifikasi')) {
            $antrean = Spj::with(['kegiatan.kasi', 'diajukanOleh', 'dikonsolidasiOleh'])
                ->where('status', SpjStatus::DIAJUKAN_VERIFIKASI)
                ->oldest('tanggal_konsolidasi')
                ->get();

            $stats = [
                'menunggu' => $antrean->count(),
                'diverifikasi_bulan_ini' => Spj::where('status', SpjStatus::DIVERIFIKASI)
                    ->whereMonth('tanggal_verifikasi', now()->month)
                    ->whereYear('tanggal_verifikasi', now()->year)
                    ->count(),
                'ditolak_bulan_ini' => Spj::where('status', SpjStatus::DITOLAK)
                    ->whereMonth('tanggal_verifikasi', now()->month)
                    ->whereYear('tanggal_verifikasi', now()->year)
                    ->count(),
            ];

            return Inertia::render('Spj/VerifikasiIndex', [
                'antrean' => $antrean,
                'stats' => $stats,
            ]);
        }

        // Staf Keuangan, Camat, or Super Admin with tab=konsolidasi: Antrean Konsolidasi (Spj/KonsolidasiIndex)
        if ($userRole === UserRole::STAF_KEUANGAN->value || (($userRole === UserRole::CAMAT->value || $userRole === UserRole::SUPER_ADMIN->value) && $tab === 'konsolidasi')) {
            $pengajuanMasuk = Spj::with(['kegiatan.kasi', 'diajukanOleh'])
                ->where('status', SpjStatus::DIAJUKAN_KASI)
                ->oldest('tanggal_pengajuan')
                ->get();

            $perluRevisi = Spj::with(['kegiatan.kasi', 'diajukanOleh', 'diverifikasiOleh'])
                ->where('status', SpjStatus::DITOLAK)
                ->latest('tanggal_verifikasi')
                ->get();

            $dikonsolidasi = Spj::with(['kegiatan.kasi', 'diajukanOleh', 'dikonsolidasiOleh'])
                ->where('status', SpjStatus::DIKONSOLIDASI)
                ->latest('tanggal_konsolidasi')
                ->get();

            return Inertia::render('Spj/KonsolidasiIndex', [
                'pengajuanMasuk' => $pengajuanMasuk,
                'perluRevisi' => $perluRevisi,
                'dikonsolidasi' => $dikonsolidasi,
            ]);
        }

        // Kasi: Daftar SPJ Milik Kasi (Spj/Arsip dengan context Kasi)
        $spjs = $this->spjService->getList(15, $filters);
        $hasRejected = Spj::where('diajukan_oleh', $user->id)
            ->where('status', SpjStatus::DITOLAK)
            ->exists();

        return Inertia::render('Spj/Arsip', [
            'spjs' => $spjs,
            'filters' => $filters,
            'kegiatans' => $kegiatanList,
            'hasRejectedSpj' => $hasRejected,
            'canCreateSpj' => true,
        ]);
    }

    /**
     * Show form for creating a new SPJ (Kasi or Super Admin).
     */
    public function create(Request $request): Response|RedirectResponse
    {
        Gate::authorize('create', Spj::class);

        $user = $request->user();
        $userRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        // Check BR-SPJ-10: Kasi blocked if there is any pending rejected SPJ
        $hasRejectedSpj = false;
        if ($userRole === UserRole::KASI->value) {
            $hasRejectedSpj = Spj::where('diajukan_oleh', $user->id)
                ->where('status', SpjStatus::DITOLAK)
                ->exists();
        }

        // Get active activities: filter by Kasi ID if Kasi, or all active if Super Admin
        $kegiatansQuery = Kegiatan::where('status', StatusKegiatan::AKTIF);
        if ($userRole === UserRole::KASI->value) {
            $kegiatansQuery->where('kasi_id', $user->id);
        }

        $kegiatans = $kegiatansQuery->get()
            ->map(fn (Kegiatan $k) => [
                'id' => $k->id,
                'nama' => $k->nama,
                'pagu' => (float) $k->pagu,
                'sisa_pagu' => (float) $k->sisa_pagu,
                'total_realisasi' => (float) $k->total_realisasi,
                'kode_rekening' => $k->kode_rekening,
                'persentase_realisasi' => (float) $k->pagu > 0
                    ? round(((float) $k->total_realisasi / (float) $k->pagu) * 100, 1)
                    : 0,
            ]);

        $tahunAktif = (int) (Pengaturan::where('key', 'tahun_anggaran_aktif')->value('value') ?? now()->format('Y'));

        return Inertia::render('Spj/FormPengajuan', [
            'kegiatans' => $kegiatans,
            'hasRejectedSpj' => $hasRejectedSpj,
            'tahunAktif' => $tahunAktif,
        ]);
    }

    /**
     * Store a newly created SPJ (Kasi or Super Admin).
     */
    public function store(StoreSpjRequest $request): RedirectResponse
    {
        try {
            $this->spjService->createPengajuan(
                data: $request->validated(),
                kasi: $request->user(),
                fileBukti: $request->file('file_bukti')
            );

            return redirect()->route('spj.index')->with('success', 'Pengajuan SPJ berhasil dikirim ke Bagian Keuangan.');
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified SPJ detail.
     */
    public function show(Spj $spj): Response
    {
        Gate::authorize('view', $spj);

        $spj->load(['kegiatan.kasi', 'diajukanOleh', 'dikonsolidasiOleh', 'diverifikasiOleh']);

        // Fetch activity logs for this SPJ record
        $logs = LogAktivitas::with('user')
            ->where('tabel_terkait', 'spj')
            ->where('record_id', $spj->id)
            ->latest('created_at')
            ->get();

        return Inertia::render('Spj/Show', [
            'spj' => $spj,
            'logs' => $logs,
            'sisa_pagu_kegiatan' => $spj->kegiatan ? (float) $spj->kegiatan->sisa_pagu : 0,
        ]);
    }

    /**
     * Consolidate the SPJ and generate official SPJ number (Staf Keuangan or Super Admin).
     */
    public function konsolidasi(Request $request, Spj $spj): RedirectResponse
    {
        Gate::authorize('konsolidasi', $spj);

        try {
            $updatedSpj = $this->spjService->konsolidasi(
                $spj,
                $request->user(),
                $request->input('nomor_spj')
            );

            return back()->with('success', "SPJ berhasil dikonsolidasi dengan nomor resmi: {$updatedSpj->nomor_spj}");
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Submit SPJ for verification to Sekmat (Staf Keuangan or Super Admin).
     */
    public function ajukanVerifikasi(Request $request, Spj $spj): RedirectResponse
    {
        Gate::authorize('ajukanVerifikasi', $spj);

        try {
            $this->spjService->ajukanVerifikasi($spj, $request->user());

            return back()->with('success', 'SPJ berhasil diajukan untuk verifikasi Sekmat.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Verify or reject SPJ (Sekmat or Super Admin).
     */
    public function verifikasi(VerifikasiSpjRequest $request, Spj $spj): RedirectResponse
    {
        Gate::authorize('verifikasi', $spj);

        try {
            $approved = (bool) $request->boolean('approved');
            $this->spjService->verifikasi(
                spj: $spj,
                sekmat: $request->user(),
                approve: $approved,
                catatan: $request->input('catatan')
            );

            $statusMessage = $approved
                ? 'SPJ telah disetujui dan diverifikasi secara resmi.'
                : 'SPJ telah ditolak dan dikembalikan ke Staf Keuangan untuk revisi.';

            return back()->with('success', $statusMessage);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Upload revised evidence file for rejected SPJ (Kasi, Staf Keuangan or Super Admin).
     */
    public function revisiBukti(Request $request, Spj $spj): RedirectResponse
    {
        $request->validate([
            'file_bukti' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'file_bukti.required' => 'Berkas bukti fisik revisi wajib diunggah.',
            'file_bukti.file' => 'Berkas bukti harus berupa file dokumen valid.',
            'file_bukti.mimes' => 'Format file yang diperbolehkan: PDF, JPG, JPEG, PNG.',
            'file_bukti.max' => 'Ukuran file bukti maksimal 5MB.',
        ]);

        try {
            $this->spjService->revisiBukti(
                spj: $spj,
                user: $request->user(),
                fileBukti: $request->file('file_bukti'),
                catatan: $request->input('catatan')
            );

            return back()->with('success', 'Dokumen bukti revisi berhasil diunggah.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Preview or stream the SPJ evidence file securely.
     */
    public function previewBukti(Spj $spj): \Symfony\Component\HttpFoundation\Response
    {
        Gate::authorize('view', $spj);

        if (! $spj->file_bukti) {
            abort(404, 'Dokumen bukti pertanggungjawaban fisik belum dilampirkan.');
        }

        $filePath = $spj->file_bukti;
        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        if ($disk->exists($filePath)) {
            return $disk->response($filePath);
        }

        $fullPath = storage_path('app/public/' . ltrim($filePath, '/'));
        if (file_exists($fullPath)) {
            return response()->file($fullPath);
        }

        // Case-insensitive fallback
        $dir = dirname($fullPath);
        $base = basename($fullPath);
        if (is_dir($dir)) {
            $entries = scandir($dir) ?: [];
            foreach ($entries as $entry) {
                if (strcasecmp($entry, $base) === 0) {
                    return response()->file($dir . DIRECTORY_SEPARATOR . $entry);
                }
            }
        }

        abort(404, 'Berkas fisik bukti dokumen tidak ditemukan pada penyimpanan server.');
    }

    /**
     * Stream public storage files as fallback if direct Nginx symlink is bypassed.
     */
    public function streamStorageFile(string $folder, string $filename): \Symfony\Component\HttpFoundation\Response
    {
        // Sanitize folder and filename against directory traversal
        $folder = preg_replace('/[^a-zA-Z0-9_\-]/', '', $folder) ?: 'bukti_spj';
        $filename = basename($filename);

        $path = $folder . '/' . $filename;
        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        if ($disk->exists($path)) {
            return $disk->response($path);
        }

        $fullPath = storage_path('app/public/' . $path);
        if (file_exists($fullPath)) {
            return response()->file($fullPath);
        }

        // Case-insensitive fallback lookup
        $dir = storage_path('app/public/' . $folder);
        if (is_dir($dir)) {
            $entries = scandir($dir) ?: [];
            foreach ($entries as $entry) {
                if (strcasecmp($entry, $filename) === 0) {
                    return response()->file($dir . DIRECTORY_SEPARATOR . $entry);
                }
            }
        }

        abort(404, 'Berkas tidak ditemukan pada server.');
    }
}
