<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCustomerRequest;
use App\Http\Requests\Api\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Pelanggan;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('customer.view');

        $query = $this->scopedQuery($request);

        if (in_array($request->input('status'), ['calon', 'aktif', 'isolir', 'nonaktif'], true)) {
            $query->where('status', $request->input('status'));
        }

        if ($search = trim((string) $request->input('search'))) {
            $pattern = '%'.$search.'%';
            $query->where(function ($where) use ($pattern): void {
                $where->where('kode_pelanggan', 'ilike', $pattern)
                    ->orWhere('nama_pelanggan', 'ilike', $pattern)
                    ->orWhere('no_hp', 'ilike', $pattern);
            });
        }

        $page = $query->orderBy('nama_pelanggan')->paginate($this->perPage($request));

        return ApiResponse::list(
            CustomerResource::collection($page->items())->resolve(),
            $this->paginationMeta($page),
            $request->header('X-Request-ID', ''),
        );
    }

    public function show(Request $request, int $customer): JsonResponse
    {
        $pelanggan = $this->scopedQuery($request)->findOrFail($customer);

        Gate::authorize('view', $pelanggan);

        return ApiResponse::ok(
            (new CustomerResource($pelanggan))->resolve(),
            200,
            $request->header('X-Request-ID', ''),
        );
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        Gate::authorize('customer.create');

        $data = $request->validated();
        $data['mitra_id'] = $this->tenantForWrite($request, $data['mitra_id'] ?? null);
        $data['kode_pelanggan'] = 'KN-'.now()->format('ym').'-'.Str::upper(Str::random(6));
        $data['status'] ??= 'calon';

        $pelanggan = Pelanggan::create($data);

        return ApiResponse::ok(
            (new CustomerResource($pelanggan))->resolve(),
            201,
            $request->header('X-Request-ID', ''),
        );
    }

    public function update(UpdateCustomerRequest $request, int $customer): JsonResponse
    {
        $pelanggan = $this->scopedQuery($request)->findOrFail($customer);

        Gate::authorize('update', $pelanggan);

        $data = $request->validated();

        // Partner users can never move a customer between tenants; central
        // transfer requires the customer.transfer permission.
        if (array_key_exists('mitra_id', $data)
            && ! (TenantContext::isCentral($request->user()) && Gate::allows('customer.transfer'))) {
            unset($data['mitra_id']);
        }

        $pelanggan->update($data);

        return ApiResponse::ok(
            (new CustomerResource($pelanggan))->resolve(),
            200,
            $request->header('X-Request-ID', ''),
        );
    }

    private function scopedQuery(Request $request)
    {
        $query = Pelanggan::query();
        $scope = TenantContext::partnerScope($request->user());

        if ($scope !== null) {
            $query->where('mitra_id', $scope);
        }

        return $query;
    }

    /**
     * Partner input never decides the tenant; central users need the
     * customer.transfer permission to assign a partner.
     */
    private function tenantForWrite(Request $request, ?int $requestedPartnerId): ?int
    {
        if (! TenantContext::isCentral($request->user())) {
            return $request->user()->mitra_id;
        }

        if ($requestedPartnerId !== null && ! Gate::allows('customer.transfer')) {
            abort(403, 'This action is unauthorized.');
        }

        return $requestedPartnerId;
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
