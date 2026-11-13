<?php

namespace App\Services\Notifications;

use App\Models\NotificationOutbox;

class NotificationOutboxService
{
    /**
     * Enqueue a notification inside the caller's transaction. The idempotency
     * key makes retries safe when the same business event is replayed.
     *
     * @param  array<string, mixed>  $variables
     */
    public function enqueue(
        string $recipient,
        string $template,
        array $variables,
        string $businessReference,
        string $idempotencyKey,
        string $channel = 'whatsapp',
    ): NotificationOutbox {
        return NotificationOutbox::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'channel' => $channel,
                'recipient' => $recipient,
                'template' => $template,
                'variables' => $variables,
                'business_reference' => $businessReference,
                'status' => 'pending',
                'available_at' => now(),
            ],
        );
    }
}
