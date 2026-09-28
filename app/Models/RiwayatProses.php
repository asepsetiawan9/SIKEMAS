<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatProses extends Model
{
    use HasFactory;

    protected $table = 'riwayat_proses';

    /** @var list<string> */
    protected $fillable = [
        'belanja_id',
        'aksi',
        'keterangan',
        'user_id',
    ];

    // ── Relasi ──

    /** @return BelongsTo<Belanja, $this> */
    public function belanja(): BelongsTo
    {
        return $this->belongsTo(Belanja::class, 'belanja_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Computed ──

    /**
     * Label aksi yang human-readable.
     */
    public function getLabelAksiAttribute(): string
    {
        return match ($this->aksi) {
            'input_data' => 'Memasukkan data belanja',
            'update_data' => 'Memperbarui data belanja',
            'upload_dokumen' => 'Mengunggah dokumen bukti',
            'hapus_dokumen' => 'Menghapus dokumen bukti',
            'upload_ulang' => 'Mengunggah ulang dokumen',
            'ajukan_verifikasi' => 'Mengajukan verifikasi ke Sekmat',
            'verifikasi_sekmat' => 'Diverifikasi oleh Sekmat',
            'tolak_sekmat' => 'Dikembalikan oleh Sekmat',
            'setujui_camat' => 'Disetujui oleh Camat',
            'tolak_camat' => 'Dikembalikan oleh Camat',
            'perbaikan' => 'Memperbaiki data/dokumen',
            default => $this->aksi,
        };
    }
}
