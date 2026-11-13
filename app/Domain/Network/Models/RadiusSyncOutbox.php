<?php

namespace App\Domain\Network\Models;

use Illuminate\Database\Eloquent\Model;

class RadiusSyncOutbox extends Model
{
    protected $table = 'radius_sync_outbox';

    public const PENDING = 'pending';

    public const PROCESSING = 'processing';

    public const SUCCEEDED = 'succeeded';

    public const FAILED = 'failed';

    public const DEAD_LETTER = 'dead_letter';

    public const SUPERSEDED = 'superseded';

    protected $fillable = [
        'aggregate_type', 'aggregate_id', 'partner_id', 'command_type', 'payload_json',
        'policy_version', 'idempotency_key', 'payload_hash', 'correlation_id', 'status',
        'attempts', 'available_at', 'locked_at', 'processed_at', 'last_error',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'policy_version' => 'integer',
            'attempts' => 'integer',
            'available_at' => 'datetime',
            'locked_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::PENDING, self::FAILED], true);
    }
}
