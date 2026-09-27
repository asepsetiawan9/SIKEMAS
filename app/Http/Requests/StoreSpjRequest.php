<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Spj;
use Illuminate\Foundation\Http\FormRequest;

class StoreSpjRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('create', Spj::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kegiatan_id' => ['required', 'integer', 'exists:kegiatan,id'],
            'nominal' => ['required', 'numeric', 'min:1'],
            'periode_bulan' => ['required', 'integer', 'between:1,12'],
            'periode_tahun' => ['required', 'integer', 'min:2020', 'max:2099'],
            'jenis_belanja' => ['nullable', 'string', 'max:255'],
            'file_bukti' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // Max 5MB
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
            'kegiatan_id.required' => 'Kegiatan anggaran wajib dipilih.',
            'kegiatan_id.exists' => 'Kegiatan yang dipilih tidak ditemukan dalam sistem.',
            'nominal.required' => 'Nominal pengajuan wajib diisi.',
            'nominal.min' => 'Nominal pengajuan minimal Rp 1,00.',
            'periode_bulan.required' => 'Bulan periode anggaran wajib dipilih.',
            'periode_bulan.between' => 'Bulan periode anggaran tidak valid (1-12).',
            'periode_tahun.required' => 'Tahun periode anggaran wajib diisi.',
            'file_bukti.required' => 'Dokumen bukti pertanggungjawaban wajib diunggah.',
            'file_bukti.mimes' => 'Format file bukti harus berupa PDF, JPG, JPEG, atau PNG.',
            'file_bukti.max' => 'Ukuran file bukti maksimal 5 MB.',
        ];
    }
}
