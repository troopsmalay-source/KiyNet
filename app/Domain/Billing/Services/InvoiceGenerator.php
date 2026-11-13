<?php

namespace App\Domain\Billing\Services;

use App\Models\Langganan;
use App\Models\Tagihan;
use App\Models\TagihanDetail;
use App\Models\User;
use App\Services\Notifications\NotificationOutboxService;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class InvoiceGenerator
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly NotificationOutboxService $notifications,
    ) {}

    public function generate(
        Langganan $subscription,
        string $period,
        ?string $idempotencyKey,
        User $actor,
    ): Tagihan {
        if ($idempotencyKey !== null) {
            $previous = Tagihan::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($previous !== null) {
                if ((int) $previous->langganan_id !== (int) $subscription->id || $previous->periode !== $period) {
                    throw new ConflictHttpException('Idempotency key was already used for another invoice.');
                }

                return $previous;
            }
        }

        return DB::transaction(function () use ($subscription, $period, $idempotencyKey, $actor): Tagihan {
            $existing = Tagihan::query()
                ->where('langganan_id', $subscription->id)
                ->where('periode', $period)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                throw new ConflictHttpException('An invoice already exists for this subscription and period.');
            }

            $amount = (int) round((float) $subscription->harga_snapshot);
            $billingDay = min(max((int) $subscription->hari_aktivasi, 1), 28);
            $dueDate = Carbon::createFromFormat('Y-m', $period)->addMonthNoOverflow()->day($billingDay);

            $invoice = Tagihan::create([
                'pelanggan_id' => $subscription->pelanggan_id,
                'langganan_id' => $subscription->id,
                'no_invoice' => 'INV-'.str_replace('-', '', $period).'-'.str_pad((string) $subscription->id, 6, '0', STR_PAD_LEFT),
                'periode' => $period,
                'idempotency_key' => $idempotencyKey,
                'tanggal_tagihan' => now()->toDateString(),
                'jatuh_tempo' => $dueDate->toDateString(),
                'akhir_masa_tenggang' => $dueDate->copy()->addDays((int) $subscription->masa_tenggang_hari)->toDateString(),
                'subtotal' => $amount,
                'diskon' => 0,
                'denda' => 0,
                'total_tagihan' => $amount,
                'total_dibayar' => 0,
                'status' => 'belum_bayar',
            ]);

            // Immutable line snapshot: amounts come from the subscription
            // price snapshot, never from the mutable package catalog.
            TagihanDetail::create([
                'tagihan_id' => $invoice->id,
                'deskripsi' => $subscription->paketInternet->nama_paket.' - '.$period,
                'qty' => 1,
                'harga_satuan' => $amount,
                'subtotal' => $amount,
            ]);

            $customer = $subscription->pelanggan;

            $this->notifications->enqueue(
                (string) $customer->no_hp,
                'invoice_created',
                [
                    'nama_pelanggan' => $customer->nama_pelanggan,
                    'no_invoice' => $invoice->no_invoice,
                    'periode' => $period,
                    'total' => $amount,
                ],
                'invoice:'.$invoice->id,
                'invoice:'.$invoice->id.':created',
            );

            $this->audit->log(
                $actor,
                'invoice.generate',
                'billing',
                'Invoice '.$invoice->no_invoice.' generated',
                [
                    'invoice_id' => $invoice->id,
                    'partner_id' => $customer->mitra_id,
                    'total_idr' => $amount,
                ],
            );

            return $invoice;
        });
    }
}
