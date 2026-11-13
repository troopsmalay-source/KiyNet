<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerBandwidthContractEvent extends Model
{
    protected $fillable = ['partner_bandwidth_contract_id', 'type', 'requested_mbps', 'reason', 'status', 'requested_by', 'decided_by', 'decided_at'];

    protected $casts = ['decided_at' => 'datetime'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(PartnerBandwidthContract::class, 'partner_bandwidth_contract_id');
    }
}
