<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Belanja;
use App\Repositories\BelanjaRepository;
use App\Services\BelanjaService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VerifikasiController extends Controller
{
    public function __construct(
        protected BelanjaService $belanjaService,
        protected BelanjaRepository $belanjaRepository
    ) {}

    public function sekmatIndex(Request $request): Response
    {
        Gate::authorize('viewAny', Belanja::class);

        $antrean = $this->belanjaRepository->getAntreanSekmat(15);

        return Inertia::render('Verifikasi/SekmatIndex', [
            'antrean' => $antrean,
        ]);
    }

    public function verifikasiSekmat(Request $request, int $id): RedirectResponse
    {
        $belanja = Belanja::findOrFail($id);
        Gate::authorize('verifikasiSekmat', $belanja);

        $request->validate([
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->belanjaService->verifikasiSekmat(
                $id,
                (int) $request->user()->id,
                $request->input('catatan')
            );
            return redirect()->back()->with('success', 'Belanja berhasil diverifikasi dan diteruskan ke Camat.');
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function kembalikanSekmat(Request $request, int $id): RedirectResponse
    {
        $belanja = Belanja::findOrFail($id);
        Gate::authorize('kembalikanSekmat', $belanja);

        $request->validate([
            'catatan' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'catatan.required' => 'Alasan pengembalian wajib diisi agar operator mengetahui perbaikan yang diperlukan.',
        ]);

        try {
            $this->belanjaService->kembalikanSekmat(
                $id,
                (int) $request->user()->id,
                $request->input('catatan')
            );
            return redirect()->back()->with('success', 'Belanja berhasil dikembalikan ke Operator untuk diperbaiki.');
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function camatIndex(Request $request): Response
    {
        Gate::authorize('viewAny', Belanja::class);

        $antrean = $this->belanjaRepository->getAntreanCamat(15);

        return Inertia::render('Verifikasi/CamatIndex', [
            'antrean' => $antrean,
        ]);
    }

    public function setujuiCamat(Request $request, int $id): RedirectResponse
    {
        $belanja = Belanja::findOrFail($id);
        Gate::authorize('setujuiCamat', $belanja);

        $request->validate([
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->belanjaService->setujuiCamat(
                $id,
                (int) $request->user()->id,
                $request->input('catatan')
            );
            return redirect()->back()->with('success', 'Belanja telah disetujui secara resmi oleh Camat.');
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function kembalikanCamat(Request $request, int $id): RedirectResponse
    {
        $belanja = Belanja::findOrFail($id);
        Gate::authorize('kembalikanCamat', $belanja);

        $request->validate([
            'catatan' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'catatan.required' => 'Alasan pengembalian wajib diisi agar operator mengetahui perbaikan yang diperlukan.',
        ]);

        try {
            $this->belanjaService->kembalikanCamat(
                $id,
                (int) $request->user()->id,
                $request->input('catatan')
            );
            return redirect()->back()->with('success', 'Belanja dikembalikan ke Operator.');
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
