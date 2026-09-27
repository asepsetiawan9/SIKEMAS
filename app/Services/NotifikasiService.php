<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotifikasiTipe;
use App\Models\Notifikasi;
use App\Models\User;
use App\Repositories\NotifikasiRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class NotifikasiService
{
    public function __construct(
        protected NotifikasiRepository $notifikasiRepository
    ) {}

    /**
     * @return LengthAwarePaginator<Notifikasi>
     */
    public function getUserNotifications(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->notifikasiRepository->paginateForUser($user->id, $perPage);
    }

    /**
     * @return Collection<int, Notifikasi>
     */
    public function getHeaderNotifications(User $user, int $limit = 10): Collection
    {
        return $this->notifikasiRepository->getLatestUnread($user->id, $limit);
    }

    public function getUnreadCount(User $user): int
    {
        return $this->notifikasiRepository->countUnread($user->id);
    }

    public function send(
        int $userId,
        string $judul,
        string $pesan,
        NotifikasiTipe $tipe = NotifikasiTipe::INFO,
        ?string $link = null
    ): Notifikasi {
        return $this->notifikasiRepository->create([
            'user_id' => $userId,
            'judul' => $judul,
            'pesan' => $pesan,
            'tipe' => $tipe->value,
            'is_read' => false,
            'link' => $link,
        ]);
    }

    public function markAsRead(Notifikasi $notifikasi): bool
    {
        return $this->notifikasiRepository->markAsRead($notifikasi);
    }

    public function markAllAsRead(User $user): int
    {
        return $this->notifikasiRepository->markAllAsRead($user->id);
    }
}
