<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Langganan;
use App\Models\PaketInternet;
use App\Models\Pelanggan;
use App\Support\DataTableResponse;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LanggananController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = $this->tableQuery();

        if ($request->ajax()) {
            $status = $request->input('status');
            if (in_array($status, ['menunggu', 'aktif', 'ditangguhkan', 'berhenti'], true)) {
                $query->where('langganan.status', $status);
            }

            return DataTableResponse::from(
                $request,
                $query,
                fn ($filtered, string $search) => $filtered->where(function ($where) use ($search) {
                    $pattern = '%'.$search.'%';
                    $where->where('pelanggan.kode_pelanggan', 'ilike', $pattern)
                        ->orWhere('pelanggan.nama_pelanggan', 'ilike', $pattern)
                        ->orWhere('paket_internet.nama_paket', 'ilike', $pattern)
                        ->orWhere('langganan.pppoe_username', 'ilike', $pattern);
                }),
                [
                    'kode_pelanggan' => 'pelanggan.kode_pelanggan',
                    'nama_pelanggan' => 'pelanggan.nama_pelanggan',
                    'nama_paket' => 'paket_internet.nama_paket',
                    'tanggal_aktivasi' => 'langganan.tanggal_aktivasi',
                    'tanggal_jatuh_tempo' => 'langganan.tanggal_jatuh_tempo',
                    'harga_snapshot' => 'langganan.harga_snapshot',
                    'status' => 'langganan.status',
                ],
                fn (Langganan $langganan) => [
                    'id' => $langganan->id,
                    'kode_pelanggan' => $langganan->kode_pelanggan,
                    'nama_pelanggan' => $langganan->nama_pelanggan,
                    'nama_paket' => $langganan->nama_paket,
                    'tanggal_aktivasi' => $langganan->tanggal_aktivasi?->format('Y-m-d'),
                    'tanggal_jatuh_tempo' => $langganan->tanggal_jatuh_tempo?->format('Y-m-d'),
                    'harga_snapshot' => $langganan->harga_snapshot,
                    'status' => $langganan->status,
                    'edit_url' => route('admin.langganan.edit', $langganan->id),
                ],
                'langganan.created_at',
            );
        }

        $summaryQuery = $this->scopedQuery();

        return view('admin.langganan.index', [
            'summary' => [
                'total' => (clone $summaryQuery)->count(),
                'active' => (clone $summaryQuery)->where('status', 'aktif')->count(),
                'waiting' => (clone $summaryQuery)->where('status', 'menunggu')->count(),
                'paused' => (clone $summaryQuery)->where('status', 'ditangguhkan')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.langganan.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pelanggan_id' => ['required', 'integer', Rule::exists('pelanggan', 'id')->where(
                fn ($query) => auth()->user()->hasRole('mitra')
                    ? $query->where('mitra_id', auth()->user()->mitra_id ?? 0)
                    : $query
            )],
            'paket_internet_id' => ['required', 'integer', Rule::exists('paket_internet', 'id')->where('is_active', true)],
            'tanggal_aktivasi' => ['required', 'date'],
            'tanggal_jatuh_tempo' => ['nullable', 'date', 'after_or_equal:tanggal_aktivasi'],
            'tanggal_berakhir' => ['nullable', 'date', 'after_or_equal:tanggal_aktivasi'],
            'masa_tenggang_hari' => ['required', 'integer', 'between:0,30'],
            'status' => ['required', Rule::in(['menunggu', 'aktif', 'ditangguhkan', 'berhenti'])],
            'pppoe_username' => ['nullable', 'string', 'max:255', 'unique:langganan,pppoe_username'],
            'ip_static' => ['nullable', 'ip'],
            'profile_router' => ['nullable', 'string', 'max:255'],
        ]);

        $package = PaketInternet::findOrFail($data['paket_internet_id']);
        $data['hari_aktivasi'] = Carbon::parse($data['tanggal_aktivasi'])->day;
        $data['harga_snapshot'] = $package->harga;

        Langganan::create($data);

        return redirect()->route('admin.langganan.index')->with('success', 'Langganan berhasil ditambahkan.');
    }

    public function edit(Langganan $langganan): View
    {
        $this->authorizeMitra($langganan);

        return view('admin.langganan.edit', [
            ...$this->formOptions($langganan),
            'langganan' => $langganan,
        ]);
    }

    public function update(Request $request, Langganan $langganan): RedirectResponse
    {
        $this->authorizeMitra($langganan);

        $data = $request->validate([
            'pelanggan_id' => ['required', 'integer', Rule::exists('pelanggan', 'id')->where(
                fn ($query) => auth()->user()->hasRole('mitra')
                    ? $query->where('mitra_id', auth()->user()->mitra_id ?? 0)
                    : $query
            )],
            'paket_internet_id' => ['required', 'integer', Rule::exists('paket_internet', 'id')->where(function ($query) use ($langganan) {
                $query->where('is_active', true)->orWhere('id', $langganan->paket_internet_id);
            })],
            'tanggal_aktivasi' => ['required', 'date'],
            'tanggal_jatuh_tempo' => ['nullable', 'date', 'after_or_equal:tanggal_aktivasi'],
            'tanggal_berakhir' => ['nullable', 'date', 'after_or_equal:tanggal_aktivasi'],
            'masa_tenggang_hari' => ['required', 'integer', 'between:0,30'],
            'status' => ['required', Rule::in(['menunggu', 'aktif', 'ditangguhkan', 'berhenti'])],
            'pppoe_username' => ['nullable', 'string', 'max:255', Rule::unique('langganan', 'pppoe_username')->ignore($langganan->id)],
            'ip_static' => ['nullable', 'ip'],
            'profile_router' => ['nullable', 'string', 'max:255'],
        ]);

        $data['hari_aktivasi'] = Carbon::parse($data['tanggal_aktivasi'])->day;
        if ((int) $data['paket_internet_id'] !== $langganan->paket_internet_id) {
            $data['harga_snapshot'] = PaketInternet::findOrFail($data['paket_internet_id'])->harga;
        }

        $langganan->update($data);

        return redirect()->route('admin.langganan.index')->with('success', 'Data langganan diperbarui.');
    }

    private function tableQuery(): Builder
    {
        return $this->scopedQuery()
            ->leftJoin('pelanggan', 'pelanggan.id', '=', 'langganan.pelanggan_id')
            ->leftJoin('paket_internet', 'paket_internet.id', '=', 'langganan.paket_internet_id')
            ->whereNull('pelanggan.deleted_at')
            ->select(
                'langganan.*',
                'pelanggan.kode_pelanggan',
                'pelanggan.nama_pelanggan',
                'paket_internet.nama_paket',
            );
    }

    private function scopedQuery(): Builder
    {
        $query = Langganan::query();
        if (auth()->user()->hasRole('mitra')) {
            if (auth()->user()->mitra_id === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('pelanggan', fn ($customers) => $customers->where('mitra_id', auth()->user()->mitra_id));
            }
        }

        return $query;
    }

    private function formOptions(?Langganan $langganan = null): array
    {
        $customers = Pelanggan::query()->orderBy('nama_pelanggan');
        if (auth()->user()->hasRole('mitra')) {
            if (auth()->user()->mitra_id === null) {
                $customers->whereRaw('1 = 0');
            } else {
                $customers->where('mitra_id', auth()->user()->mitra_id);
            }
        }

        $packages = PaketInternet::query()->where('is_active', true)->orderBy('nama_paket');
        if ($langganan && !$langganan->paketInternet->is_active) {
            $packages->orWhere('id', $langganan->paket_internet_id);
        }

        return [
            'customers' => $customers->get(),
            'packages' => $packages->get(),
        ];
    }

    private function authorizeMitra(Langganan $langganan): void
    {
        if (auth()->user()->hasRole('mitra')) {
            abort_unless(
                auth()->user()->mitra_id !== null
                    && $langganan->pelanggan?->mitra_id === auth()->user()->mitra_id,
                404,
            );
        }
    }
}
