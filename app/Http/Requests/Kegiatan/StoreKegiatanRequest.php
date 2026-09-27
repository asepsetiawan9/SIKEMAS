<?php

declare(strict_types=1);

namespace App\Http\Requests\Kegiatan;

use App\Enums\StatusKegiatan;
use App\Enums\SumberDana;
use App\Models\Kegiatan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKegiatanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('create', Kegiatan::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'pagu' => ['required', 'numeric', 'min:0'],
            'tahun_anggaran' => ['required', 'integer', 'min:2020', 'max:2099'],
            'kode_rekening' => ['required', 'string', 'max:100'],
            'sumber_dana' => ['required', Rule::enum(SumberDana::class)],
            'kasi_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::enum(StatusKegiatan::class)],
            'deskripsi' => ['nullable', 'string'],
            'periode_mulai' => ['nullable', 'date'],
            'periode_selesai' => ['nullable', 'date', 'after_or_equal:periode_mulai'],
        ];
    }

    /**
     * Custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kegiatan wajib diisi.',
            'pagu.required' => 'Pagu anggaran wajib diisi.',
            'pagu.numeric' => 'Pagu anggaran harus berupa angka nominal yang valid.',
            'pagu.min' => 'Pagu anggaran tidak boleh bernilai negatif.',
            'tahun_anggaran.required' => 'Tahun anggaran wajib diisi.',
            'kode_rekening.required' => 'Kode rekening kegiatan wajib diisi.',
            'sumber_dana.required' => 'Sumber dana wajib dipilih.',
            'kasi_id.required' => 'Penanggung jawab (Kasi) wajib dipilih.',
            'kasi_id.exists' => 'Penanggung jawab yang dipilih tidak ditemukan dalam sistem.',
            'periode_selesai.after_or_equal' => 'Periode selesai harus sama dengan atau setelah tanggal periode mulai.',
        ];
    }
}
