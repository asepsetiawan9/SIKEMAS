<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\SpjStatus;
use App\Models\Spj;
use App\Models\User;

class SpjPolicy
{
    /**
     * Determine whether the user can view any SPJs.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('spj.view-all') || $user->can('spj.view-own');
    }

    /**
     * Determine whether the user can view the specific SPJ.
     */
    public function view(User $user, Spj $spj): bool
    {
        if ($user->can('spj.view-all')) {
            return true;
        }

        if ($user->can('spj.view-own')) {
            return (int) $spj->diajukan_oleh === (int) $user->id
                || ($spj->kegiatan && (int) $spj->kegiatan->kasi_id === (int) $user->id);
        }

        return false;
    }

    /**
     * Determine whether the user can create an SPJ.
     */
    public function create(User $user): bool
    {
        return $user->can('spj.create');
    }

    /**
     * Determine whether the user can update the SPJ.
     * BR-SPJ-06: Diverifikasi is immutable.
     * BR-SPJ-03: Ditolak can only be revised by Staf Keuangan.
     */
    public function update(User $user, Spj $spj): bool
    {
        if ($spj->status === SpjStatus::DIVERIFIKASI) {
            return false;
        }

        if ($user->can('spj.konsolidasi')) {
            return in_array($spj->status, [
                SpjStatus::DIAJUKAN_KASI,
                SpjStatus::DIKONSOLIDASI,
                SpjStatus::DITOLAK,
            ], true);
        }

        if ($user->can('spj.create')) {
            return $spj->status === SpjStatus::DRAFT && (int) $spj->diajukan_oleh === (int) $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the SPJ.
     */
    public function delete(User $user, Spj $spj): bool
    {
        if ($spj->status === SpjStatus::DIVERIFIKASI) {
            return false;
        }

        return $spj->status === SpjStatus::DRAFT && (int) $spj->diajukan_oleh === (int) $user->id;
    }

    /**
     * Determine whether the user can perform konsolidasi on the SPJ.
     */
    public function konsolidasi(User $user, Spj $spj): bool
    {
        return $user->can('spj.konsolidasi')
            && in_array($spj->status, [SpjStatus::DIAJUKAN_KASI, SpjStatus::DITOLAK], true);
    }

    /**
     * Determine whether the user can submit the SPJ for verification.
     */
    public function ajukanVerifikasi(User $user, Spj $spj): bool
    {
        return $user->can('spj.ajukan-verifikasi')
            && $spj->status === SpjStatus::DIKONSOLIDASI;
    }

    /**
     * Determine whether the user can verify or reject the SPJ.
     */
    public function verifikasi(User $user, Spj $spj): bool
    {
        return $user->can('spj.verifikasi')
            && $spj->status === SpjStatus::DIAJUKAN_VERIFIKASI;
    }
}
