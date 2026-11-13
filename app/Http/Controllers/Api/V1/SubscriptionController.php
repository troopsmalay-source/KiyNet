<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Network\Services\SubscriptionNetworkSync;
use App\Http\Controllers\Controller;
use App\Models\Langganan;
use App\Models\PaketInternet;
use App\Models\Pelanggan;
use App\Models\SubscriptionEvent;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionNetworkSync $network,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('subscription.view');

        $query = $this->scopedQuery($request)->with(['pelanggan', 'paketInternet']);

        if (in_array($request->input('status'), ['menunggu', 'aktif', 'ditangguhkan', 'berhenti'], true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('customer_id')) {
            $query->where('pelanggan_id', $request->integer('customer_id'));
        }

        $page = $query->orderByDesc('id')->paginate($this->perPage($request));

        return ApiResponse::list(
            collect($page->items())->map(fn (Langganan $subscription) => $this->payload($subscription))->all(),
            $this->paginationMeta($page),
            $request->header('X-Request-ID', ''),
        );
    }

    public function show(Request $request, int $subscription): JsonResponse
    {
        $model = $this->scopedQuery($request)->with(['pelanggan', 'paketInternet'])->findOrFail($subscription);

        Gate::authorize('view', $model);

        return ApiResponse::ok($this->payload($model), 200, $request->header('X-Request-ID', ''));
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('subscription.create');

        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:pelanggan,id'],
            'package_id' => ['required', 'integer', 'exists:paket_internet,id'],
            'pppoe_username' => ['nullable', 'string', 'max:100', 'unique:langganan,pppoe_username'],
            'start_date' => ['required', 'date'],
            'billing_day' => ['nullable', 'integer', 'between:1,31'],
            'masa_tenggang_hari' => ['nullable', 'integer', 'between:0,30'],
        ]);

        $customer = $this->scopedCustomer($request, (int) $data['customer_id']);
        $package = PaketInternet::query()->where('is_active', true)->findOrFail((int) $data['package_id']);

        $start = Carbon::parse($data['start_date']);
        $billingDay = (int) ($data['billing_day'] ?? $start->day);
        $dueDate = $start->copy()->addMonthNoOverflow()->day(min(max($billingDay, 1), 28));

        $subscription = DB::transaction(function () use ($request, $customer, $package, $start, $billingDay, $dueDate, $data): Langganan {
            $subscription = Langganan::create([
                'pelanggan_id' => $customer->id,
                'paket_internet_id' => $package->id,
                'tanggal_aktivasi' => $start->toDateString(),
                'hari_aktivasi' => $billingDay,
                'tanggal_jatuh_tempo' => $dueDate->toDateString(),
                'masa_tenggang_hari' => (int) ($data['masa_tenggang_hari'] ?? 7),
                'harga_snapshot' => $package->harga,
                'status' => 'menunggu',
                'pppoe_username' => $data['pppoe_username'] ?? null,
            ]);

            SubscriptionEvent::create([
                'langganan_id' => $subscription->id,
                'type' => 'created',
                'to_paket_internet_id' => $package->id,
                'effective_date' => $start->toDateString(),
                'actor_id' => $request->user()->id,
            ]);

            $this->audit->log($request->user(), 'subscription.create', 'subscription', 'Subscription created', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customer->id,
                'package_id' => $package->id,
            ], $request);

            return $subscription;
        });

        return ApiResponse::ok($this->payload($subscription->load(['pelanggan', 'paketInternet'])), 201, $request->header('X-Request-ID', ''));
    }

    public function update(Request $request, int $subscription): JsonResponse
    {
        $model = $this->scopedQuery($request)->findOrFail($subscription);
        Gate::authorize('update', $model);

        Gate::authorize('subscription.update');

        $data = $request->validate([
            'masa_tenggang_hari' => ['sometimes', 'integer', 'between:0,30'],
            'tanggal_jatuh_tempo' => ['sometimes', 'date'],
            'profile_router' => ['nullable', 'string', 'max:100'],
        ]);

        $model->update($data);

        return ApiResponse::ok($this->payload($model->load(['pelanggan', 'paketInternet'])), 200, $request->header('X-Request-ID', ''));
    }

    public function activate(Request $request, int $subscription): JsonResponse
    {
        return $this->transition($request, $subscription, 'activate');
    }

    public function suspend(Request $request, int $subscription): JsonResponse
    {
        return $this->transition($request, $subscription, 'suspend');
    }

    public function restore(Request $request, int $subscription): JsonResponse
    {
        return $this->transition($request, $subscription, 'restore');
    }

    public function changePackage(Request $request, int $subscription): JsonResponse
    {
        Gate::authorize('subscription.change_package');

        $model = $this->scopedQuery($request)->findOrFail($subscription);
        Gate::authorize('update', $model);

        $data = $request->validate([
            'package_id' => ['required', 'integer', 'exists:paket_internet,id'],
            'effective_date' => ['nullable', 'date'],
        ]);

        $package = PaketInternet::query()->where('is_active', true)->findOrFail((int) $data['package_id']);

        if ((int) $package->id === (int) $model->paket_internet_id) {
            throw new ConflictHttpException('Subscription already uses this package.');
        }

        $fromPackageId = (int) $model->paket_internet_id;

        $command = DB::transaction(function () use ($request, $model, $package, $fromPackageId, $data) {
            $model->update([
                'paket_internet_id' => $package->id,
                'harga_snapshot' => $package->harga,
            ]);

            SubscriptionEvent::create([
                'langganan_id' => $model->id,
                'type' => 'package_changed',
                'from_paket_internet_id' => $fromPackageId,
                'to_paket_internet_id' => $package->id,
                'effective_date' => $data['effective_date'] ?? now()->toDateString(),
                'actor_id' => $request->user()->id,
            ]);

            $this->audit->log($request->user(), 'subscription.change_package', 'subscription', 'Package changed', [
                'subscription_id' => $model->id,
                'from_package_id' => $fromPackageId,
                'to_package_id' => $package->id,
            ], $request);

            return $this->network->changePackage($model, $request->header('X-Request-ID'));
        });

        $payload = $this->payload($model->fresh(['pelanggan', 'paketInternet']));
        $payload['network'] = $this->networkResult($command);

        return ApiResponse::ok($payload, $command !== null ? 202 : 200, $request->header('X-Request-ID', ''));
    }

    private function transition(Request $request, int $subscriptionId, string $action): JsonResponse
    {
        $permission = match ($action) {
            'activate' => 'subscription.activate',
            'suspend' => 'subscription.suspend',
            default => 'subscription.restore',
        };

        Gate::authorize($permission);

        $model = $this->scopedQuery($request)->findOrFail($subscriptionId);
        Gate::authorize('update', $model);

        $allowedFrom = match ($action) {
            'activate' => ['menunggu', 'ditangguhkan'],
            'suspend' => ['aktif'],
            default => ['ditangguhkan'],
        };

        if (! in_array($model->status, $allowedFrom, true)) {
            throw new ConflictHttpException("Cannot {$action} a subscription with status {$model->status}.");
        }

        $reason = $request->input('reason_note') ?? $request->input('reason_code');

        $command = DB::transaction(function () use ($request, $model, $action, $reason) {
            $model->update(['status' => match ($action) {
                'activate', 'restore' => 'aktif',
                default => 'ditangguhkan',
            }]);

            SubscriptionEvent::create([
                'langganan_id' => $model->id,
                'type' => $action.'d',
                'actor_id' => $request->user()->id,
                'metadata' => ['reason' => $reason],
            ]);

            $this->audit->log($request->user(), 'subscription.'.$action, 'subscription', 'Subscription '.$action, [
                'subscription_id' => $model->id,
                'reason' => $reason,
            ], $request);

            return match ($action) {
                'activate' => $this->network->activate($model, $request->header('X-Request-ID')),
                'suspend' => $this->network->suspend($model, $reason, $request->header('X-Request-ID')),
                default => $this->network->restore($model, $request->header('X-Request-ID')),
            };
        });

        $payload = $this->payload($model->fresh(['pelanggan', 'paketInternet']));
        $payload['network'] = $this->networkResult($command);

        return ApiResponse::ok($payload, $command !== null ? 202 : 200, $request->header('X-Request-ID', ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function networkResult($command): array
    {
        if ($command === null) {
            return ['sync_status' => 'not_required', 'command_id' => null, 'desired_state' => null];
        }

        return [
            'sync_status' => 'pending',
            'command_id' => $command->id,
            'desired_state' => $command->desired_state ?? null,
        ];
    }

    private function scopedQuery(Request $request)
    {
        $query = Langganan::query();
        $scope = TenantContext::partnerScope($request->user());

        if ($scope !== null) {
            $query->whereHas('pelanggan', fn ($where) => $where->where('mitra_id', $scope));
        }

        return $query;
    }

    private function scopedCustomer(Request $request, int $customerId): Pelanggan
    {
        $query = Pelanggan::query();
        $scope = TenantContext::partnerScope($request->user());

        if ($scope !== null) {
            $query->where('mitra_id', $scope);
        }

        return $query->findOrFail($customerId);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Langganan $subscription): array
    {
        return [
            'id' => $subscription->id,
            'customer_id' => $subscription->pelanggan_id,
            'partner_id' => $subscription->pelanggan?->mitra_id,
            'package' => [
                'id' => $subscription->paket_internet_id,
                'nama_paket' => $subscription->paketInternet?->nama_paket,
                'harga_idr' => (int) round((float) $subscription->harga_snapshot),
            ],
            'status' => $subscription->status,
            'tanggal_aktivasi' => $subscription->tanggal_aktivasi?->toDateString(),
            'tanggal_jatuh_tempo' => $subscription->tanggal_jatuh_tempo?->toDateString(),
            'masa_tenggang_hari' => $subscription->masa_tenggang_hari,
            'pppoe_username' => $subscription->pppoe_username,
            'profile_router' => $subscription->profile_router,
        ];
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 25), 1), 100);
    }

    /**
     * @return array<string, int>
     */
    private function paginationMeta($page): array
    {
        return [
            'page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'last_page' => $page->lastPage(),
        ];
    }
}
