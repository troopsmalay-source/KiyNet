<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerBandwidthContract extends Model
{
    protected $fillable = ['mitra_id', 'committed_mbps', 'price_idr_per_mbps', 'starts_at', 'ends_at', 'status', 'approved_by', 'approved_at'];

    protected $casts = ['starts_at' => 'date', 'ends_at' => 'date', 'approved_at' => 'datetime'];

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PartnerBandwidthContractEvent::class);
    }
}
