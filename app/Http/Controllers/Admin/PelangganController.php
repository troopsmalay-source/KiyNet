<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\Mitra;
use App\Support\DataTableResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;
class PelangganController extends Controller {
    public function index(Request $request): View|JsonResponse {
        $query = Pelanggan::query()
            ->leftJoin('mitra', 'mitra.id', '=', 'pelanggan.mitra_id')
            ->select('pelanggan.*', 'mitra.nama_mitra');

        if (auth()->user()->hasRole('mitra')) {
            $query->where('pelanggan.mitra_id', auth()->user()->mitra_id);
        }

        if ($request->ajax()) {
            return DataTableResponse::from(
                $request,
                $query,
                fn ($filtered, string $search) => $filtered->where(function ($where) use ($search) {
                    $pattern = '%'.$search.'%';
                    $where->where('pelanggan.nama_pelanggan', 'ilike', $pattern)
                        ->orWhere('pelanggan.kode_pelanggan', 'ilike', $pattern)
                        ->orWhere('pelanggan.no_hp', 'ilike', $pattern)
                        ->orWhere('mitra.nama_mitra', 'ilike', $pattern);
                }),
                [
                    'kode_pelanggan' => 'pelanggan.kode_pelanggan',
                    'nama_pelanggan' => 'pelanggan.nama_pelanggan',
                    'no_hp' => 'pelanggan.no_hp',
                    'mitra' => 'mitra.nama_mitra',
                    'status' => 'pelanggan.status',
                ],
                fn (Pelanggan $pelanggan) => [
                    'id' => $pelanggan->id,
                    'kode_pelanggan' => $pelanggan->kode_pelanggan,
                    'nama_pelanggan' => $pelanggan->nama_pelanggan,
                    'no_hp' => $pelanggan->no_hp,
                    'mitra' => $pelanggan->nama_mitra,
                    'status' => $pelanggan->status,
                    'show_url' => route('admin.pelanggan.show', $pelanggan->id),
                    'edit_url' => route('admin.pelanggan.edit', $pelanggan->id),
                ],
                'pelanggan.created_at',
            );
        }

        return view('admin.pelanggan.index');
    }
    public function create(): View {
        $mitra = Mitra::where('status','aktif')->get();
        return view('admin.pelanggan.create', compact('mitra'));
    }
    public function store(Request $request): RedirectResponse {
        $data = $request->validate([
            'mitra_id'=>'nullable|exists:mitra,id','nama_pelanggan'=>'required|string|max:150',
            'no_hp'=>'required|string|max:25','email'=>'nullable|email|max:255',
            'alamat_pasang'=>'required|string','latitude'=>'nullable|numeric|between:-90,90',
            'longitude'=>'nullable|numeric|between:-180,180','nik'=>'nullable|string|max:30','catatan'=>'nullable|string',
        ]);
        if (auth()->user()->hasRole('mitra')) $data['mitra_id'] = auth()->user()->mitra_id;
        $data['kode_pelanggan'] = 'KN-'.now()->format('ym').'-'.Str::upper(Str::random(6));
        $data['status'] = 'calon';
        Pelanggan::create($data);
        return redirect()->route('admin.pelanggan.index')->with('success','Pelanggan berhasil ditambahkan.');
    }
    public function show(Pelanggan $pelanggan): View {
        $this->authorizeMitra($pelanggan);
        return view('admin.pelanggan.show', compact('pelanggan'));
    }
    public function edit(Pelanggan $pelanggan): View {
        $this->authorizeMitra($pelanggan);
        $mitra = Mitra::where('status','aktif')->get();
        return view('admin.pelanggan.edit', compact('pelanggan','mitra'));
    }
    public function update(Request $request, Pelanggan $pelanggan): RedirectResponse {
        $this->authorizeMitra($pelanggan);
        $data = $request->validate([
            'mitra_id'=>'nullable|exists:mitra,id','nama_pelanggan'=>'required|string|max:150',
            'no_hp'=>'required|string|max:25','email'=>'nullable|email|max:255',
            'alamat_pasang'=>'required|string','latitude'=>'nullable|numeric|between:-90,90',
            'longitude'=>'nullable|numeric|between:-180,180','nik'=>'nullable|string|max:30','status'=>'required|in:calon,aktif,isolir,nonaktif','catatan'=>'nullable|string',
        ]);
        if (auth()->user()->hasRole('mitra')) $data['mitra_id'] = auth()->user()->mitra_id;
        $pelanggan->update($data);
        return redirect()->route('admin.pelanggan.index')->with('success','Data pelanggan diperbarui.');
    }
    public function destroy(Pelanggan $pelanggan): RedirectResponse {
        $this->authorizeMitra($pelanggan);
        $pelanggan->delete();
        return back()->with('success','Pelanggan dinonaktifkan dari daftar (soft delete).');
    }
    private function authorizeMitra(Pelanggan $pelanggan): void {
        if (auth()->user()->hasRole('mitra')) abort_unless($pelanggan->mitra_id === auth()->user()->mitra_id, 403);
    }
}
