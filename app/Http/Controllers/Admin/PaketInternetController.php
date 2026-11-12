<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Langganan;
use App\Models\PaketInternet;
use App\Support\DataTableResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;
class PaketInternetController extends Controller {
    public function index(Request $request): View|JsonResponse {
        $query = PaketInternet::query();

        if ($request->ajax()) {
            if (in_array((string) $request->input('status'), ['0', '1'], true)) {
                $query->where('is_active', (bool) $request->input('status'));
            }

            return DataTableResponse::from(
                $request,
                $query,
                fn ($filtered, string $search) => $filtered->where(function ($where) use ($search) {
                    $pattern = '%'.$search.'%';
                    $where->where('kode_paket', 'ilike', $pattern)
                        ->orWhere('nama_paket', 'ilike', $pattern)
                        ->orWhere('deskripsi', 'ilike', $pattern);
                }),
                [
                    'kode_paket' => 'kode_paket',
                    'nama_paket' => 'nama_paket',
                    'download_mbps' => 'download_mbps',
                    'upload_mbps' => 'upload_mbps',
                    'harga' => 'harga',
                    'is_active' => 'is_active',
                ],
                fn (PaketInternet $paket) => [
                    'id' => $paket->id,
                    'kode_paket' => $paket->kode_paket,
                    'nama_paket' => $paket->nama_paket,
                    'download_mbps' => $paket->download_mbps,
                    'upload_mbps' => $paket->upload_mbps,
                    'harga' => $paket->harga,
                    'is_active' => $paket->is_active,
                    'edit_url' => route('admin.paket.edit', $paket->id),
                ],
            );
        }

        return view('admin.paket.index', [
            'summary' => [
                'total' => PaketInternet::count(),
                'active' => PaketInternet::where('is_active', true)->count(),
                'inactive' => PaketInternet::where('is_active', false)->count(),
                'subscriptions' => Langganan::count(),
            ],
        ]);
    }
    public function create(): View { return view('admin.paket.create'); }
    public function store(Request $request): RedirectResponse {
        $data=$request->validate(['nama_paket'=>'required|string|max:100','download_mbps'=>'nullable|integer|min:1','upload_mbps'=>'nullable|integer|min:1','harga'=>'required|numeric|min:0','deskripsi'=>'nullable|string','is_active'=>'required|boolean']);
        $data['kode_paket']='PK-'.Str::upper(Str::random(7));
        PaketInternet::create($data);
        return redirect()->route('admin.paket.index')->with('success','Paket internet berhasil ditambahkan.');
    }
    public function edit(PaketInternet $paket): View { return view('admin.paket.edit',compact('paket')); }
    public function update(Request $request,PaketInternet $paket): RedirectResponse {
        $data=$request->validate(['nama_paket'=>'required|string|max:100','download_mbps'=>'nullable|integer|min:1','upload_mbps'=>'nullable|integer|min:1','harga'=>'required|numeric|min:0','deskripsi'=>'nullable|string','is_active'=>'required|boolean']);
        $paket->update($data); return redirect()->route('admin.paket.index')->with('success','Paket diperbarui.');
    }
}
