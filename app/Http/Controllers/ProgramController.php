<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreKegiatanRapRequest;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\StoreSubKegiatanRequest;
use App\Models\Program;
use App\Services\ProgramService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends Controller
{
    public function __construct(
        protected ProgramService $programService
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Program::class);

        $tahun = $request->filled('tahun') ? (int) $request->input('tahun') : (int) date('Y');
        $tree = $this->programService->getHierarchyTree($tahun);

        return Inertia::render('Program/Index', [
            'tree' => $tree,
            'filters' => [
                'tahun' => $tahun,
            ],
            'availableYears' => range((int) date('Y') + 1, (int) date('Y') - 4),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $tahun = $request->filled('tahun') ? (int) $request->input('tahun') : null;
        $options = $this->programService->getDropdownOptions($tahun);

        return response()->json($options);
    }

    public function storeProgram(StoreProgramRequest $request): RedirectResponse
    {
        Gate::authorize('create', Program::class);

        $this->programService->createProgram(
            $request->validated(),
            $request->user()?->id
        );

        return redirect()->back()->with('success', 'Program berhasil ditambahkan.');
    }

    public function updateProgram(StoreProgramRequest $request, int $id): RedirectResponse
    {
        $program = Program::findOrFail($id);
        Gate::authorize('update', $program);

        $this->programService->updateProgram($id, $request->validated());

        return redirect()->back()->with('success', 'Program berhasil diperbarui.');
    }

    public function destroyProgram(int $id): RedirectResponse
    {
        $program = Program::findOrFail($id);
        Gate::authorize('delete', $program);

        try {
            $this->programService->deleteProgram($id);
            return redirect()->back()->with('success', 'Program berhasil dihapus.');
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function storeKegiatan(StoreKegiatanRapRequest $request): RedirectResponse
    {
        Gate::authorize('create', Program::class);

        $this->programService->createKegiatan($request->validated());

        return redirect()->back()->with('success', 'Kegiatan berhasil ditambahkan.');
    }

    public function updateKegiatan(StoreKegiatanRapRequest $request, int $id): RedirectResponse
    {
        Gate::authorize('create', Program::class);

        $this->programService->updateKegiatan($id, $request->validated());

        return redirect()->back()->with('success', 'Kegiatan berhasil diperbarui.');
    }

    public function destroyKegiatan(int $id): RedirectResponse
    {
        Gate::authorize('create', Program::class);

        try {
            $this->programService->deleteKegiatan($id);
            return redirect()->back()->with('success', 'Kegiatan berhasil dihapus.');
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function storeSubKegiatan(StoreSubKegiatanRequest $request): RedirectResponse
    {
        Gate::authorize('create', Program::class);

        $this->programService->createSubKegiatan($request->validated());

        return redirect()->back()->with('success', 'Sub Kegiatan berhasil ditambahkan.');
    }

    public function updateSubKegiatan(StoreSubKegiatanRequest $request, int $id): RedirectResponse
    {
        Gate::authorize('create', Program::class);

        $this->programService->updateSubKegiatan($id, $request->validated());

        return redirect()->back()->with('success', 'Sub Kegiatan berhasil diperbarui.');
    }

    public function destroySubKegiatan(int $id): RedirectResponse
    {
        Gate::authorize('create', Program::class);

        try {
            $this->programService->deleteSubKegiatan($id);
            return redirect()->back()->with('success', 'Sub Kegiatan berhasil dihapus.');
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
