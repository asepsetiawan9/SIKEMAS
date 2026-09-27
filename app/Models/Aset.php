<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CaraPerolehan;
use App\Enums\KondisiAset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Aset extends Model
{
    use HasFactory;

    protected $table = 'aset';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kode_barang',
        'nama',
        'tahun_perolehan',
        'nilai',
        'kondisi',
        'lokasi',
        'penanggung_jawab',
        'qr_code_path',
        'merk_type',
        'nomor_register',
        'ukuran',
        'bahan',
        'cara_perolehan',
        'tanggal_verifikasi_fisik',
        'foto_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun_perolehan' => 'integer',
            'nilai' => 'decimal:2',
            'kondisi' => KondisiAset::class,
            'cara_perolehan' => CaraPerolehan::class,
            'tanggal_verifikasi_fisik' => 'date',
        ];
    }

    /**
     * @var list<string>
     */
    protected $appends = [
        'foto_url',
        'qr_code_url',
        'is_overdue_verifikasi',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penanggung_jawab');
    }

    /**
     * @return HasMany<KibKir, $this>
     */
    public function kibKirs(): HasMany
    {
        return $this->hasMany(KibKir::class, 'aset_id');
    }

    /**
     * @return HasOne<KibKir, $this>
     */
    public function kibKir(): HasOne
    {
        return $this->hasOne(KibKir::class, 'aset_id')->latestOfMany();
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_path ? asset('storage/' . $this->foto_path) : null;
    }

    public function getQrCodeUrlAttribute(): ?string
    {
        return $this->qr_code_path ? asset('storage/' . $this->qr_code_path) : null;
    }

    public function getIsOverdueVerifikasiAttribute(): bool
    {
        if ($this->tanggal_verifikasi_fisik === null) {
            return true;
        }

        return $this->tanggal_verifikasi_fisik->lt(now()->subDays(90));
    }
}

