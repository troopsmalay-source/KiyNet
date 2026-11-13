<?php

namespace App\Domain\Accounting\Models;

use App\Domain\Network\Models\NasDevice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadiusSession extends Model
{
    protected $table = 'radius_sessions';

    public const ACTIVE = 'active';

    public const CLOSED = 'closed';

    public const STALE = 'stale';

    protected $fillable = [
        'nas_device_id', 'subscription_id', 'customer_id', 'partner_id', 'username',
        'acct_session_id', 'acct_unique_id', 'framed_ip_address', 'start_at',
        'last_sample_at', 'stop_at', 'session_seconds', 'input_bytes_64',
        'output_bytes_64', 'terminate_cause', 'status', 'estimated_close',
        'close_reason', 'raw_record_reference',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'last_sample_at' => 'datetime',
            'stop_at' => 'datetime',
            'session_seconds' => 'integer',
            'input_bytes_64' => 'integer',
            'output_bytes_64' => 'integer',
            'estimated_close' => 'boolean',
        ];
    }

    public function nasDevice(): BelongsTo
    {
        return $this->belongsTo(NasDevice::class);
    }

    public function samples(): HasMany
    {
        return $this->hasMany(RadiusAccountingSample::class);
    }

    public function sessionKey(): string
    {
        return $this->nas_device_id.':'.$this->acct_session_id.':'.$this->start_at?->getTimestamp();
    }
}
