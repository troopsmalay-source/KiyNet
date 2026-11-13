<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRefund extends Model
{
    protected $fillable = ['pembayaran_id', 'amount_idr', 'reason', 'status', 'requested_by', 'approved_by', 'processed_at'];

    protected $casts = ['processed_at' => 'datetime'];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'pembayaran_id');
    }
}
