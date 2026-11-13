<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StorePartnerRequest extends FormRequest
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
            'nama_mitra' => ['required', 'string', 'max:150'],
            'nama_pic' => ['nullable', 'string', 'max:150'],
            'no_hp' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ];
    }
}
