<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\WhatsAppGateway;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WhatsAppTestController extends Controller
{
    public function __construct(
        private readonly WhatsAppGateway $gateway,
        private readonly AuditLogger $audit,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('integration.whatsapp.test');

        $data = $request->validate([
            'recipient' => ['required', 'string', 'max:30'],
            'template' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->gateway->sendTemplate(
            $data['recipient'],
            $data['template'] ?? 'test_message',
            ['message' => 'KiyNet integration test'],
        );

        $this->audit->log($request->user(), 'integration.whatsapp.test', 'integration', 'WhatsApp test send', [
            'recipient' => $data['recipient'],
            'status' => $result['status'],
        ], $request);

        return ApiResponse::ok($result, 200, $request->header('X-Request-ID', ''));
    }
}
