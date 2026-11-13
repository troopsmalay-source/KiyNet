<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Billing\Services\InvoiceBalanceService;
use App\Http\Controllers\Controller;
use App\Models\PaymentRefund;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly InvoiceBalanceService $balances,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('payment.view');

        $query = $this->scopedQuery($request)->with('tagihan');

        if (in_array($request->input('status'), ['pending', 'berhasil', 'ditolak', 'kadaluarsa', 'refund'], true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('invoice_id')) {
            $query->where('tagihan_id', $request->integer('invoice_id'));
        }

        $page = $query->orderByDesc('id')->paginate($this->perPage($request));

        return ApiResponse::list(
            collect($page->items())->map(fn (Pembayaran $payment) => $this->payload($payment))->all(),
            $this->paginationMeta($page),
            $request->header('X-Request-ID', ''),
        );
    }

    public function intent(Request $request, int $invoice): JsonResponse
    {
        Gate::authorize('payment.create');

        $model = $this->scopedInvoice($request, $invoice);
        Gate::authorize('view', $model);

        $data = $request->validate([
            'method' => ['required', 'in:qris,virtual_account,transfer_manual'],
            'amount_idr' => ['nullable', 'integer', 'min:1'],
        ]);

        $key = $request->header('Idempotency-Key') ?: $request->input('idempotency_key');

        if (! is_string($key) || $key === '') {
            throw new HttpException(422, 'Idempotency-Key header is required for payment intents.');
        }

        $previous = Pembayaran::query()->where('idempotency_key', $key)->first();

        if ($previous !== null) {
            if ((int) $previous->tagihan_id !== (int) $model->id) {
                throw new ConflictHttpException('Idempotency key was already used for another invoice.');
            }

            return ApiResponse::ok($this->payload($previous), 200, $request->header('X-Request-ID', ''));
        }

        if (in_array($model->status, ['lunas', 'dibatalkan'], true)) {
            throw new ConflictHttpException('Invoice is not payable.');
        }

        $remaining = (int) round((float) $model->total_tagihan) - (int) round((float) $model->total_dibayar);
        $amount = (int) ($data['amount_idr'] ?? $remaining);

        if ($remaining <= 0) {
            throw new ConflictHttpException('Invoice has no outstanding balance.');
        }

        if ($amount > $remaining) {
            throw new HttpException(422, 'Payment amount exceeds the outstanding balance.');
        }

        $payment = DB::transaction(function () use ($request, $model, $data, $amount, $key): Pembayaran {
            $payment = Pembayaran::create([
                'tagihan_id' => $model->id,
                'metode' => $data['method'],
                'nominal' => $amount,
                'nomor_referensi' => 'PAY-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
                'idempotency_key' => $key,
                'kadaluarsa_at' => now()->addDay(),
                'status' => 'pending',
            ]);

            $this->audit->log($request->user(), 'payment.intent.create', 'billing', 'Payment intent created', [
                'payment_id' => $payment->id,
                'invoice_id' => $model->id,
                'amount_idr' => $amount,
            ], $request);

            return $payment;
        });

        return ApiResponse::ok($this->payload($payment), 201, $request->header('X-Request-ID', ''));
    }

    public function refund(Request $request, int $payment): JsonResponse
    {
        Gate::authorize('payment.refund');

        $model = $this->scopedQuery($request)->findOrFail($payment);

        $data = $request->validate([
            'amount_idr' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        if ($model->status !== 'berhasil') {
            throw new ConflictHttpException('Only successful payments can be refunded.');
        }

        $already = (int) PaymentRefund::query()
            ->where('pembayaran_id', $model->id)
            ->whereIn('status', ['requested', 'approved', 'processed'])
            ->sum('amount_idr');

        if ((int) round((float) $model->nominal) < $data['amount_idr'] + $already) {
            throw new ConflictHttpException('Refund exceeds the refundable amount.');
        }

        $refund = DB::transaction(function () use ($request, $model, $data): PaymentRefund {
            $refund = PaymentRefund::create([
                'pembayaran_id' => $model->id,
                'amount_idr' => $data['amount_idr'],
                'reason' => $data['reason'],
                'status' => 'requested',
                'requested_by' => $request->user()->id,
            ]);

            $this->audit->log($request->user(), 'payment.refund.request', 'billing', 'Refund requested', [
                'payment_id' => $model->id,
                'refund_id' => $refund->id,
                'amount_idr' => $data['amount_idr'],
            ], $request);

            return $refund;
        });

        return ApiResponse::ok([
            'id' => $refund->id,
            'payment_id' => $model->id,
            'amount_idr' => (int) $refund->amount_idr,
            'status' => $refund->status,
        ], 201, $request->header('X-Request-ID', ''));
    }

    public function reconcile(Request $request, int $payment): JsonResponse
    {
        Gate::authorize('payment.reconcile');

        $model = $this->scopedQuery($request)->with('tagihan')->findOrFail($payment);

        $data = $request->validate(['note' => ['nullable', 'string', 'max:255']]);

        if ($model->status === 'berhasil') {
            return ApiResponse::ok($this->payload($model), 200, $request->header('X-Request-ID', ''));
        }

        if ($model->status !== 'pending') {
            throw new ConflictHttpException('Only pending payments can be reconciled.');
        }

        DB::transaction(function () use ($request, $model, $data): void {
            $model->update([
                'status' => 'berhasil',
                'dibayar_at' => now(),
                'verified_by' => $request->user()->id,
            ]);

            $this->balances->recalculate($model->tagihan);

            $this->audit->log($request->user(), 'payment.reconcile', 'billing', 'Payment manually reconciled', [
                'payment_id' => $model->id,
                'invoice_id' => $model->tagihan_id,
                'note' => $data['note'] ?? null,
            ], $request);
        });

        return ApiResponse::ok($this->payload($model->fresh('tagihan')), 200, $request->header('X-Request-ID', ''));
    }

    private function scopedQuery(Request $request)
    {
        $query = Pembayaran::query();
        $scope = TenantContext::partnerScope($request->user());

        if ($scope !== null) {
            $query->whereHas('tagihan.pelanggan', fn ($where) => $where->where('mitra_id', $scope));
        }

        return $query;
    }

    private function scopedInvoice(Request $request, int $invoiceId): Tagihan
    {
        $query = Tagihan::query();
        $scope = TenantContext::partnerScope($request->user());

        if ($scope !== null) {
            $query->whereHas('pelanggan', fn ($where) => $where->where('mitra_id', $scope));
        }

        return $query->findOrFail($invoiceId);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Pembayaran $payment): array
    {
        return [
            'id' => $payment->id,
            'invoice_id' => $payment->tagihan_id,
            'method' => $payment->metode,
            'amount_idr' => (int) round((float) $payment->nominal),
            'reference' => $payment->nomor_referensi,
            'status' => $payment->status,
            'paid_at' => $payment->dibayar_at?->toIso8601ZuluString(),
            'expires_at' => $payment->kadaluarsa_at?->toIso8601ZuluString(),
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
