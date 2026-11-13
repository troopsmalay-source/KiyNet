<?php

namespace App\Http\Controllers\Api\V1\Network;

use App\Domain\Accounting\Readers\EloquentNetworkAnalyticsReader;
use App\Domain\Accounting\Models\RadiusSession;
use App\Domain\Network\Dto\NetworkDisconnectCommand;
use App\Domain\Network\Dto\SessionQuery;
use App\Domain\Network\Exceptions\IdempotencyConflict;
use App\Domain\Network\Services\NetworkAuthorizer;
use App\Domain\Network\Services\NetworkOutboxDispatcher;
use App\Domain\Network\Support\ApiEnvelope;
use App\Domain\Network\Support\ResolvesNetworkUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NetworkSessionController extends Controller
{
    use ResolvesNetworkUser;

    public function __construct(
        private readonly NetworkAuthorizer $authorizer,
        private readonly EloquentNetworkAnalyticsReader $reader,
        private readonly NetworkOutboxDispatcher $dispatcher,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $scope = $this->authorizer->scopeFor($this->networkUser($request));

        $validated = $request->validate([
            'active' => ['sometimes', 'boolean'],
            'customer_id' => ['sometimes', 'integer', 'min:1'],
            'partner_id' => ['sometimes', 'integer', 'min:1'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->reader->activeSessions(new SessionQuery(
            scope: $scope,
            activeOnly: (bool) ($validated['active'] ?? true),
            customerId: isset($validated['customer_id']) ? (int) $validated['customer_id'] : null,
            partnerId: isset($validated['partner_id']) ? (int) $validated['partner_id'] : null,
            page: (int) ($validated['page'] ?? 1),
            perPage: (int) ($validated['per_page'] ?? 25),
        ));

        return ApiEnvelope::paginated(
            $result->items,
            $result->page,
            $result->perPage,
            $result->total,
            $result->lastPage(),
        );
    }

    public function disconnect(Request $request, int $session): JsonResponse
    {
        $user = $this->networkUser($request);
        $this->authorizer->assertCentral($user);

        $validated = $request->validate([
            'reason_code' => ['required', 'string', 'max:60'],
            'reason_note' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var RadiusSession|null $target */
        $target = RadiusSession::query()->find($session);

        if ($target === null) {
            abort(404, 'Sesi tidak ditemukan.');
        }

        if ($target->stop_at !== null) {
            abort(409, 'Sesi sudah berhenti.');
        }

        $idempotencyKey = (string) ($request->header('Idempotency-Key')
            ?: 'disconnect:'.$target->id.':'.$validated['reason_code'].':'.$user->getKey());

        try {
            $command = $this->dispatcher->enqueueDisconnect(new NetworkDisconnectCommand(
                sessionId: (int) $target->id,
                username: $target->username,
                nasDeviceId: (int) $target->nas_device_id,
                reasonCode: $validated['reason_code'],
                reasonNote: $validated['reason_note'] ?? null,
                correlationId: (string) ($request->header('X-Request-Id') ?: Str::uuid()),
                idempotencyKey: $idempotencyKey,
            ));
        } catch (IdempotencyConflict $exception) {
            abort(409, $exception->getMessage());
        }

        return ApiEnvelope::ok([
            'command_id' => (string) $command->id,
            'desired_state' => 'disconnected',
            'sync_status' => $command->status,
        ], status: 202);
    }
}
