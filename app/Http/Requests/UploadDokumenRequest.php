<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\JenisDokumen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadDokumenRequest extends FormRequest
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
            'jenis_dokumen' => ['required', Rule::enum(JenisDokumen::class)],
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240', // Max 10MB per file
            ],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jenis_dokumen.required' => 'Jenis dokumen wajib dipilih (nota, kwitansi, faktur, kontrak, atau lainnya).',
            'file.required' => 'File dokumen bukti wajib dilampirkan.',
            'file.mimes' => 'Format file dokumen harus berupa PDF, JPG, JPEG, atau PNG.',
            'file.max' => 'Ukuran file dokumen bukti maksimal 10 MB.',
        ];
    }
}
