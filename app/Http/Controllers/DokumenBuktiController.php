<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\JenisDokumen;
use App\Http\Requests\UploadDokumenRequest;
use App\Models\Belanja;
use App\Models\DokumenBukti;
use App\Services\DokumenBuktiService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DokumenBuktiController extends Controller
{
    public function __construct(
        protected DokumenBuktiService $dokumenService
    ) {}

    public function store(UploadDokumenRequest $request, int $belanjaId): RedirectResponse
    {
        $belanja = Belanja::findOrFail($belanjaId);
        Gate::authorize('uploadDokumen', $belanja);

        try {
            $this->dokumenService->uploadDokumen(
                belanjaId: $belanjaId,
                file: $request->file('file'),
                jenisDokumen: JenisDokumen::from($request->input('jenis_dokumen')),
                keterangan: $request->input('keterangan'),
                userId: (int) $request->user()->id
            );

            return redirect()->back()->with('success', 'Dokumen bukti berhasil diunggah.');
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $dokumen = DokumenBukti::with('belanja')->findOrFail($id);
        Gate::authorize('deleteDokumen', $dokumen->belanja);

        try {
            $this->dokumenService->deleteDokumen($id, (int) $request->user()->id);
            return redirect()->back()->with('success', 'Dokumen berhasil dihapus.');
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function download(int $id): StreamedResponse
    {
        $dokumen = DokumenBukti::with('belanja')->findOrFail($id);
        Gate::authorize('view', $dokumen->belanja);

        if (! Storage::disk('public')->exists($dokumen->file_path)) {
            abort(404, 'File dokumen tidak ditemukan pada penyimpanan server.');
        }

        return Storage::disk('public')->download($dokumen->file_path, $dokumen->nama_file_asli);
    }
}
