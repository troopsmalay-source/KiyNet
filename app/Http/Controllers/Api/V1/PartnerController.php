<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePartnerRequest;
use App\Http\Requests\Api\UpdatePartnerRequest;
use App\Http\Resources\PartnerResource;
use App\Models\Mitra;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class PartnerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('partner.view');

        $user = $request->user();
        $query = Mitra::query()->withCount('pelanggan');

        if (! TenantContext::isCentral($user)) {
            $query->whereKey($user->mitra_id ?? 0);
        }

        if (in_array($request->input('status'), ['aktif', 'nonaktif'], true)) {
            $query->where('status', $request->input('status'));
        }

        if ($search = trim((string) $request->input('search'))) {
            $pattern = '%'.$search.'%';
            $query->where(function ($where) use ($pattern): void {
                $where->where('kode_mitra', 'ilike', $pattern)
                    ->orWhere('nama_mitra', 'ilike', $pattern)
                    ->orWhere('nama_pic', 'ilike', $pattern);
            });
        }

        $page = $query->orderBy('nama_mitra')->paginate($this->perPage($request));

        return ApiResponse::list(
            PartnerResource::collection($page->items())->resolve(),
            $this->paginationMeta($page),
            $request->header('X-Request-ID', ''),
        );
    }

    public function show(Request $request, int $partner): JsonResponse
    {
        $mitra = $this->scopedQuery($request)->findOrFail($partner);

        Gate::authorize('view', $mitra);

        return ApiResponse::ok(
            (new PartnerResource($mitra->loadCount('pelanggan')))->resolve(),
            200,
            $request->header('X-Request-ID', ''),
        );
    }

    public function store(StorePartnerRequest $request): JsonResponse
    {
        Gate::authorize('partner.create');

        $mitra = Mitra::create($request->validated() + [
            'kode_mitra' => 'MT-'.Str::upper(Str::random(8)),
        ]);

        return ApiResponse::ok(
            (new PartnerResource($mitra))->resolve(),
            201,
            $request->header('X-Request-ID', ''),
        );
    }

    public function update(UpdatePartnerRequest $request, int $partner): JsonResponse
    {
        $mitra = $this->scopedQuery($request)->findOrFail($partner);

        Gate::authorize('update', $mitra);

        $mitra->update($request->validated());

        return ApiResponse::ok(
            (new PartnerResource($mitra))->resolve(),
            200,
            $request->header('X-Request-ID', ''),
        );
    }

    private function scopedQuery(Request $request)
    {
        $query = Mitra::query();

        if (! TenantContext::isCentral($request->user())) {
            $query->whereKey($request->user()->mitra_id ?? 0);
        }

        return $query;
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
