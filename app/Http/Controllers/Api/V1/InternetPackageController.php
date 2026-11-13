<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaketInternet;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class InternetPackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('package.view');

        $query = PaketInternet::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($search = trim((string) $request->input('search'))) {
            $pattern = '%'.$search.'%';
            $query->where(function ($where) use ($pattern): void {
                $where->where('kode_paket', 'ilike', $pattern)
                    ->orWhere('nama_paket', 'ilike', $pattern);
            });
        }

        $page = $query->orderBy('nama_paket')->paginate($this->perPage($request));

        return ApiResponse::list(
            collect($page->items())->map(fn (PaketInternet $package) => $this->payload($package))->all(),
            $this->paginationMeta($page),
            $request->header('X-Request-ID', ''),
        );
    }

    public function show(Request $request, int $package): JsonResponse
    {
        Gate::authorize('package.view');

        $model = PaketInternet::findOrFail($package);

        return ApiResponse::ok($this->payload($model), 200, $request->header('X-Request-ID', ''));
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('package.manage');

        $data = $request->validate([
            'nama_paket' => ['required', 'string', 'max:100'],
            'download_mbps' => ['nullable', 'integer', 'min:1'],
            'upload_mbps' => ['nullable', 'integer', 'min:1'],
            'harga' => ['required', 'integer', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $data['kode_paket'] = 'PK-'.Str::upper(Str::random(8));
        $data['is_active'] ??= true;

        $model = PaketInternet::create($data);

        return ApiResponse::ok($this->payload($model), 201, $request->header('X-Request-ID', ''));
    }

    public function update(Request $request, int $package): JsonResponse
    {
        $model = PaketInternet::findOrFail($package);
        Gate::authorize('update', $model);

        $data = $request->validate([
            'nama_paket' => ['sometimes', 'required', 'string', 'max:100'],
            'download_mbps' => ['nullable', 'integer', 'min:1'],
            'upload_mbps' => ['nullable', 'integer', 'min:1'],
            'harga' => ['sometimes', 'required', 'integer', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $model->update($data);

        return ApiResponse::ok($this->payload($model), 200, $request->header('X-Request-ID', ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(PaketInternet $package): array
    {
        return [
            'id' => $package->id,
            'kode_paket' => $package->kode_paket,
            'nama_paket' => $package->nama_paket,
            'download_mbps' => $package->download_mbps,
            'upload_mbps' => $package->upload_mbps,
            'harga_idr' => (int) round((float) $package->harga),
            'deskripsi' => $package->deskripsi,
            'is_active' => (bool) $package->is_active,
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
