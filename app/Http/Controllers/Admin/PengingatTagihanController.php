<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tagihan;
use App\Support\DataTableResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengingatTagihanController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $days = $request->input('days');
        $days = in_array((string) $days, ['7', '3', '1'], true) ? (int) $days : null;

        if ($request->ajax()) {
            $query = $this->tableQuery($days);

            return DataTableResponse::from(
                $request,
                $query,
                fn ($filtered, string $search) => $filtered->where(function ($where) use ($search) {
                    $pattern = '%'.$search.'%';
                    $where->where('tagihan.no_invoice', 'ilike', $pattern)
                        ->orWhere('pelanggan.kode_pelanggan', 'ilike', $pattern)
                        ->orWhere('pelanggan.nama_pelanggan', 'ilike', $pattern)
                        ->orWhere('pelanggan.no_hp', 'ilike', $pattern)
                        ->orWhere('paket_internet.nama_paket', 'ilike', $pattern);
                }),
                [
                    'no_invoice' => 'tagihan.no_invoice',
                    'kode_pelanggan' => 'pelanggan.kode_pelanggan',
                    'nama_pelanggan' => 'pelanggan.nama_pelanggan',
                    'nama_paket' => 'paket_internet.nama_paket',
                    'jatuh_tempo' => 'tagihan.jatuh_tempo',
                    'total_tagihan' => 'tagihan.total_tagihan',
                    'status' => 'tagihan.status',
                ],
                fn (Tagihan $tagihan) => [
                    'id' => $tagihan->id,
                    'no_invoice' => $tagihan->no_invoice,
                    'kode_pelanggan' => $tagihan->kode_pelanggan,
                    'nama_pelanggan' => $tagihan->nama_pelanggan,
                    'no_hp' => $tagihan->no_hp,
                    'nama_paket' => $tagihan->nama_paket,
                    'jatuh_tempo' => $tagihan->jatuh_tempo?->format('Y-m-d'),
                    'total_tagihan' => $tagihan->total_tagihan,
                    'sisa_tagihan' => max(0, (float) $tagihan->total_tagihan - (float) $tagihan->total_dibayar),
                    'status' => $tagihan->status,
                    'show_url' => route('admin.tagihan.show', $tagihan->id),
                ],
                'tagihan.jatuh_tempo',
            );
        }

        $scoped = $this->baseQuery();
        $today = now()->toDateString();
        $counts = [];
        foreach ([7, 3, 1] as $daysBefore) {
            $counts[$daysBefore] = (clone $scoped)
                ->whereIn('tagihan.status', ['belum_bayar', 'sebagian'])
                ->whereDate('tagihan.jatuh_tempo', now()->addDays($daysBefore)->toDateString())
                ->count();
        }

        return view('admin.pengingat.index', [
            'counts' => [
                'all' => (clone $scoped)->count(),
                7 => $counts[7],
                3 => $counts[3],
                1 => $counts[1],
            ],
            'today' => $today,
        ]);
    }

    private function tableQuery(?int $days): Builder
    {
        $query = $this->baseQuery()
            ->leftJoin('langganan', 'langganan.id', '=', 'tagihan.langganan_id')
            ->leftJoin('paket_internet', 'paket_internet.id', '=', 'langganan.paket_internet_id')
            ->select(
                'tagihan.*',
                'pelanggan.kode_pelanggan',
                'pelanggan.nama_pelanggan',
                'pelanggan.no_hp',
                'paket_internet.nama_paket',
            );

        if ($days !== null) {
            $query->whereIn('tagihan.status', ['belum_bayar', 'sebagian'])
                ->whereDate('tagihan.jatuh_tempo', now()->addDays($days)->toDateString());
        }

        return $query;
    }

    private function baseQuery(): Builder
    {
        $query = Tagihan::query()
            ->join('pelanggan', 'pelanggan.id', '=', 'tagihan.pelanggan_id')
            ->whereNull('pelanggan.deleted_at');

        if (auth()->user()->hasRole('mitra')) {
            $query->where('pelanggan.mitra_id', auth()->user()->mitra_id ?? 0);
        }

        return $query;
    }
}
