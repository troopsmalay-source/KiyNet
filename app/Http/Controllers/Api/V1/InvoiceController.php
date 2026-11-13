<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Billing\Services\InvoiceAccessTokenService;
use App\Domain\Billing\Services\InvoiceGenerator;
use App\Http\Controllers\Controller;
use App\Models\InvoiceAccessToken;
use App\Models\Langganan;
use App\Models\Tagihan;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceGenerator $generator,
        private readonly InvoiceAccessTokenService $tokens,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('invoice.view');

        $query = $this->scopedQuery($request)->with(['pelanggan', 'detail']);

        if (in_array($request->input('status'), ['draft', 'belum_bayar', 'sebagian', 'lunas', 'dibatalkan'], true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('period')) {
            $query->where('periode', $request->input('period'));
        }

        if ($request->filled('customer_id')) {
            $query->where('pelanggan_id', $request->integer('customer_id'));
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where('no_invoice', 'ilike', '%'.$search.'%');
        }

        $page = $query->orderByDesc('id')->paginate($this->perPage($request));

        return ApiResponse::list(
            collect($page->items())->map(fn (Tagihan $invoice) => $this->payload($invoice))->all(),
            $this->paginationMeta($page),
            $request->header('X-Request-ID', ''),
        );
    }

    public function show(Request $request, int $invoice): JsonResponse
    {
        $model = $this->scopedQuery($request)->with(['pelanggan', 'detail', 'pembayaran'])->findOrFail($invoice);

        Gate::authorize('view', $model);

        return ApiResponse::ok($this->payload($model), 200, $request->header('X-Request-ID', ''));
    }

    public function generate(Request $request): JsonResponse
    {
        Gate::authorize('invoice.create');

        $data = $request->validate([
            'subscription_id' => ['required', 'integer', 'exists:langganan,id'],
            'period' => ['required', 'date_format:Y-m'],
        ]);

        $subscription = $this->scopedSubscription($request, (int) $data['subscription_id']);

        $idempotencyKey = $request->header('Idempotency-Key') ?: $request->input('idempotency_key');

        if (! is_string($idempotencyKey) || $idempotencyKey === '') {
            throw new HttpException(422, 'Idempotency-Key header is required for invoice generation.');
        }

        $invoice = $this->generator->generate($subscription, $data['period'], $idempotencyKey, $request->user());

        return ApiResponse::ok(
            $this->payload($invoice->load(['pelanggan', 'detail'])),
            201,
            $request->header('X-Request-ID', ''),
        );
    }

    public function void(Request $request, int $invoice): JsonResponse
    {
        $model = $this->scopedQuery($request)->findOrFail($invoice);

        Gate::authorize('invoice.void');
        Gate::authorize('void', $model);

        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        if ($model->status === 'dibatalkan') {
            throw new ConflictHttpException('Invoice is already void.');
        }

        if ($model->status === 'lunas') {
            throw new ConflictHttpException('Paid invoices require a refund/adjustment, not void.');
        }

        DB::transaction(function () use ($request, $model, $data): void {
            $model->update([
                'status' => 'dibatalkan',
                'voided_at' => now(),
                'void_reason' => $data['reason'],
            ]);

            $this->audit->log($request->user(), 'invoice.void', 'billing', 'Invoice voided', [
                'invoice_id' => $model->id,
                'reason' => $data['reason'],
            ], $request);
        });

        return ApiResponse::ok($this->payload($model->fresh(['pelanggan', 'detail'])), 200, $request->header('X-Request-ID', ''));
    }

    public function issueToken(Request $request, int $invoice): JsonResponse
    {
        Gate::authorize('invoice.public_link.create');

        $model = $this->scopedQuery($request)->findOrFail($invoice);
        Gate::authorize('view', $model);

        $issued = $this->tokens->issue($model, $request->user());

        $this->audit->log($request->user(), 'invoice.public_link.create', 'billing', 'Public invoice token issued', [
            'invoice_id' => $model->id,
            'token_id' => $issued['model']->id,
            'expires_at' => $issued['model']->expires_at->toIso8601ZuluString(),
        ], $request);

        return ApiResponse::ok([
            'id' => $issued['model']->id,
            'token' => $issued['token'],
            'expires_at' => $issued['model']->expires_at->toIso8601ZuluString(),
        ], 201, $request->header('X-Request-ID', ''));
    }

    public function revokeToken(Request $request, int $token): JsonResponse
    {
        Gate::authorize('invoice.public_link.revoke');

        $model = InvoiceAccessToken::query()->with('tagihan.pelanggan')->findOrFail($token);
        $invoice = $model->tagihan;

        Gate::authorize('view', $invoice);

        if ($model->revoked_at === null) {
            $model->update(['revoked_at' => now()]);

            $this->audit->log($request->user(), 'invoice.public_link.revoke', 'billing', 'Public invoice token revoked', [
                'invoice_id' => $invoice->id,
                'token_id' => $model->id,
            ], $request);
        }

        return ApiResponse::ok(['id' => $model->id, 'revoked_at' => $model->revoked_at?->toIso8601ZuluString()], 200, $request->header('X-Request-ID', ''));
    }

    private function scopedQuery(Request $request)
    {
        $query = Tagihan::query();
        $scope = TenantContext::partnerScope($request->user());

        if ($scope !== null) {
            $query->whereHas('pelanggan', fn ($where) => $where->where('mitra_id', $scope));
        }

        return $query;
    }

    private function scopedSubscription(Request $request, int $subscriptionId): Langganan
    {
        $query = Langganan::query()->with(['pelanggan', 'paketInternet']);
        $scope = TenantContext::partnerScope($request->user());

        if ($scope !== null) {
            $query->whereHas('pelanggan', fn ($where) => $where->where('mitra_id', $scope));
        }

        return $query->findOrFail($subscriptionId);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Tagihan $invoice): array
    {
        return [
            'id' => $invoice->id,
            'no_invoice' => $invoice->no_invoice,
            'period' => $invoice->periode,
            'customer' => [
                'id' => $invoice->pelanggan_id,
                'kode_pelanggan' => $invoice->pelanggan?->kode_pelanggan,
                'nama_pelanggan' => $invoice->pelanggan?->nama_pelanggan,
            ],
            'partner_id' => $invoice->pelanggan?->mitra_id,
            'status' => $invoice->status,
            'tanggal_tagihan' => $invoice->tanggal_tagihan?->toDateString(),
            'jatuh_tempo' => $invoice->jatuh_tempo?->toDateString(),
            'akhir_masa_tenggang' => $invoice->akhir_masa_tenggang?->toDateString(),
            'subtotal_idr' => (int) round((float) $invoice->subtotal),
            'diskon_idr' => (int) round((float) $invoice->diskon),
            'denda_idr' => (int) round((float) $invoice->denda),
            'total_idr' => (int) round((float) $invoice->total_tagihan),
            'paid_idr' => (int) round((float) $invoice->total_dibayar),
            'paid_at' => $invoice->paid_at?->toIso8601ZuluString(),
            'voided_at' => $invoice->voided_at?->toIso8601ZuluString(),
            'items' => $invoice->relationLoaded('detail')
                ? $invoice->detail->map(fn ($item) => [
                    'description' => $item->deskripsi,
                    'qty' => (int) $item->qty,
                    'unit_price_idr' => (int) round((float) $item->harga_satuan),
                    'subtotal_idr' => (int) round((float) $item->subtotal),
                ])->all()
                : [],
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
