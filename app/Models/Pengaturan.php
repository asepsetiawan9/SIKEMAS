<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    use HasFactory;

    protected $table = 'pengaturan';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'keterangan',
    ];

    /**
     * Get a setting value by key with optional default.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set or update a setting value.
     */
    public static function setValue(string $key, string $value, ?string $keterangan = null): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'keterangan' => $keterangan,
            ]
        );
    }
}
