<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubKegiatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'kegiatan_rap_id' => ['required', 'integer', 'exists:kegiatan_rap,id'],
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['nullable', 'string', 'max:50'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kegiatan_rap_id.required' => 'Kegiatan induk wajib dipilih.',
            'kegiatan_rap_id.exists' => 'Kegiatan tidak ditemukan.',
            'nama.required' => 'Nama sub kegiatan wajib diisi.',
            'nama.max' => 'Nama sub kegiatan maksimal 255 karakter.',
        ];
    }
}
