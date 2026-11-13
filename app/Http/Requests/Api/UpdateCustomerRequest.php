<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mitra_id' => ['nullable', 'integer', 'exists:mitra,id'],
            'nama_pelanggan' => ['sometimes', 'required', 'string', 'max:150'],
            'no_hp' => ['sometimes', 'required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat_pasang' => ['sometimes', 'required', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'nik' => ['nullable', 'string', 'max:30'],
            'status' => ['sometimes', 'required', 'in:calon,aktif,isolir,nonaktif'],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
