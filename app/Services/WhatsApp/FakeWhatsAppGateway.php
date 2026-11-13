<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGateway;

class FakeWhatsAppGateway implements WhatsAppGateway
{
    /** @var list<array{recipient: string, template: string, variables: array<string, mixed>}> */
    public array $sent = [];

    public function sendTemplate(string $recipient, string $template, array $variables = []): array
    {
        $this->sent[] = ['recipient' => $recipient, 'template' => $template, 'variables' => $variables];

        return ['message_id' => 'fake-'.count($this->sent), 'status' => 'sent'];
    }
}
