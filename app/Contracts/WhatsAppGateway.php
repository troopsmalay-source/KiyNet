<?php

namespace App\Contracts;

interface WhatsAppGateway
{
    /**
     * @param  array<string, string|int>  $variables
     * @return array{message_id: ?string, status: string}
     */
    public function sendTemplate(string $recipient, string $template, array $variables = []): array;
}
