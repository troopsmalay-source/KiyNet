<?php

namespace App\Domain\Network\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NetworkAccessProfile extends Model
{
    protected $table = 'network_access_profiles';

    protected $fillable = [
        'code', 'name', 'download_mbps', 'upload_mbps', 'mikrotik_rate_limit',
        'is_isolation', 'status', 'version', 'effective_from',
    ];

    protected function casts(): array
    {
        return [
            'download_mbps' => 'integer',
            'upload_mbps' => 'integer',
            'is_isolation' => 'boolean',
            'version' => 'integer',
            'effective_from' => 'datetime',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(NetworkAccessAssignment::class, 'profile_id');
    }

    /**
     * Vendor attribute derived from the profile. Direction (rx/tx) must be
     * proven against the target RouterOS version in the lab.
     */
    public function rateLimit(): string
    {
        if (filled($this->mikrotik_rate_limit)) {
            return (string) $this->mikrotik_rate_limit;
        }

        return str_replace(
            [':upload', ':download'],
            [$this->upload_mbps, $this->download_mbps],
            (string) config('network.rate_limit.template', ':uploadM/:downloadM'),
        );
    }
}
