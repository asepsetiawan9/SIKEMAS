<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JenisBelanja;
use App\Enums\JenisDokumen;
use App\Enums\StatusDokumen;
use App\Enums\StatusVerifikasi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Belanja extends Model
{
    use HasFactory;

    protected $table = 'belanja';

    /** @var list<string> */
    protected $fillable = [
        'sub_kegiatan_id',
        'jenis_belanja',
        'uraian',
        'spesifikasi',
        'harga_satuan',
        'volume',
        'satuan',
        'total_nilai',
        'tanggal_belanja',
        'penerima',
        'nomor_bukti_manual',
        'keterangan',
        'status_dokumen',
        'status_verifikasi',
        'catatan_sekmat',
        'catatan_camat',
        'diajukan_pada',
        'diverifikasi_sekmat_pada',
        'disetujui_camat_pada',
        'created_by',
        'diverifikasi_oleh',
        'disetujui_oleh',
    ];

    /** @var list<string> */
    protected $appends = [
        'nominal',
        'catatan',
        'dokumen_checklist',
        'dokumen_count',
        'breadcrumb',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'jenis_belanja' => JenisBelanja::class,
            'status_dokumen' => StatusDokumen::class,
            'status_verifikasi' => StatusVerifikasi::class,
            'harga_satuan' => 'decimal:2',
            'volume' => 'decimal:2',
            'total_nilai' => 'decimal:2',
            'tanggal_belanja' => 'date',
            'diajukan_pada' => 'datetime',
            'diverifikasi_sekmat_pada' => 'datetime',
            'disetujui_camat_pada' => 'datetime',
        ];
    }

    // ── Relasi ──

    /** @return BelongsTo<SubKegiatan, $this> */
    public function subKegiatan(): BelongsTo
    {
        return $this->belongsTo(SubKegiatan::class, 'sub_kegiatan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    /** @return HasMany<DokumenBukti, $this> */
    public function dokumenBukti(): HasMany
    {
        return $this->hasMany(DokumenBukti::class, 'belanja_id');
    }

    /** @return HasMany<RiwayatProses, $this> */
    public function riwayatProses(): HasMany
    {
        return $this->hasMany(RiwayatProses::class, 'belanja_id')->orderBy('created_at', 'desc');
    }

    // ── Computed Properties ──

    /**
     * Hitung status kelengkapan dokumen secara otomatis.
     * Lengkap = minimal ada Nota DAN Kwitansi (BR-DOK-05).
     */
    public function hitungStatusDokumen(): StatusDokumen
    {
        $jenisYangAda = $this->dokumenBukti()
            ->get()
            ->map(fn ($d) => $d->jenis_dokumen instanceof \BackedEnum ? $d->jenis_dokumen->value : (string) $d->jenis_dokumen)
            ->unique()
            ->toArray();

        $hasNota = in_array(JenisDokumen::NOTA->value, $jenisYangAda, true);
        $hasKwitansi = in_array(JenisDokumen::KWITANSI->value, $jenisYangAda, true);

        return ($hasNota && $hasKwitansi)
            ? StatusDokumen::LENGKAP
            : StatusDokumen::BELUM_LENGKAP;
    }

    /**
     * Sinkronkan status_dokumen berdasarkan dokumen yang ada.
     */
    public function syncStatusDokumen(): void
    {
        $this->update(['status_dokumen' => $this->hitungStatusDokumen()]);
    }

    /**
     * Apakah belanja ini bisa diedit oleh operator?
     */
    public function isEditable(): bool
    {
        return $this->status_verifikasi->isEditable();
    }

    /**
     * Apakah dokumen terkunci (status final)?
     */
    public function isDokumenLocked(): bool
    {
        return $this->status_verifikasi->isFinal();
    }

    /**
     * Ringkasan checklist dokumen per jenis standar.
     *
     * @return array<string, bool>
     */
    public function getDokumenChecklistAttribute(): array
    {
        $existing = $this->dokumenBukti()
            ->get()
            ->map(fn ($d) => $d->jenis_dokumen instanceof \BackedEnum ? $d->jenis_dokumen->value : (string) $d->jenis_dokumen)
            ->unique()
            ->toArray();

        $checklist = [];
        foreach (JenisDokumen::standar() as $jenis) {
            $checklist[$jenis->value] = in_array($jenis->value, $existing, true);
        }

        return $checklist;
    }

    /**
     * Jumlah total dokumen bukti.
     */
    public function getDokumenCountAttribute(): int
    {
        return $this->dokumenBukti()->count();
    }

    /**
     * Total nilai alias nominal untuk kemudahan komponen UI.
     */
    public function getNominalAttribute(): float
    {
        return (float) ($this->total_nilai ?? 0);
    }

    /**
     * Catatan aktif dari Sekmat atau Camat.
     */
    public function getCatatanAttribute(): ?string
    {
        return $this->catatan_sekmat ?? $this->catatan_camat;
    }

    /**
     * Navigasi hierarki: Program → Kegiatan → Sub Kegiatan.
     */
    public function getBreadcrumbAttribute(): string
    {
        $sub = $this->subKegiatan;
        $keg = $sub?->kegiatanRap;
        $prog = $keg?->program;

        return implode(' → ', array_filter([
            $prog?->kode,
            $keg?->kode,
            $sub?->kode,
        ]));
    }
}
