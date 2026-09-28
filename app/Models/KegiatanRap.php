<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KegiatanRap extends Model
{
    use HasFactory;

    protected $table = 'kegiatan_rap';

    /** @var list<string> */
    protected $fillable = [
        'program_id',
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

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    /** @return HasMany<SubKegiatan, $this> */
    public function subKegiatan(): HasMany
    {
        return $this->hasMany(SubKegiatan::class, 'kegiatan_rap_id');
    }

    // ── Computed ──

    /**
     * Label lengkap: kode + nama.
     */
    public function getLabelAttribute(): string
    {
        return "{$this->kode} {$this->nama}";
    }
}
