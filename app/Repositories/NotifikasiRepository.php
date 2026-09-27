<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Notifikasi;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class NotifikasiRepository
{
    /**
     * @return LengthAwarePaginator<Notifikasi>
     */
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Notifikasi::where('user_id', $userId)
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, Notifikasi>
     */
    public function getLatestUnread(int $userId, int $limit = 10): Collection
    {
        return Notifikasi::where('user_id', $userId)
            ->where('is_read', false)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function countUnread(int $userId): int
    {
        return Notifikasi::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Notifikasi
    {
        return Notifikasi::create($data);
    }

    public function markAsRead(Notifikasi $notifikasi): bool
    {
        return $notifikasi->update(['is_read' => true]);
    }

    public function markAllAsRead(int $userId): int
    {
        return Notifikasi::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
