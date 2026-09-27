<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\SpjStatus;
use App\Enums\UserRole;
use App\Models\Notifikasi;
use App\Models\Spj;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $permissions = ($user && method_exists($user, 'getAllPermissions'))
            ? $user->getAllPermissions()->pluck('name')->toArray()
            : [];

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role instanceof UserRole ? $user->role->value : (string) $user->role,
                    'seksi' => $user->seksi ? ($user->seksi instanceof \BackedEnum ? $user->seksi->value : (string) $user->seksi) : null,
                    'nip' => $user->nip,
                    'jabatan' => $user->jabatan,
                    'no_hp' => $user->no_hp,
                    'avatar' => $user->avatar,
                    'permissions' => $permissions,
                ] : null,
                'permissions' => $permissions,
            ],
            'notif_count' => $user ? Notifikasi::where('user_id', $user->id)->where('is_read', false)->count() : 0,
            'sidebar_badges' => $user ? [
                'spj_pending_konsolidasi' => ($user->role === UserRole::STAF_KEUANGAN || (is_string($user->role) && $user->role === UserRole::STAF_KEUANGAN->value))
                    ? Spj::where('status', SpjStatus::DIAJUKAN_KASI)->count()
                    : 0,
                'spj_pending_verifikasi' => ($user->role === UserRole::SEKMAT || (is_string($user->role) && $user->role === UserRole::SEKMAT->value))
                    ? Spj::where('status', SpjStatus::DIAJUKAN_VERIFIKASI)->count()
                    : 0,
                'spj_ditolak_kasi' => ($user->role === UserRole::KASI || (is_string($user->role) && $user->role === UserRole::KASI->value))
                    ? Spj::where('diajukan_oleh', $user->id)->where('status', SpjStatus::DITOLAK)->count()
                    : 0,
            ] : [],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
