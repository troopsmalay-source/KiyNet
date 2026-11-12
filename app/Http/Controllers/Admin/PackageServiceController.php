<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PackageServiceRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackageServiceController extends Controller
{
    public function __construct(private readonly PackageServiceRepository $repository) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status', 'kategori', 'profile', 'kecepatan']);

        return view('admin.packages.index', [
            'packages' => $this->repository->filter($filters),
            'filters' => $filters,
            'stats' => [
                'total' => $this->repository->all()->count(),
                'active' => $this->repository->active()->count(),
                'custom' => $this->repository->customPackages()->count(),
                'inactive' => $this->repository->inactive()->count(),
            ],
            'profiles' => PackageServiceRepository::PROFILES,
            'categories' => PackageServiceRepository::KATEGORI,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $record = $this->repository->create($data);

        return redirect()
            ->route('admin.packages.index')
            ->with('success', 'Paket "'.$record['nama'].'" berhasil ditambahkan (mode demo).');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $data = $this->validated($request);

        abort_if($this->repository->find($id) === null, 404);
        $this->repository->update($id, $data);

        return redirect()
            ->route('admin.packages.index')
            ->with('success', 'Paket "'.$data['nama'].'" diperbarui (mode demo).');
    }

    public function toggleStatus(Request $request, string $id): RedirectResponse
    {
        $record = $this->repository->find($id);
        abort_if($record === null, 404);

        $this->repository->toggleStatus($id);

        $status = $record['is_active'] ? 'dineaktifkan' : 'diaktifkan';

        return redirect()
            ->route('admin.packages.index')
            ->with('success', 'Paket "'.$record['nama'].'" '.$status.'.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $record = $this->repository->find($id);
        abort_if($record === null, 404);

        $this->repository->destroy($id);

        return redirect()
            ->route('admin.packages.index')
            ->with('success', 'Paket "'.$record['nama'].'" dihapus (soft delete, mode demo).');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kategori' => ['required', 'in:'.implode(',', array_keys(PackageServiceRepository::KATEGORI))],
            'siklus_tagihan' => ['required', 'in:'.implode(',', array_keys(PackageServiceRepository::SIKLUS))],
            'is_active' => ['sometimes', 'boolean'],
            'is_custom' => ['sometimes', 'boolean'],
            'custom_pelanggan' => ['nullable'],
            'download_mbps' => ['required', 'numeric', 'min:0.5', 'max:1000'],
            'upload_mbps' => ['required', 'numeric', 'min:0.5', 'max:1000'],
            'mikrotik_profile' => ['required', 'string', 'max:60'],
            'rate_limit' => ['required', 'string', 'max:30'],
            'ip_pool' => ['nullable', 'string', 'max:40'],
            'queue_priority' => ['required', 'integer', 'min:1', 'max:8'],
            'harga' => ['required', 'numeric', 'min:0'],
            'biaya_instalasi' => ['nullable', 'numeric', 'min:0'],
            'biaya_addon' => ['nullable', 'numeric', 'min:0'],
            'diskon_jenis' => ['nullable', 'in:persen,nominal'],
            'diskon_value' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'diskon_berlaku_sampai' => ['nullable', 'date'],
            'fup_enabled' => ['sometimes', 'boolean'],
            'fup_limit_gb' => ['nullable', 'numeric', 'min:1', 'max:100000'],
            'fup_throttle_rx' => ['nullable', 'numeric', 'min:0.5', 'max:1000'],
            'fup_throttle_tx' => ['nullable', 'numeric', 'min:0.5', 'max:1000'],
            'fup_aksi' => ['nullable', 'in:'.implode(',', array_keys(PackageServiceRepository::FUP_AKSI))],
        ]);

        return $this->hydrate($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function hydrate(array $data): array
    {
        $data['is_active'] = array_key_exists('is_active', $data)
            ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN)
            : true;
        $data['is_custom'] = array_key_exists('is_custom', $data)
            ? filter_var($data['is_custom'], FILTER_VALIDATE_BOOLEAN)
            : false;
        $customerRaw = $data['custom_pelanggan'] ?? null;
        if (is_string($customerRaw)) {
            $customerRaw = array_filter(array_map('trim', explode(',', $customerRaw)));
        }
        $data['custom_pelanggan'] = array_values(array_filter((array) $customerRaw));
        $data['biaya_instalasi'] = (float) ($data['biaya_instalasi'] ?? 0);
        $data['biaya_addon'] = (float) ($data['biaya_addon'] ?? 0);
        $data['diskon_value'] = (float) ($data['diskon_value'] ?? 0);

        if (! filled($data['diskon_jenis'] ?? null) || (float) $data['diskon_value'] <= 0) {
            $data['diskon_jenis'] = null;
            $data['diskon_value'] = 0;
            $data['diskon_berlaku_sampai'] = null;
        }

        if (! ($data['fup_enabled'] ?? false)) {
            $data['fup_limit_gb'] = null;
            $data['fup_throttle_rx'] = null;
            $data['fup_throttle_tx'] = null;
            $data['fup_aksi'] = null;
        } elseif (filled($data['fup_limit_gb'] ?? null)) {
            $data['fup_aksi'] = $data['fup_aksi'] ?? 'throttle';
        }

        return $data;
    }
}
