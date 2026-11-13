<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiusAccountingSample extends Model
{
    protected $table = 'radius_accounting_samples';

    protected $fillable = [
        'radius_session_id', 'nas_device_id', 'username', 'session_key', 'event_type',
        'sampled_at', 'input_total_bytes', 'output_total_bytes', 'input_delta_bytes',
        'output_delta_bytes', 'delta_valid', 'counter_reset_detected', 'out_of_order',
        'dedupe_key', 'source_event_id', 'ingested_at',
    ];

    protected function casts(): array
    {
        return [
            'sampled_at' => 'datetime',
            'ingested_at' => 'datetime',
            'input_total_bytes' => 'integer',
            'output_total_bytes' => 'integer',
            'input_delta_bytes' => 'integer',
            'output_delta_bytes' => 'integer',
            'delta_valid' => 'boolean',
            'counter_reset_detected' => 'boolean',
            'out_of_order' => 'boolean',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(RadiusSession::class, 'radius_session_id');
    }
}
