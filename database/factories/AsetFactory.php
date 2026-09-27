<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CaraPerolehan;
use App\Enums\KondisiAset;
use App\Models\Aset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aset>
 */
class AsetFactory extends Factory
{
    protected $model = Aset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $urut = str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);
        $tahun = fake()->numberBetween(2020, 2026);

        return [
            'kode_barang' => "02.06/{$urut}/{$tahun}",
            'nama' => fake()->randomElement([
                'Laptop ASUS ExpertBook',
                'Sepeda Motor Honda Vario 125',
                'Meja Rapat Kayu Jati',
                'Kursi Kerja Ergonomis',
                'Printer Epson L3210',
                'AC Split Panasonic 1.5 PK',
                'Proyektor Epson EB-X500',
            ]),
            'tahun_perolehan' => $tahun,
            'nilai' => fake()->randomFloat(2, 1000000, 25000000),
            'kondisi' => KondisiAset::BAIK,
            'lokasi' => fake()->randomElement([
                'Ruang Pelayanan Umum',
                'Ruang Sekretariat',
                'Ruang Camat',
                'Ruang Keuangan',
                'Aula Kecamatan',
            ]),
            'penanggung_jawab' => User::factory(),
            'merk_type' => fake()->word() . ' ' . fake()->randomNumber(3),
            'nomor_register' => 'REG-' . fake()->numerify('####'),
            'ukuran' => 'Standar',
            'bahan' => fake()->randomElement(['Kayu', 'Besi', 'Plastik', 'Aluminium']),
            'cara_perolehan' => CaraPerolehan::PEMBELIAN,
            'tanggal_verifikasi_fisik' => now()->toDateString(),
        ];
    }
}
