<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StatusDokumen;
use App\Enums\StatusVerifikasi;
use App\Models\Belanja;
use App\Models\User;

class BelanjaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('belanja.view');
    }

    public function view(User $user, Belanja $belanja): bool
    {
        return $user->can('belanja.view');
    }

    public function create(User $user): bool
    {
        return $user->can('belanja.create');
    }

    public function update(User $user, Belanja $belanja): bool
    {
        if (! $belanja->isEditable()) {
            return false;
        }

        return $user->can('belanja.edit');
    }

    public function delete(User $user, Belanja $belanja): bool
    {
        if (! $belanja->isEditable()) {
            return false;
        }

        return $user->can('belanja.delete');
    }

    public function uploadDokumen(User $user, Belanja $belanja): bool
    {
        if ($belanja->isDokumenLocked()) {
            return false;
        }

        return $user->can('dokumen.upload');
    }

    public function deleteDokumen(User $user, Belanja $belanja): bool
    {
        if ($belanja->isDokumenLocked()) {
            return false;
        }

        return $user->can('dokumen.delete');
    }

    public function ajukan(User $user, Belanja $belanja): bool
    {
        if (! $user->can('belanja.ajukan')) {
            return false;
        }

        $canStatus = in_array($belanja->status_verifikasi, [
            StatusVerifikasi::DRAFT,
            StatusVerifikasi::DIKEMBALIKAN_SEKMAT,
            StatusVerifikasi::DIKEMBALIKAN_CAMAT,
        ], true);

        $hasDokumen = $belanja->relationLoaded('dokumenBukti')
            ? $belanja->dokumenBukti->isNotEmpty()
            : $belanja->dokumenBukti()->exists();

        return $canStatus && $hasDokumen;
    }

    public function verifikasiSekmat(User $user, Belanja $belanja): bool
    {
        return $user->can('belanja.verifikasi')
            && $belanja->status_verifikasi === StatusVerifikasi::DIAJUKAN;
    }

    public function kembalikanSekmat(User $user, Belanja $belanja): bool
    {
        return $user->can('belanja.verifikasi')
            && $belanja->status_verifikasi === StatusVerifikasi::DIAJUKAN;
    }

    public function setujuiCamat(User $user, Belanja $belanja): bool
    {
        return $user->can('belanja.setujui')
            && $belanja->status_verifikasi === StatusVerifikasi::DIVERIFIKASI_SEKMAT;
    }

    public function kembalikanCamat(User $user, Belanja $belanja): bool
    {
        return $user->can('belanja.setujui')
            && $belanja->status_verifikasi === StatusVerifikasi::DIVERIFIKASI_SEKMAT;
    }
}
