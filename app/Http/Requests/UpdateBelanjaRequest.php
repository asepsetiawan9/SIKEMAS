<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\JenisBelanja;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBelanjaRequest extends FormRequest
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
            'sub_kegiatan_id' => ['sometimes', 'required', 'integer', 'exists:sub_kegiatan,id'],
            'uraian' => ['sometimes', 'required', 'string', 'max:500'],
            'jenis_belanja' => ['sometimes', 'required', Rule::enum(JenisBelanja::class)],
            'nominal' => ['sometimes', 'required', 'numeric', 'min:1', 'max:9999999999999.99'],
            'tanggal_belanja' => ['sometimes', 'required', 'date'],
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
            'uraian.required' => 'Uraian belanja wajib diisi.',
            'nominal.required' => 'Nominal belanja wajib diisi.',
            'tanggal_belanja.required' => 'Tanggal belanja wajib diisi.',
        ];
    }
}
