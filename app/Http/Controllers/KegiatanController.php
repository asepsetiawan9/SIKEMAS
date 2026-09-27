<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StatusKegiatan;
use App\Enums\SumberDana;
use App\Enums\UserRole;
use App\Http\Requests\Kegiatan\StoreKegiatanRequest;
use App\Http\Requests\Kegiatan\UpdateKegiatanRequest;
use App\Models\Kegiatan;
use App\Models\User;
use App\Services\KegiatanService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class KegiatanController extends Controller
{
    public function __construct(
        protected KegiatanService $kegiatanService
    ) {}

    /**
     * Display a listing of activities.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Kegiatan::class);

        $user = $request->user();
        $tahun = $request->filled('tahun') ? (int) $request->input('tahun') : null;
        $kasiId = $user->hasRole('kasi') ? (int) $user->id : ($request->filled('kasi_id') ? (int) $request->input('kasi_id') : null);
        $search = $request->filled('search') ? (string) $request->input('search') : null;
        $status = $request->filled('status') ? (string) $request->input('status') : null;

        $kegiatan = $this->kegiatanService->getList(
            perPage: 15,
            tahun: $tahun,
            kasiId: $kasiId,
            search: $search,
            status: $status
        );

        // Append computed attributes without N+1 query penalty
        $kegiatan->getCollection()->transform(function (Kegiatan $item) {
            $item->total_realisasi = $item->total_realisasi;
            $item->sisa_pagu = $item->sisa_pagu;
            $item->persentase_realisasi = $item->persentase_realisasi;
            return $item;
        });

        // List active Kasi for assignment
        $kasiList = User::where('role', UserRole::KASI->value)
            ->where('is_active', true)
            ->select(['id', 'name', 'seksi', 'jabatan'])
            ->get()
            ->map(fn ($k) => [
                'id' => $k->id,
                'name' => $k->name,
                'seksi' => $k->seksi?->value,
                'seksi_label' => $k->seksi?->label() ?? $k->name,
                'jabatan' => $k->jabatan,
            ]);

        $sumberDanaOptions = array_map(
            fn (SumberDana $item) => ['value' => $item->value, 'label' => $item->label()],
            SumberDana::cases()
        );

        $statusOptions = array_map(
            fn (StatusKegiatan $item) => ['value' => $item->value, 'label' => $item->label()],
            StatusKegiatan::cases()
        );

        $currentYear = (int) date('Y');
        $tahunOptions = range($currentYear + 1, 2024);

        return Inertia::render('Kegiatan/Index', [
            'kegiatanList' => $kegiatan,
            'kasiList' => $kasiList,
            'sumberDanaOptions' => $sumberDanaOptions,
            'statusOptions' => $statusOptions,
            'tahunOptions' => $tahunOptions,
            'filters' => [
                'tahun' => $tahun,
                'kasi_id' => $kasiId,
                'search' => $search,
                'status' => $status,
            ],
            'canManage' => $user->can('kegiatan.manage') || $user->hasRole('staf_keuangan'),
        ]);
    }

    /**
     * Store a newly created activity.
     */
    public function store(StoreKegiatanRequest $request): RedirectResponse
    {
        $this->kegiatanService->create($request->validated());

        return redirect()->route('kegiatan.index')->with('success', 'Kegiatan anggaran berhasil ditambahkan.');
    }

    /**
     * Update the specified activity.
     */
    public function update(UpdateKegiatanRequest $request, Kegiatan $kegiatan): RedirectResponse
    {
        $this->kegiatanService->update($kegiatan, $request->validated());

        return redirect()->route('kegiatan.index')->with('success', 'Kegiatan anggaran berhasil diperbarui.');
    }

    /**
     * Remove the specified activity.
     */
    public function destroy(Kegiatan $kegiatan): RedirectResponse
    {
        Gate::authorize('delete', $kegiatan);

        try {
            $this->kegiatanService->delete($kegiatan);
            return redirect()->route('kegiatan.index')->with('success', 'Kegiatan anggaran berhasil dihapus.');
        } catch (DomainException $e) {
            return redirect()->route('kegiatan.index')->with('error', $e->getMessage());
        }
    }
}
