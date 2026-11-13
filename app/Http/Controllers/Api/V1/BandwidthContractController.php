<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\PartnerBandwidthContract;
use App\Models\PartnerBandwidthContractEvent;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BandwidthContractController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request, int $partner): JsonResponse
    {
        Gate::authorize('partner.contract.view');

        $mitra = $this->scopedPartner($request, $partner);

        $contracts = PartnerBandwidthContract::query()
            ->where('mitra_id', $mitra->id)
            ->with('events')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PartnerBandwidthContract $contract) => $this->payload($contract));

        return ApiResponse::ok($contracts->all(), 200, $request->header('X-Request-ID', ''));
    }

    public function store(Request $request, int $partner): JsonResponse
    {
        Gate::authorize('partner.contract.manage');

        if (! TenantContext::isCentral($request->user())) {
            abort(403);
        }

        $mitra = Mitra::query()->findOrFail($partner);

        $data = $request->validate([
            'committed_mbps' => ['required', 'integer', 'min:1'],
            'price_idr_per_mbps' => ['required', 'integer', 'min:0'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'status' => ['required', 'in:draft,active'],
        ]);

        $contract = DB::transaction(function () use ($request, $mitra, $data): PartnerBandwidthContract {
            $contract = PartnerBandwidthContract::create($data + [
                'mitra_id' => $mitra->id,
                'approved_by' => $data['status'] === 'active' ? $request->user()->id : null,
                'approved_at' => $data['status'] === 'active' ? now() : null,
            ]);

            $this->audit->log($request->user(), 'partner.contract.create', 'partner', 'Bandwidth contract created', [
                'partner_id' => $mitra->id,
                'contract_id' => $contract->id,
                'committed_mbps' => $contract->committed_mbps,
            ], $request);

            return $contract;
        });

        return ApiResponse::ok($this->payload($contract->load('events')), 201, $request->header('X-Request-ID', ''));
    }

    public function update(Request $request, int $partner, int $contract): JsonResponse
    {
        Gate::authorize('partner.contract.manage');

        if (! TenantContext::isCentral($request->user())) {
            abort(403);
        }

        $mitra = Mitra::query()->findOrFail($partner);
        $model = PartnerBandwidthContract::query()->where('mitra_id', $mitra->id)->findOrFail($contract);

        $data = $request->validate([
            'committed_mbps' => ['sometimes', 'integer', 'min:1'],
            'price_idr_per_mbps' => ['sometimes', 'integer', 'min:0'],
            'ends_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:draft,active,expired,terminated'],
        ]);

        DB::transaction(function () use ($request, $model, $data): void {
            $model->update($data);

            $this->audit->log($request->user(), 'partner.contract.update', 'partner', 'Bandwidth contract updated', [
                'contract_id' => $model->id,
                'changes' => $data,
            ], $request);
        });

        return ApiResponse::ok($this->payload($model->fresh('events')), 200, $request->header('X-Request-ID', ''));
    }

    public function capacityRequest(Request $request, int $partner, int $contract): JsonResponse
    {
        Gate::authorize('partner.contract.request');

        $mitra = $this->scopedPartner($request, $partner);
        $model = PartnerBandwidthContract::query()->where('mitra_id', $mitra->id)->findOrFail($contract);

        $data = $request->validate([
            'requested_mbps' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $event = DB::transaction(function () use ($request, $model, $data): PartnerBandwidthContractEvent {
            $event = $model->events()->create([
                'type' => 'capacity_change_requested',
                'requested_mbps' => $data['requested_mbps'],
                'reason' => $data['reason'],
                'status' => 'pending',
                'requested_by' => $request->user()->id,
            ]);

            $this->audit->log($request->user(), 'partner.contract.capacity_request', 'partner', 'Capacity change requested', [
                'contract_id' => $model->id,
                'requested_mbps' => $data['requested_mbps'],
            ], $request);

            return $event;
        });

        return ApiResponse::ok([
            'id' => $event->id,
            'contract_id' => $model->id,
            'requested_mbps' => $event->requested_mbps,
            'status' => $event->status,
            'reason' => $event->reason,
        ], 201, $request->header('X-Request-ID', ''));
    }

    private function scopedPartner(Request $request, int $partnerId): Mitra
    {
        $scope = TenantContext::partnerScope($request->user());

        if ($scope !== null && $scope !== $partnerId) {
            abort(404);
        }

        return Mitra::query()->findOrFail($partnerId);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(PartnerBandwidthContract $contract): array
    {
        return [
            'id' => $contract->id,
            'partner_id' => $contract->mitra_id,
            'committed_mbps' => (int) $contract->committed_mbps,
            'price_idr_per_mbps' => (int) $contract->price_idr_per_mbps,
            'starts_at' => $contract->starts_at?->toDateString(),
            'ends_at' => $contract->ends_at?->toDateString(),
            'status' => $contract->status,
            'events' => $contract->relationLoaded('events')
                ? $contract->events->map(fn (PartnerBandwidthContractEvent $event) => [
                    'id' => $event->id,
                    'type' => $event->type,
                    'requested_mbps' => $event->requested_mbps,
                    'reason' => $event->reason,
                    'status' => $event->status,
                ])->all()
                : [],
        ];
    }
}
