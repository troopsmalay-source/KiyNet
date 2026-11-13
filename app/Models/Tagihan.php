<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tagihan extends Model
{
    protected $table = 'tagihan';

    protected $fillable = ['pelanggan_id', 'langganan_id', 'no_invoice', 'periode', 'idempotency_key', 'tanggal_tagihan', 'jatuh_tempo', 'akhir_masa_tenggang', 'subtotal', 'diskon', 'denda', 'total_tagihan', 'total_dibayar', 'status', 'paid_at', 'voided_at', 'void_reason'];

    protected $casts = ['tanggal_tagihan' => 'date', 'jatuh_tempo' => 'date', 'akhir_masa_tenggang' => 'date', 'paid_at' => 'datetime', 'voided_at' => 'datetime', 'total_tagihan' => 'decimal:2', 'total_dibayar' => 'decimal:2'];

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class);
    }

    public function langganan(): BelongsTo
    {
        return $this->belongsTo(Langganan::class);
    }

    public function detail(): HasMany
    {
        return $this->hasMany(TagihanDetail::class);
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(InvoiceAccessToken::class);
    }
}
