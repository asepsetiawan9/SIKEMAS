<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\JenisBelanja;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBelanjaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'sub_kegiatan_id' => ['required', 'integer', 'exists:sub_kegiatan,id'],
            'uraian' => ['required', 'string', 'max:500'],
            'jenis_belanja' => ['required', Rule::enum(JenisBelanja::class)],
            'nominal' => ['required', 'numeric', 'min:1', 'max:9999999999999.99'],
            'tanggal_belanja' => ['required', 'date'],
            'penerima' => ['nullable', 'string', 'max:255'],
            'nomor_bukti_manual' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sub_kegiatan_id.required' => 'Sub kegiatan wajib dipilih.',
            'sub_kegiatan_id.exists' => 'Sub kegiatan tidak ditemukan.',
            'uraian.required' => 'Uraian belanja wajib diisi.',
            'uraian.max' => 'Uraian belanja maksimal 500 karakter.',
            'jenis_belanja.required' => 'Jenis belanja wajib dipilih (cetak, mamin, perdin, atk, dll).',
            'nominal.required' => 'Nominal belanja wajib diisi.',
            'nominal.min' => 'Nominal belanja minimal Rp 1.',
            'tanggal_belanja.required' => 'Tanggal belanja wajib diisi.',
            'tanggal_belanja.date' => 'Format tanggal belanja tidak valid.',
        ];
    }
}
