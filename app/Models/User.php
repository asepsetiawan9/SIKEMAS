<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SeksiType;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'seksi',
        'nip',
        'jabatan',
        'no_hp',
        'avatar',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'seksi' => SeksiType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Kegiatan, $this>
     */
    public function kegiatan(): HasMany
    {
        return $this->hasMany(Kegiatan::class, 'kasi_id');
    }

    /**
     * @return HasMany<Spj, $this>
     */
    public function spjDiajukan(): HasMany
    {
        return $this->hasMany(Spj::class, 'diajukan_oleh');
    }

    /**
     * @return HasMany<Aset, $this>
     */
    public function aset(): HasMany
    {
        return $this->hasMany(Aset::class, 'penanggung_jawab');
    }

    /**
     * @return HasMany<ArsipDigital, $this>
     */
    public function arsipDigital(): HasMany
    {
        return $this->hasMany(ArsipDigital::class, 'uploaded_by');
    }

    /**
     * @return HasMany<Notifikasi, $this>
     */
    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'user_id');
    }

    /**
     * @return HasMany<LogAktivitas, $this>
     */
    public function logAktivitas(): HasMany
    {
        return $this->hasMany(LogAktivitas::class, 'user_id');
    }

    public function isStafUmum(): bool
    {
        return $this->role === UserRole::STAF_UMUM;
    }

    public function isStafKeuangan(): bool
    {
        return $this->role === UserRole::STAF_KEUANGAN;
    }

    public function isKasi(): bool
    {
        return $this->role === UserRole::KASI;
    }

    public function isSekmat(): bool
    {
        return $this->role === UserRole::SEKMAT;
    }

    public function isCamat(): bool
    {
        return $this->role === UserRole::CAMAT;
    }
}
