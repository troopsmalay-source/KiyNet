<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookLog extends Model
{
    protected $table = 'payment_webhook_logs';

    protected $fillable = ['provider', 'provider_event_id', 'event_type', 'payload', 'status_proses', 'error_message', 'processed_at'];

    protected $casts = ['payload' => 'array', 'processed_at' => 'datetime'];
}
