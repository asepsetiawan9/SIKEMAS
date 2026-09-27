<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SpjStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Spj extends Model
{
    use HasFactory;

    protected $table = 'spj';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kegiatan_id',
        'nominal',
        'status',
        'file_bukti',
        'nomor_spj',
        'tanggal_pengajuan',
        'tanggal_konsolidasi',
        'tanggal_verifikasi',
        'periode_bulan',
        'periode_tahun',
        'jenis_belanja',
        'diajukan_oleh',
        'dikonsolidasi_oleh',
        'diverifikasi_oleh',
        'catatan_verifikasi',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'status' => SpjStatus::class,
            'tanggal_pengajuan' => 'date',
            'tanggal_konsolidasi' => 'date',
            'tanggal_verifikasi' => 'date',
            'periode_bulan' => 'integer',
            'periode_tahun' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Kegiatan, $this>
     */
    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class, 'kegiatan_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function diajukanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dikonsolidasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikonsolidasi_oleh');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function diverifikasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function isDiverifikasi(): bool
    {
        return $this->status === SpjStatus::DIVERIFIKASI;
    }

    public function isDitolak(): bool
    {
        return $this->status === SpjStatus::DITOLAK;
    }
}
