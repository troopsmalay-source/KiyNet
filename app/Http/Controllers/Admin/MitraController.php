<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\Pelanggan;
use App\Support\DataTableResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;
class MitraController extends Controller {
    public function index(Request $request): View|JsonResponse {
        $query = Mitra::query()->withCount('pelanggan');

        if ($request->ajax()) {
            if (in_array($request->input('status'), ['aktif', 'nonaktif'], true)) {
                $query->where('status', $request->input('status'));
            }

            return DataTableResponse::from(
                $request,
                $query,
                fn ($filtered, string $search) => $filtered->where(function ($where) use ($search) {
                    $pattern = '%'.$search.'%';
                    $where->where('kode_mitra', 'ilike', $pattern)
                        ->orWhere('nama_mitra', 'ilike', $pattern)
                        ->orWhere('nama_pic', 'ilike', $pattern)
                        ->orWhere('no_hp', 'ilike', $pattern)
                        ->orWhere('email', 'ilike', $pattern);
                }),
                [
                    'kode_mitra' => 'kode_mitra',
                    'nama_mitra' => 'nama_mitra',
                    'nama_pic' => 'nama_pic',
                    'no_hp' => 'no_hp',
                    'email' => 'email',
                    'pelanggan_count' => 'pelanggan_count',
                    'status' => 'status',
                ],
                fn (Mitra $mitra) => [
                    'id' => $mitra->id,
                    'kode_mitra' => $mitra->kode_mitra,
                    'nama_mitra' => $mitra->nama_mitra,
                    'nama_pic' => $mitra->nama_pic,
                    'no_hp' => $mitra->no_hp,
                    'email' => $mitra->email,
                    'pelanggan_count' => $mitra->pelanggan_count,
                    'status' => $mitra->status,
                    'edit_url' => route('admin.mitra.edit', $mitra->id),
                ],
            );
        }

        return view('admin.mitra.index', [
            'summary' => [
                'total' => Mitra::count(),
                'active' => Mitra::where('status', 'aktif')->count(),
                'inactive' => Mitra::where('status', 'nonaktif')->count(),
                'customers' => Pelanggan::whereNotNull('mitra_id')->count(),
            ],
        ]);
    }
    public function create(): View { return view('admin.mitra.create'); }
    public function store(Request $request): RedirectResponse {
        $data=$request->validate(['nama_mitra'=>'required|string|max:150','nama_pic'=>'nullable|string|max:150','no_hp'=>'nullable|string|max:25','email'=>'nullable|email|max:255','alamat'=>'nullable|string','status'=>'required|in:aktif,nonaktif']);
        $data['kode_mitra']='MT-'.Str::upper(Str::random(8));
        Mitra::create($data);
        return redirect()->route('admin.mitra.index')->with('success','Mitra berhasil ditambahkan.');
    }
    public function edit(Mitra $mitra): View { return view('admin.mitra.edit',compact('mitra')); }
    public function update(Request $request,Mitra $mitra): RedirectResponse {
        $data=$request->validate(['nama_mitra'=>'required|string|max:150','nama_pic'=>'nullable|string|max:150','no_hp'=>'nullable|string|max:25','email'=>'nullable|email|max:255','alamat'=>'nullable|string','status'=>'required|in:aktif,nonaktif']);
        $mitra->update($data); return redirect()->route('admin.mitra.index')->with('success','Data mitra diperbarui.');
    }
}
