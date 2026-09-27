<?php

declare(strict_types=1);

namespace App\Http\Requests\Aset;

use App\Enums\CaraPerolehan;
use App\Enums\KondisiAset;
use App\Models\Aset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAsetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Aset|null $aset */
        $aset = $this->route('aset');

        return $aset !== null && ($this->user()?->can('update', $aset) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Aset|null $aset */
        $aset = $this->route('aset');
        $asetId = $aset?->id;

        return [
            'kode_barang' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('aset', 'kode_barang')->ignore($asetId),
                'regex:/^\d{2}\.\d{2}\/\d{4}\/\d{4}$/',
            ],
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'lokasi' => ['sometimes', 'required', 'string', 'max:255'],
            'tahun_perolehan' => ['sometimes', 'required', 'integer', 'digits:4', 'min:1900', 'max:' . (date('Y') + 1)],
            'nilai' => ['sometimes', 'required', 'numeric', 'min:0'],
            'kondisi' => ['sometimes', 'required', Rule::enum(KondisiAset::class)],
            'penanggung_jawab' => ['sometimes', 'required', 'exists:users,id'],
            'cara_perolehan' => ['sometimes', 'required', Rule::enum(CaraPerolehan::class)],
            'merk_type' => ['nullable', 'string', 'max:255'],
            'nomor_register' => ['nullable', 'string', 'max:255'],
            'ukuran' => ['nullable', 'string', 'max:255'],
            'bahan' => ['nullable', 'string', 'max:255'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode_barang.required' => 'Kode barang wajib diisi.',
            'kode_barang.unique' => 'Kode barang sudah terdaftar pada sistem.',
            'kode_barang.regex' => 'Format kode barang harus sesuai standar: {GOLONGAN}.{SUB}/{URUT_4DIGIT}/{TAHUN} (Contoh: 02.06/0012/2024).',
            'nama.required' => 'Nama barang wajib diisi.',
            'lokasi.required' => 'Lokasi penempatan barang wajib diisi.',
            'tahun_perolehan.required' => 'Tahun perolehan wajib diisi.',
            'tahun_perolehan.digits' => 'Tahun perolehan harus 4 digit angka.',
            'nilai.required' => 'Nilai aset wajib diisi.',
            'nilai.min' => 'Nilai aset tidak boleh kurang dari 0.',
            'kondisi.required' => 'Kondisi aset wajib dipilih.',
            'penanggung_jawab.required' => 'Penanggung jawab wajib dipilih.',
            'penanggung_jawab.exists' => 'Penanggung jawab yang dipilih tidak valid.',
            'cara_perolehan.required' => 'Cara perolehan aset wajib dipilih.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.mimes' => 'Format gambar yang diperbolehkan: jpg, jpeg, png.',
            'foto.max' => 'Ukuran foto maksimal 2MB.',
        ];
    }
}
