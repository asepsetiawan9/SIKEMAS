<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Repositories\BelanjaRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected BelanjaRepository $belanjaRepository
    ) {}

    /**
     * Tampilan dashboard sederhana berbasis angka ringkasan SPJ / Bukti Belanja.
     * Sesuai mandat rapat: bukan sistem keuangan kompleks, fokus pada arsip bukti belanja.
     */
    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $role = $user->role instanceof UserRole ? $user->role : UserRole::tryFrom((string) $user->role);

        // SEC-01: Tolak tegas jika role tidak terdaftar / null
        if (! $role) {
            abort(403, 'Akses ditolak: Akun Anda tidak memiliki peran (role) yang sah dalam sistem SIMPEL KAN.');
        }

        // Staf Umum diarahkan ke modul aset sebagai workspace utama
        if ($role === UserRole::STAF_UMUM) {
            return redirect()->route('aset.index');
        }

        $stats = $this->belanjaRepository->getDashboardStats();
        $recentBelanja = $this->belanjaRepository->getRecent(8);

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'recentBelanja' => $recentBelanja,
            'userRole' => $role->value,
        ]);
    }
}
