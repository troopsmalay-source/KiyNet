<?php

namespace App\Domain\Network\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkPolicyAudit extends Model
{
    protected $table = 'network_policy_audits';

    protected $fillable = [
        'actor_id', 'actor_type', 'entity_type', 'entity_id', 'action',
        'old_policy_version', 'new_policy_version', 'old_profile_code',
        'new_profile_code', 'result', 'nas_device_id', 'reason', 'correlation_id',
    ];

    protected function casts(): array
    {
        return [
            'old_policy_version' => 'integer',
            'new_policy_version' => 'integer',
        ];
    }
}
