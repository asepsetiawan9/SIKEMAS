<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Services\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * Display the appropriate dashboard based on user role.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $role = $user->role instanceof UserRole ? $user->role : UserRole::tryFrom((string) $user->role);

        // SEC-01: Tolak tegas jika role tidak terdaftar / null
        if (! $role) {
            abort(403, 'Akses ditolak: Akun Anda tidak memiliki peran (role) yang sah dalam sistem SIKEMAS.');
        }

        // Staf Umum redirects to Aset management as their primary workspace
        if ($role === UserRole::STAF_UMUM) {
            return redirect()->route('aset.index');
        }

        // Kasi Dashboard
        if ($role === UserRole::KASI) {
            $data = $this->dashboardService->getKasiDashboard($user);
            return Inertia::render('Dashboard/KasiDashboard', $data);
        }

        // Camat Executive Dashboard
        if ($role === UserRole::CAMAT) {
            $data = $this->dashboardService->getCamatDashboard();
            return Inertia::render('Dashboard/CamatDashboard', $data);
        }

        // Super Admin, Staf Keuangan & Sekmat Dashboard (Operasional / Monitoring / Verifikasi)
        if ($role === UserRole::SUPER_ADMIN || $role === UserRole::STAF_KEUANGAN || $role === UserRole::SEKMAT) {
            $data = $this->dashboardService->getStafSekmatDashboard($role->value);
            return Inertia::render('Dashboard/StafSekmatDashboard', $data);
        }

        abort(403, 'Akses ditolak: Peran pengguna tidak sah.');
    }
}
