<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Kegiatan;
use App\Models\User;

class KegiatanPolicy
{
    /**
     * Determine whether the user can view any activities.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('kegiatan.view') 
            || $user->hasRole('kasi') 
            || $user->hasRole('staf_keuangan') 
            || $user->hasRole('sekmat') 
            || $user->hasRole('camat');
    }

    /**
     * Determine whether the user can view the specific activity.
     */
    public function view(User $user, Kegiatan $kegiatan): bool
    {
        if ($user->can('kegiatan.view') || $user->hasRole('staf_keuangan') || $user->hasRole('sekmat') || $user->hasRole('camat')) {
            return true;
        }

        if ($user->hasRole('kasi')) {
            return (int) $kegiatan->kasi_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create an activity.
     */
    public function create(User $user): bool
    {
        return $user->can('kegiatan.manage') || $user->hasRole('staf_keuangan');
    }

    /**
     * Determine whether the user can update the activity.
     */
    public function update(User $user, Kegiatan $kegiatan): bool
    {
        return $user->can('kegiatan.manage') || $user->hasRole('staf_keuangan');
    }

    /**
     * Determine whether the user can delete the activity.
     */
    public function delete(User $user, Kegiatan $kegiatan): bool
    {
        return $user->can('kegiatan.manage') || $user->hasRole('staf_keuangan');
    }
}
