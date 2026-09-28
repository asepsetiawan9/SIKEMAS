<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubKegiatan extends Model
{
    use HasFactory;

    protected $table = 'sub_kegiatan';

    /** @var list<string> */
    protected $fillable = [
        'kegiatan_rap_id',
        'kode',
        'nama',
        'deskripsi',
        'keterangan',
    ];

    /** @var list<string> */
    protected $appends = [
        'keterangan',
        'label',
    ];

    public function getKeteranganAttribute(): ?string
    {
        return $this->deskripsi;
    }

    public function setKeteranganAttribute(?string $value): void
    {
        $this->attributes['deskripsi'] = $value;
    }

    // ── Relasi ──

    /** @return BelongsTo<KegiatanRap, $this> */
    public function kegiatanRap(): BelongsTo
    {
        return $this->belongsTo(KegiatanRap::class, 'kegiatan_rap_id');
    }

    /** @return HasMany<Belanja, $this> */
    public function belanja(): HasMany
    {
        return $this->hasMany(Belanja::class, 'sub_kegiatan_id');
    }

    // ── Computed ──

    /**
     * Label lengkap: kode + nama.
     */
    public function getLabelAttribute(): string
    {
        return "{$this->kode} {$this->nama}";
    }

    /**
     * Total nilai belanja di bawah sub kegiatan ini.
     */
    public function getTotalNilaiBelanjaAttribute(): float
    {
        return (float) $this->belanja()->sum('total_nilai');
    }
}
