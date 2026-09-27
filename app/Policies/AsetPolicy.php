<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Aset;
use App\Models\User;

class AsetPolicy
{
    /**
     * Determine whether the user can view any assets.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('aset.view');
    }

    /**
     * Determine whether the user can view the specific asset.
     */
    public function view(User $user, Aset $aset): bool
    {
        return $user->can('aset.view');
    }

    /**
     * Determine whether the user can create an asset.
     */
    public function create(User $user): bool
    {
        return $user->can('aset.create');
    }

    /**
     * Determine whether the user can update the asset.
     */
    public function update(User $user, Aset $aset): bool
    {
        return $user->can('aset.update');
    }

    /**
     * Determine whether the user can delete the asset.
     * BR-ASET-06: Aset tidak boleh dihapus (hanya bisa diubah kondisi menjadi rusak_berat).
     */
    public function delete(User $user, Aset $aset): bool
    {
        return false;
    }

    /**
     * Determine whether the user can generate a QR code for the asset.
     */
    public function generateQr(User $user, Aset $aset): bool
    {
        return $user->can('aset.generate-qr');
    }

    /**
     * Determine whether the user can generate KIB/KIR documents.
     */
    public function generateKibKir(User $user, Aset $aset): bool
    {
        return $user->can('aset.generate-kibkir');
    }
}
