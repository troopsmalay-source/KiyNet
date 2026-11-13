<?php

namespace App\Http\Resources;

use App\Models\Mitra;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Mitra */
class PartnerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode_mitra' => $this->kode_mitra,
            'nama_mitra' => $this->nama_mitra,
            'nama_pic' => $this->nama_pic,
            'no_hp' => $this->no_hp,
            'email' => $this->email,
            'alamat' => $this->alamat,
            'status' => $this->status,
            'customers_count' => $this->whenCounted('pelanggan'),
        ];
    }
}
