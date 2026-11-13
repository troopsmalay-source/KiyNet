<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';

    protected $fillable = ['tagihan_id', 'payment_gateway_id', 'metode', 'nominal', 'nomor_referensi', 'idempotency_key', 'provider_transaction_id', 'bukti_transfer', 'kadaluarsa_at', 'dibayar_at', 'verified_by', 'status', 'provider_payload'];

    protected $casts = ['nominal' => 'decimal:2', 'kadaluarsa_at' => 'datetime', 'dibayar_at' => 'datetime', 'provider_payload' => 'array'];

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }
}
