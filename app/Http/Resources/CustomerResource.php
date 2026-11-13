<?php

namespace App\Http\Resources;

use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Pelanggan */
class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode_pelanggan' => $this->kode_pelanggan,
            'partner_id' => $this->mitra_id,
            'nama_pelanggan' => $this->nama_pelanggan,
            'no_hp' => $this->no_hp,
            'email' => $this->email,
            'alamat_pasang' => $this->alamat_pasang,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'nik' => $this->nik,
            'status' => $this->status,
            'catatan' => $this->catatan,
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
