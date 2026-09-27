<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAktivitas extends Model
{
    use HasFactory;

    protected $table = 'log_aktivitas';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'aksi',
        'tabel_terkait',
        'record_id',
        'keterangan',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'record_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Static helper to log an activity.
     */
    public static function catat(?int $userId, string $aksi, string $tabelTerkait, int $recordId, ?string $keterangan = null): self
    {
        return static::create([
            'user_id' => $userId,
            'aksi' => $aksi,
            'tabel_terkait' => $tabelTerkait,
            'record_id' => $recordId,
            'keterangan' => $keterangan,
            'created_at' => now(),
        ]);
    }
}
