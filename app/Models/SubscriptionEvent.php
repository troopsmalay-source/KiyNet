<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionEvent extends Model
{
    protected $fillable = ['langganan_id', 'type', 'from_paket_internet_id', 'to_paket_internet_id', 'effective_date', 'actor_id', 'metadata'];

    protected $casts = ['effective_date' => 'date', 'metadata' => 'array'];

    public function langganan(): BelongsTo
    {
        return $this->belongsTo(Langganan::class);
    }
}
