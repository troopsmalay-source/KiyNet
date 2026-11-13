<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Billing\Services\InvoiceAccessTokenService;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicInvoiceController extends Controller
{
    public function __construct(private readonly InvoiceAccessTokenService $tokens) {}

    public function show(Request $request, string $token): JsonResponse
    {
        $access = $this->tokens->findByRawToken($token);

        if ($access === null || ! $access->isUsable()) {
            abort(404);
        }

        $invoice = $access->tagihan()->with(['pelanggan', 'detail'])->firstOrFail();
        $access->update(['last_used_at' => now()]);

        // Strict allowlist: no address, phone, NIK, PPPoE or other PII.
        $payload = [
            'no_invoice' => $invoice->no_invoice,
            'period' => $invoice->periode,
            'status' => $invoice->status,
            'tanggal_tagihan' => $invoice->tanggal_tagihan?->toDateString(),
            'jatuh_tempo' => $invoice->jatuh_tempo?->toDateString(),
            'akhir_masa_tenggang' => $invoice->akhir_masa_tenggang?->toDateString(),
            'subtotal_idr' => (int) round((float) $invoice->subtotal),
            'diskon_idr' => (int) round((float) $invoice->diskon),
            'denda_idr' => (int) round((float) $invoice->denda),
            'total_idr' => (int) round((float) $invoice->total_tagihan),
            'paid_idr' => (int) round((float) $invoice->total_dibayar),
            'customer' => [
                'kode_pelanggan' => $invoice->pelanggan?->kode_pelanggan,
                'nama_pelanggan' => $invoice->pelanggan?->nama_pelanggan,
            ],
            'items' => $invoice->detail->map(fn ($item) => [
                'description' => $item->deskripsi,
                'qty' => (int) $item->qty,
                'unit_price_idr' => (int) round((float) $item->harga_satuan),
                'subtotal_idr' => (int) round((float) $item->subtotal),
            ])->all(),
        ];

        return ApiResponse::ok($payload, 200, $request->header('X-Request-ID', ''))
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
