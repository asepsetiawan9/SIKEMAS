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

        // If tab is 'arsip' or role is camat, render the global Arsip page
        if ($tab === 'arsip' || $userRole === UserRole::CAMAT->value) {
            $spjs = $this->spjService->getList(15, $filters);

            return Inertia::render('Spj/Arsip', [
                'spjs' => $spjs,
                'filters' => $filters,
                'kegiatans' => $kegiatanList,
            ]);
        }

        // Sekmat: Antrean Verifikasi (Spj/VerifikasiIndex)
        if ($userRole === UserRole::SEKMAT->value) {
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

        // Staf Keuangan: Antrean Konsolidasi (Spj/KonsolidasiIndex)
        if ($userRole === UserRole::STAF_KEUANGAN->value) {
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
        ]);
    }

    /**
     * Show form for creating a new SPJ (Kasi).
     */
    public function create(Request $request): Response|RedirectResponse
    {
        Gate::authorize('create', Spj::class);

        $user = $request->user();

        // Check BR-SPJ-10: Kasi blocked if there is any pending rejected SPJ
        $hasRejectedSpj = Spj::where('diajukan_oleh', $user->id)
            ->where('status', SpjStatus::DITOLAK)
            ->exists();

        // Get active activities belonging to Kasi
        $kegiatans = Kegiatan::where('kasi_id', $user->id)
            ->where('status', StatusKegiatan::AKTIF)
            ->get()
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
     * Store a newly created SPJ (Kasi).
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
     * Consolidate the SPJ and generate official SPJ number (Staf Keuangan).
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
     * Submit SPJ for verification to Sekmat (Staf Keuangan).
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
     * Verify or reject SPJ (Sekmat).
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
     * Upload revised evidence file for rejected SPJ (Kasi or Staf Keuangan).
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
}
