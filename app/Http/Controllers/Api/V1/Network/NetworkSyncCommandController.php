<?php

namespace App\Http\Controllers\Api\V1\Network;

use App\Domain\Network\Models\RadiusSyncOutbox;
use App\Domain\Network\Services\NetworkAuthorizer;
use App\Domain\Network\Support\ApiEnvelope;
use App\Domain\Network\Support\ResolvesNetworkUser;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NetworkSyncCommandController extends Controller
{
    use ResolvesNetworkUser;

    public function __construct(private readonly NetworkAuthorizer $authorizer)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizer->assertCentral($this->networkUser($request));

        $validated = $request->validate([
            'status' => ['sometimes', 'in:pending,processing,succeeded,failed,dead_letter,superseded'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 25);
        $page = (int) ($validated['page'] ?? 1);

        $builder = RadiusSyncOutbox::query()->when(
            isset($validated['status']),
            fn ($query) => $query->where('status', $validated['status']),
        );

        $total = (clone $builder)->count();

        $commands = $builder->orderByDesc('id')->forPage($page, $perPage)->get();

        return ApiEnvelope::paginated(
            $commands->map(fn (RadiusSyncOutbox $command) => [
                'id' => (string) $command->id,
                'command_type' => $command->command_type,
                'aggregate_type' => $command->aggregate_type,
                'aggregate_id' => (string) $command->aggregate_id,
                'policy_version' => (int) $command->policy_version,
                'status' => $command->status,
                'attempts' => (int) $command->attempts,
                'available_at' => $command->available_at?->toIso8601ZuluString(),
                'processed_at' => $command->processed_at?->toIso8601ZuluString(),
                'last_error' => $command->last_error,
            ])->all(),
            $page,
            $perPage,
            $total,
            (int) ceil($total / max(1, $perPage)),
        );
    }

    public function retry(Request $request, int $command): JsonResponse
    {
        $this->authorizer->assertCentral($this->networkUser($request));

        /** @var RadiusSyncOutbox|null $outbox */
        $outbox = RadiusSyncOutbox::query()->find($command);

        if ($outbox === null) {
            abort(404, 'Perintah sinkronisasi tidak ditemukan.');
        }

        if (! in_array($outbox->status, [RadiusSyncOutbox::FAILED, RadiusSyncOutbox::DEAD_LETTER], true)) {
            abort(409, 'Hanya perintah gagal/dead-letter yang bisa di-retry.');
        }

        $outbox->status = RadiusSyncOutbox::PENDING;
        $outbox->attempts = 0;
        $outbox->available_at = CarbonImmutable::now('UTC');
        $outbox->last_error = null;
        $outbox->save();

        return ApiEnvelope::ok([
            'command_id' => (string) $outbox->id,
            'sync_status' => $outbox->status,
        ], status: 202);
    }
}
