<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogger
{
    /**
     * Record a privileged business change. Call inside the same DB transaction
     * as the change so an audit row never describes a rolled-back mutation.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function log(?User $actor, string $action, string $module, ?string $description = null, array $metadata = [], ?Request $request = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $actor?->id,
            'aksi' => $action,
            'modul' => $module,
            'deskripsi' => $description,
            'ip_address' => $request?->ip(),
            'metadata' => $metadata,
        ]);
    }
}
