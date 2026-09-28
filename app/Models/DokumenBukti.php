<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JenisDokumen;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenBukti extends Model
{
    use HasFactory;

    protected $table = 'dokumen_bukti';

    /** @var list<string> */
    protected $fillable = [
        'belanja_id',
        'jenis_dokumen',
        'nama_dokumen',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'nomor_dokumen',
        'uploaded_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'jenis_dokumen' => JenisDokumen::class,
            'file_size' => 'integer',
        ];
    }

    // ── Relasi ──

    /** @return BelongsTo<Belanja, $this> */
    public function belanja(): BelongsTo
    {
        return $this->belongsTo(Belanja::class, 'belanja_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ── Computed ──

    /**
     * Format ukuran file yang human-readable.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 1) . ' MB';
    }

    /**
     * Apakah file ini adalah gambar?
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->file_type ?? '', 'image/');
    }

    /**
     * Apakah file ini adalah PDF?
     */
    public function getIsPdfAttribute(): bool
    {
        return $this->file_type === 'application/pdf';
    }
}
