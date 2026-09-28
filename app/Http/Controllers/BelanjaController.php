<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\JenisBelanja;
use App\Enums\JenisDokumen;
use App\Enums\StatusDokumen;
use App\Enums\StatusVerifikasi;
use App\Http\Requests\StoreBelanjaRequest;
use App\Http\Requests\UpdateBelanjaRequest;
use App\Models\Belanja;
use App\Repositories\BelanjaRepository;
use App\Services\BelanjaService;
use App\Services\ProgramService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BelanjaController extends Controller
{
    public function __construct(
        protected BelanjaService $belanjaService,
        protected BelanjaRepository $belanjaRepository,
        protected ProgramService $programService
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Belanja::class);

        $filters = [
            'search' => $request->input('search'),
            'program_id' => $request->input('program_id'),
            'kegiatan_rap_id' => $request->input('kegiatan_rap_id'),
            'sub_kegiatan_id' => $request->input('sub_kegiatan_id'),
            'jenis_belanja' => $request->input('jenis_belanja'),
            'status_dokumen' => $request->input('status_dokumen'),
            'status_verifikasi' => $request->input('status_verifikasi'),
            'tanggal_dari' => $request->input('tanggal_dari'),
            'tanggal_sampai' => $request->input('tanggal_sampai'),
        ];

        $belanjaList = $this->belanjaRepository->getFilteredList($filters, 15);
        $programTree = $this->programService->getDropdownOptions((int) date('Y'));

        return Inertia::render('Belanja/Index', [
            'belanjaList' => $belanjaList,
            'filters' => $filters,
            'programTree' => $programTree,
            'jenisBelanjaOptions' => JenisBelanja::cases(),
            'statusDokumenOptions' => StatusDokumen::cases(),
            'statusVerifikasiOptions' => StatusVerifikasi::cases(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Belanja::class);

        $programTree = $this->programService->getDropdownOptions((int) date('Y'));

        return Inertia::render('Belanja/Create', [
            'programTree' => $programTree,
            'jenisBelanjaOptions' => JenisBelanja::cases(),
        ]);
    }

    public function store(StoreBelanjaRequest $request): RedirectResponse
    {
        Gate::authorize('create', Belanja::class);

        $belanja = $this->belanjaService->createBelanja(
            $request->validated(),
            (int) $request->user()->id
        );

        return redirect()->route('belanja.show', $belanja->id)
            ->with('success', 'Uraian belanja berhasil dibuat. Silakan unggah dokumen bukti belanja.');
    }

    public function show(int $id): Response
    {
        $belanja = $this->belanjaRepository->findByIdWithDetails($id);
        if (! $belanja) {
            abort(404, 'Data belanja tidak ditemukan.');
        }

        Gate::authorize('view', $belanja);

        return Inertia::render('Belanja/Detail', [
            'belanja' => $belanja,
            'checklist' => $belanja->dokumen_checklist,
            'canEdit' => $belanja->isEditable() && request()->user()->can('belanja.edit'),
            'canDelete' => $belanja->isEditable() && request()->user()->can('belanja.delete'),
            'canUpload' => ! $belanja->isDokumenLocked() && request()->user()->can('dokumen.upload'),
            'canAjukan' => request()->user()->can('ajukan', $belanja),
            'jenisDokumenOptions' => JenisDokumen::cases(),
        ]);
    }

    public function edit(int $id): Response
    {
        $belanja = Belanja::with('subKegiatan.kegiatanRap.program')->findOrFail($id);
        Gate::authorize('update', $belanja);

        $programTree = $this->programService->getDropdownOptions((int) date('Y'));

        return Inertia::render('Belanja/Edit', [
            'belanja' => $belanja,
            'programTree' => $programTree,
            'jenisBelanjaOptions' => JenisBelanja::cases(),
        ]);
    }

    public function update(UpdateBelanjaRequest $request, int $id): RedirectResponse
    {
        $belanja = Belanja::findOrFail($id);
        Gate::authorize('update', $belanja);

        try {
            $this->belanjaService->updateBelanja(
                $id,
                $request->validated(),
                (int) $request->user()->id
            );
            return redirect()->route('belanja.show', $id)
                ->with('success', 'Data belanja berhasil diperbarui.');
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $belanja = Belanja::findOrFail($id);
        Gate::authorize('delete', $belanja);

        try {
            $this->belanjaService->deleteBelanja($id, (int) request()->user()->id);
            return redirect()->route('belanja.index')
                ->with('success', 'Data belanja dan seluruh berkas berhasil dihapus.');
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function ajukan(int $id): RedirectResponse
    {
        $belanja = Belanja::findOrFail($id);
        Gate::authorize('ajukan', $belanja);

        try {
            $this->belanjaService->ajukanVerifikasi($id, (int) request()->user()->id);
            return redirect()->route('belanja.show', $id)
                ->with('success', 'Belanja berhasil diajukan untuk verifikasi Sekmat.');
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
