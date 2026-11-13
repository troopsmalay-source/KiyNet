<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationOutbox extends Model
{
    protected $table = 'notification_outbox';

    protected $fillable = ['channel', 'recipient', 'template', 'variables', 'business_reference', 'idempotency_key', 'status', 'attempts', 'available_at', 'locked_at', 'sent_at', 'last_error'];

    protected $casts = ['variables' => 'array', 'available_at' => 'datetime', 'locked_at' => 'datetime', 'sent_at' => 'datetime'];

    public function attempts(): HasMany
    {
        return $this->hasMany(NotificationAttempt::class);
    }
}
