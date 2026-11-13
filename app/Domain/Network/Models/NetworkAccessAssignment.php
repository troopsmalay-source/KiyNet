<?php

namespace App\Domain\Network\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkAccessAssignment extends Model
{
    protected $table = 'network_access_assignments';

    protected $fillable = [
        'subscription_id', 'customer_id', 'partner_id', 'nas_device_id', 'profile_id',
        'pppoe_username', 'credential_reference', 'ip_pool', 'desired_state',
        'applied_state', 'policy_version', 'last_sync_at', 'last_error',
    ];

    protected function casts(): array
    {
        return [
            'policy_version' => 'integer',
            'last_sync_at' => 'datetime',
        ];
    }

    public function nasDevice(): BelongsTo
    {
        return $this->belongsTo(NasDevice::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(NetworkAccessProfile::class, 'profile_id');
    }
}
