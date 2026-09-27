<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Spj;
use Illuminate\Foundation\Http\FormRequest;

class VerifikasiSpjRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $spj = $this->route('spj');

        return $this->user() !== null && $spj instanceof Spj && $this->user()->can('verifikasi', $spj);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'approved' => ['required', 'boolean'],
            'catatan' => ['exclude_if:approved,true', 'required', 'string', 'min:10'],
        ];
    }

    /**
     * Custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'approved.required' => 'Keputusan verifikasi (setujui / tolak) wajib ditentukan.',
            'catatan.required' => 'Catatan verifikasi wajib diisi jika pengajuan SPJ ditolak (minimal 10 karakter).',
            'catatan.min' => 'Catatan penolakan harus memberikan alasan yang jelas (minimal 10 karakter).',
        ];
    }
}
