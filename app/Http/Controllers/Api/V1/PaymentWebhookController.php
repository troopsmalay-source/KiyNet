<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Billing\Services\PaymentWebhookService;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(private readonly PaymentWebhookService $webhooks) {}

    public function __invoke(Request $request, string $provider): JsonResponse
    {
        $result = $this->webhooks->handle($provider, $request);

        return ApiResponse::ok($result, 200, $request->header('X-Request-ID', ''));
    }
}
