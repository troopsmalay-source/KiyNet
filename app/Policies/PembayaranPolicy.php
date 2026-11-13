<?php

namespace App\Policies;

use App\Models\Pembayaran;
use App\Models\User;
use App\Support\TenantContext;

class PembayaranPolicy
{
    public function view(User $user, Pembayaran $payment): bool
    {
        if (TenantContext::isCentral($user)) {
            return true;
        }

        $partnerId = $payment->tagihan?->pelanggan?->mitra_id;

        return $user->mitra_id !== null && $partnerId !== null && (int) $user->mitra_id === (int) $partnerId;
    }
}
