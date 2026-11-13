<?php

namespace App\Domain\Billing\Services;

use App\Models\PaymentWebhookLog;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentWebhookService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Verify the provider signature over the raw body, persist the event under
     * a unique provider event id, then apply the payment exactly once.
     *
     * @return array{status: string, payment_id?: int}
     */
    public function handle(string $provider, Request $request): array
    {
        $secret = (string) config('services.payment.webhook_secret');

        if ($secret === '') {
            throw new HttpException(503, 'Payment webhook is not configured.');
        }

        $raw = $request->getContent();
        $signature = (string) $request->header('X-Signature', '');

        if ($signature === '' || ! hash_equals(hash_hmac('sha256', $raw, $secret), $signature)) {
            throw new HttpException(401, 'Invalid webhook signature.');
        }

        $payload = json_decode($raw, true);

        if (! is_array($payload)) {
            throw new HttpException(400, 'Malformed webhook payload.');
        }

        $eventId = $payload['event_id'] ?? null;
        $reference = $payload['reference'] ?? null;
        $amount = $payload['amount_idr'] ?? null;

        if (! is_string($eventId) || $eventId === '' || ! is_string($reference) || $reference === '' || ! is_int($amount)) {
            throw new HttpException(422, 'Webhook requires event_id, reference and integer amount_idr.');
        }

        return DB::transaction(function () use ($provider, $payload, $eventId, $reference, $amount): array {
            $log = PaymentWebhookLog::firstOrCreate(
                ['provider' => $provider, 'provider_event_id' => $eventId],
                [
                    'event_type' => is_string($payload['type'] ?? null) ? $payload['type'] : null,
                    'payload' => $payload,
                    'status_proses' => 'received',
                ],
            );

            if (! $log->wasRecentlyCreated) {
                return ['status' => 'already_processed'];
            }

            $payment = Pembayaran::query()
                ->where('nomor_referensi', $reference)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                $log->update(['status_proses' => 'failed', 'error_message' => 'Unknown payment reference.', 'processed_at' => now()]);

                throw new HttpException(404, 'Unknown payment reference.');
            }

            if ((int) round((float) $payment->nominal) !== $amount) {
                $log->update(['status_proses' => 'failed', 'error_message' => 'Amount mismatch.', 'processed_at' => now()]);

                throw new HttpException(422, 'Webhook amount does not match the payment intent.');
            }

            if ($payment->status === 'berhasil') {
                $log->update(['status_proses' => 'processed', 'processed_at' => now()]);

                return ['status' => 'already_paid', 'payment_id' => $payment->id];
            }

            $payment->update([
                'status' => 'berhasil',
                'dibayar_at' => now(),
                'provider_transaction_id' => is_string($payload['provider_transaction_id'] ?? null)
                    ? $payload['provider_transaction_id']
                    : $payment->provider_transaction_id,
                'provider_payload' => $payload,
            ]);

            $this->recalculateInvoice($payment->tagihan);

            $this->audit->log(
                null,
                'payment.webhook',
                'billing',
                'Payment '.$reference.' verified by provider webhook',
                ['payment_id' => $payment->id, 'amount_idr' => $amount, 'event_id' => $eventId],
            );

            $log->update(['status_proses' => 'processed', 'processed_at' => now()]);

            return ['status' => 'processed', 'payment_id' => $payment->id];
        });
    }

    private function recalculateInvoice(Tagihan $invoice): void
    {
        $paid = (int) round((float) Pembayaran::query()
            ->where('tagihan_id', $invoice->id)
            ->where('status', 'berhasil')
            ->sum('nominal'));

        $total = (int) round((float) $invoice->total_tagihan);
        $isPaid = $paid >= $total;

        $invoice->update([
            'total_dibayar' => $paid,
            'status' => $isPaid ? 'lunas' : ($paid > 0 ? 'sebagian' : $invoice->status),
            'paid_at' => $isPaid ? now() : null,
        ]);
    }
}
