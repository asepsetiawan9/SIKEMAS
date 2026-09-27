<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SpjStatus;
use App\Enums\StatusKegiatan;
use App\Enums\SumberDana;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kegiatan extends Model
{
    use HasFactory;

    protected $table = 'kegiatan';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'pagu',
        'tahun_anggaran',
        'kode_rekening',
        'sumber_dana',
        'status',
        'deskripsi',
        'periode_mulai',
        'periode_selesai',
        'kasi_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pagu' => 'decimal:2',
            'tahun_anggaran' => 'integer',
            'sumber_dana' => SumberDana::class,
            'status' => StatusKegiatan::class,
            'periode_mulai' => 'date',
            'periode_selesai' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function kasi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasi_id');
    }

    /**
     * @return HasMany<Spj, $this>
     */
    public function spj(): HasMany
    {
        return $this->hasMany(Spj::class, 'kegiatan_id');
    }

    /**
     * Total realisasi anggaran (SPJ berstatus diverifikasi)
     */
    public function getTotalRealisasiAttribute(): float
    {
        return (float) $this->spj()
            ->where('status', SpjStatus::DIVERIFIKASI->value)
            ->sum('nominal');
    }

    /**
     * Sisa pagu real-time: pagu - realisasi
     */
    public function getSisaPaguAttribute(): float
    {
        return max(0, (float) $this->pagu - $this->total_realisasi);
    }

    /**
     * Persentase realisasi anggaran
     */
    public function getPersentaseRealisasiAttribute(): float
    {
        if ((float) $this->pagu <= 0) {
            return 0.0;
        }

        return round(($this->total_realisasi / (float) $this->pagu) * 100, 2);
    }
}
