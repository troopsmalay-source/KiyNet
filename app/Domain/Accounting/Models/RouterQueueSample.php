<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Network\Models\NasDevice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouterQueueSample extends Model
{
    protected $table = 'router_queue_samples';

    protected $fillable = [
        'nas_device_id', 'partner_id', 'queue_key', 'sampled_at',
        'current_rate_down_bps', 'current_rate_up_bps', 'bytes_down_total',
        'bytes_up_total', 'sample_interval_seconds', 'dedupe_key',
    ];

    protected function casts(): array
    {
        return [
            'sampled_at' => 'datetime',
            'current_rate_down_bps' => 'integer',
            'current_rate_up_bps' => 'integer',
            'bytes_down_total' => 'integer',
            'bytes_up_total' => 'integer',
            'sample_interval_seconds' => 'integer',
        ];
    }

    public function nasDevice(): BelongsTo
    {
        return $this->belongsTo(NasDevice::class);
    }
}
