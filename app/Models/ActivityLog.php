<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit trail. Reuses the legacy activity_logs table (see docs/REPO_AUDIT.md
 * migration map) so there is a single audit source of truth.
 */
class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = ['user_id', 'aksi', 'modul', 'deskripsi', 'ip_address', 'metadata'];

    protected $casts = ['metadata' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
