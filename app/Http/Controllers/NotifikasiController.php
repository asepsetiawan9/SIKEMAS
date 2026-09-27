<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotifikasiController extends Controller
{
    public function __construct(
        protected NotifikasiService $notifikasiService
    ) {}

    /**
     * Display listing of notifications.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $notifications = Notifikasi::where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        return Inertia::render('Notifikasi/Index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Get recent unread notifications for Topbar dropdown.
     */
    public function recent(Request $request): JsonResponse
    {
        $notifications = $this->notifikasiService->getUnreadByUser($request->user()->id);

        return response()->json([
            'unread_count' => $notifications->count(),
            'items' => $notifications->take(10),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, Notifikasi $notifikasi): RedirectResponse|JsonResponse
    {
        abort_unless((int) $notifikasi->user_id === (int) $request->user()->id, 403);

        $this->notifikasiService->tandaiDibaca($notifikasi->id);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        if ($notifikasi->link) {
            return redirect($notifikasi->link);
        }

        return back();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        $this->notifikasiService->tandaiSemuaDibaca($request->user()->id);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }
}
