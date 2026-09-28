<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Program extends Model
{
    use HasFactory;

    protected $table = 'program';

    /** @var list<string> */
    protected $fillable = [
        'kode',
        'nama',
        'tahun_anggaran',
        'deskripsi',
        'keterangan',
        'created_by',
    ];

    /** @var list<string> */
    protected $appends = [
        'keterangan',
    ];

    public function getKeteranganAttribute(): ?string
    {
        return $this->deskripsi;
    }

    public function setKeteranganAttribute(?string $value): void
    {
        $this->attributes['deskripsi'] = $value;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tahun_anggaran' => 'integer',
        ];
    }

    // ── Relasi ──

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<KegiatanRap, $this> */
    public function kegiatanRap(): HasMany
    {
        return $this->hasMany(KegiatanRap::class, 'program_id');
    }

    /** @return HasManyThrough<SubKegiatan, KegiatanRap, $this> */
    public function subKegiatan(): HasManyThrough
    {
        return $this->hasManyThrough(SubKegiatan::class, KegiatanRap::class, 'program_id', 'kegiatan_rap_id');
    }

    // ── Computed ──

    /**
     * Hitung total belanja di bawah program ini.
     */
    public function getTotalBelanjaCountAttribute(): int
    {
        return $this->subKegiatan()
            ->join('belanja', 'belanja.sub_kegiatan_id', '=', 'sub_kegiatan.id')
            ->count();
    }
}
