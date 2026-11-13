<?php

namespace App\Domain\Network\Models;

use App\Domain\Accounting\Models\RadiusSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NasDevice extends Model
{
    protected $table = 'nas_devices';

    protected $fillable = [
        'code', 'name', 'management_ip', 'nas_identifier', 'site_label', 'routeros_version',
        'model', 'status', 'accounting_enabled', 'interim_interval_seconds',
        'radius_secret_reference', 'api_secret_reference', 'last_seen_at',
    ];

    protected $hidden = ['radius_secret_reference', 'api_secret_reference'];

    protected function casts(): array
    {
        return [
            'accounting_enabled' => 'boolean',
            'interim_interval_seconds' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(NetworkAccessAssignment::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(RadiusSession::class);
    }
}
