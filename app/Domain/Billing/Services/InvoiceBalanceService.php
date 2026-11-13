<?php

namespace App\Domain\Billing\Services;

use App\Models\Pembayaran;
use App\Models\Tagihan;

class InvoiceBalanceService
{
    public function recalculate(Tagihan $invoice): Tagihan
    {
        $paid = (int) round((float) Pembayaran::query()
            ->where('tagihan_id', $invoice->id)
            ->where('status', 'berhasil')
            ->sum('nominal'));

        $total = (int) round((float) $invoice->total_tagihan);
        $isPaid = $paid >= $total;

        $invoice->update([
            'total_dibayar' => $paid,
            'status' => $isPaid ? 'lunas' : ($paid > 0 ? 'sebagian' : 'belum_bayar'),
            'paid_at' => $isPaid ? now() : null,
        ]);

        return $invoice->refresh();
    }
}
