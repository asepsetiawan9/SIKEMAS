<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JenisKibKir;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KibKir extends Model
{
    use HasFactory;

    protected $table = 'kib_kir';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'aset_id',
        'jenis',
        'file_pdf',
        'dibuat_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => JenisKibKir::class,
        ];
    }

    /**
     * @return BelongsTo<Aset, $this>
     */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
