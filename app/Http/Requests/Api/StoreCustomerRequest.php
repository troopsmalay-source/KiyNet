<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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
            // mitra_id is only honoured for central users; the controller
            // overwrites it from the principal for partner users.
            'mitra_id' => ['nullable', 'integer', 'exists:mitra,id'],
            'nama_pelanggan' => ['required', 'string', 'max:150'],
            'no_hp' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat_pasang' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'nik' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'in:calon,aktif,isolir,nonaktif'],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
