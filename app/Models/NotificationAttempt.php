<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationAttempt extends Model
{
    protected $fillable = ['notification_outbox_id', 'attempt', 'status', 'response'];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(NotificationOutbox::class, 'notification_outbox_id');
    }
}
