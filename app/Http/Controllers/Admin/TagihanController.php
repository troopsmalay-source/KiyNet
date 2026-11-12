<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Langganan;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\TagihanDetail;
use App\Support\DataTableResponse;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TagihanController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = $this->tableQuery();

        if ($request->ajax()) {
            $status = $request->input('status');
            if (in_array($status, ['draft', 'belum_bayar', 'sebagian', 'lunas', 'dibatalkan'], true)) {
                $query->where('tagihan.status', $status);
            }

            return DataTableResponse::from(
                $request,
                $query,
                fn ($filtered, string $search) => $filtered->where(function ($where) use ($search) {
                    $pattern = '%'.$search.'%';
                    $where->where('tagihan.no_invoice', 'ilike', $pattern)
                        ->orWhere('pelanggan.kode_pelanggan', 'ilike', $pattern)
                        ->orWhere('pelanggan.nama_pelanggan', 'ilike', $pattern)
                        ->orWhere('paket_internet.nama_paket', 'ilike', $pattern);
                }),
                [
                    'no_invoice' => 'tagihan.no_invoice',
                    'kode_pelanggan' => 'pelanggan.kode_pelanggan',
                    'nama_pelanggan' => 'pelanggan.nama_pelanggan',
                    'nama_paket' => 'paket_internet.nama_paket',
                    'periode' => 'tagihan.periode',
                    'jatuh_tempo' => 'tagihan.jatuh_tempo',
                    'total_tagihan' => 'tagihan.total_tagihan',
                    'status' => 'tagihan.status',
                ],
                fn (Tagihan $tagihan) => [
                    'id' => $tagihan->id,
                    'no_invoice' => $tagihan->no_invoice,
                    'kode_pelanggan' => $tagihan->kode_pelanggan,
                    'nama_pelanggan' => $tagihan->nama_pelanggan,
                    'nama_paket' => $tagihan->nama_paket,
                    'periode' => $tagihan->periode,
                    'jatuh_tempo' => $tagihan->jatuh_tempo?->format('Y-m-d'),
                    'total_tagihan' => $tagihan->total_tagihan,
                    'total_dibayar' => $tagihan->total_dibayar,
                    'status' => $tagihan->status,
                    'show_url' => route('admin.tagihan.show', $tagihan->id),
                ],
                'tagihan.created_at',
            );
        }

        $summary = $this->invoiceQuery();

        return view('admin.tagihan.index', [
            'summary' => [
                'total' => (clone $summary)->count(),
                'unpaid' => (clone $summary)->where('status', 'belum_bayar')->count(),
                'partial' => (clone $summary)->where('status', 'sebagian')->count(),
                'paid' => (clone $summary)->where('status', 'lunas')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.tagihan.create', [
            'subscriptions' => $this->subscriptionQuery()
                ->with(['pelanggan', 'paketInternet'])
                ->where('status', 'aktif')
                ->whereHas('pelanggan', fn ($query) => $query->whereNull('deleted_at'))
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'langganan_id' => [
                'required',
                'integer',
                Rule::exists('langganan', 'id')
                    ->where('status', 'aktif'),
            ],
            'periode' => [
                'required',
                'date_format:Y-m',
                Rule::unique('tagihan', 'periode')->where('langganan_id', $request->input('langganan_id')),
            ],
            'jatuh_tempo' => ['required', 'date', 'after_or_equal:today'],
            'diskon' => ['nullable', 'numeric', 'min:0'],
        ]);

        $period = Carbon::createFromFormat('!Y-m', $data['periode']);
        $discount = (float) ($data['diskon'] ?? 0);

        $invoice = DB::transaction(function () use ($period, $data, $discount) {
            $subscription = $this->subscriptionQuery()
                ->with('paketInternet')
                ->where('status', 'aktif')
                ->lockForUpdate()
                ->findOrFail($data['langganan_id']);
            if ($subscription->tagihan()->where('periode', $period->format('Y-m'))->exists()) {
                throw ValidationException::withMessages([
                    'periode' => 'Invoice untuk langganan dan periode tersebut sudah tersedia.',
                ]);
            }

            $subtotal = (float) $subscription->harga_snapshot;
            if ($discount >= $subtotal) {
                throw ValidationException::withMessages([
                    'diskon' => 'Diskon harus lebih kecil dari harga langganan.',
                ]);
            }

            $invoice = Tagihan::create([
                'pelanggan_id' => $subscription->pelanggan_id,
                'langganan_id' => $subscription->id,
                'no_invoice' => 'INV-'.$period->format('Ym').'-'.Str::upper(Str::random(8)),
                'periode' => $period->format('Y-m'),
                'tanggal_tagihan' => now()->toDateString(),
                'jatuh_tempo' => $data['jatuh_tempo'],
                'akhir_masa_tenggang' => Carbon::parse($data['jatuh_tempo'])
                    ->addDays($subscription->masa_tenggang_hari)
                    ->toDateString(),
                'subtotal' => $subtotal,
                'diskon' => $discount,
                'denda' => 0,
                'total_tagihan' => $subtotal - $discount,
                'total_dibayar' => 0,
                'status' => 'belum_bayar',
            ]);

            TagihanDetail::create([
                'tagihan_id' => $invoice->id,
                'deskripsi' => $subscription->paketInternet->nama_paket.' - '.$period->translatedFormat('F Y'),
                'qty' => 1,
                'harga_satuan' => $subtotal,
                'subtotal' => $subtotal,
            ]);

            return $invoice;
        });

        return redirect()->route('admin.tagihan.show', $invoice)->with('success', 'Tagihan berhasil dibuat.');
    }

    public function show(Tagihan $tagihan): View
    {
        $this->authorizeMitra($tagihan);
        $tagihan->load(['pelanggan', 'langganan.paketInternet', 'detail', 'pembayaran' => fn ($query) => $query->orderByDesc('created_at')]);

        return view('admin.tagihan.show', compact('tagihan'));
    }

    public function storePayment(Request $request, Tagihan $tagihan): RedirectResponse
    {
        $this->authorizeMitra($tagihan);
        $data = $request->validate([
            'nominal' => ['required', 'numeric', 'gt:0'],
            'nomor_referensi' => ['nullable', 'string', 'max:120', 'unique:pembayaran,nomor_referensi'],
            'dibayar_at' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($tagihan, $data, $request) {
            $invoice = Tagihan::query()->lockForUpdate()->findOrFail($tagihan->id);
            if (in_array($invoice->status, ['lunas', 'dibatalkan'], true)) {
                throw ValidationException::withMessages([
                    'nominal' => 'Tagihan lunas atau dibatalkan tidak dapat menerima pembayaran.',
                ]);
            }

            $remaining = (float) $invoice->total_tagihan - (float) $invoice->total_dibayar;
            $amount = (float) $data['nominal'];
            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'nominal' => 'Nominal pembayaran melebihi sisa tagihan Rp '.number_format($remaining, 0, ',', '.').'.',
                ]);
            }

            Pembayaran::create([
                'tagihan_id' => $invoice->id,
                'metode' => 'transfer_manual',
                'nominal' => $amount,
                'nomor_referensi' => $data['nomor_referensi'] ?: null,
                'dibayar_at' => $data['dibayar_at'],
                'verified_by' => $request->user()->id,
                'status' => 'berhasil',
            ]);

            $totalPaid = (float) $invoice->total_dibayar + $amount;
            $invoice->total_dibayar = $totalPaid;
            $invoice->status = $totalPaid >= (float) $invoice->total_tagihan ? 'lunas' : 'sebagian';
            $invoice->paid_at = $invoice->status === 'lunas' ? $data['dibayar_at'] : null;
            $invoice->save();
        });

        return redirect()->route('admin.tagihan.show', $tagihan)->with('success', 'Pembayaran berhasil dicatat.');
    }

    private function tableQuery(): Builder
    {
        return $this->invoiceQuery()
            ->leftJoin('pelanggan', 'pelanggan.id', '=', 'tagihan.pelanggan_id')
            ->leftJoin('langganan', 'langganan.id', '=', 'tagihan.langganan_id')
            ->leftJoin('paket_internet', 'paket_internet.id', '=', 'langganan.paket_internet_id')
            ->whereNull('pelanggan.deleted_at')
            ->select(
                'tagihan.*',
                'pelanggan.kode_pelanggan',
                'pelanggan.nama_pelanggan',
                'paket_internet.nama_paket',
            );
    }

    private function invoiceQuery(): Builder
    {
        $query = Tagihan::query();
        if (auth()->user()->hasRole('mitra')) {
            if (auth()->user()->mitra_id === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('pelanggan', fn ($customers) => $customers->where('mitra_id', auth()->user()->mitra_id));
            }
        }

        return $query;
    }

    private function subscriptionQuery(): Builder
    {
        $query = Langganan::query();
        if (auth()->user()->hasRole('mitra')) {
            $query->whereHas('pelanggan', fn ($customers) => $customers->where('mitra_id', auth()->user()->mitra_id ?? 0));
        }

        return $query;
    }

    private function authorizeMitra(Tagihan $tagihan): void
    {
        if (auth()->user()->hasRole('mitra')) {
            abort_unless(
                auth()->user()->mitra_id !== null
                    && $tagihan->pelanggan?->mitra_id === auth()->user()->mitra_id,
                404,
            );
        }
    }
}
