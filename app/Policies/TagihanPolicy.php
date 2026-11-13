<?php

namespace App\Policies;

use App\Models\Tagihan;
use App\Models\User;
use App\Support\TenantContext;

class TagihanPolicy
{
    public function view(User $user, Tagihan $invoice): bool
    {
        return $this->sameTenant($user, $invoice);
    }

    public function void(User $user, Tagihan $invoice): bool
    {
        return TenantContext::isCentral($user) && $this->sameTenant($user, $invoice);
    }

    private function sameTenant(User $user, Tagihan $invoice): bool
    {
        if (TenantContext::isCentral($user)) {
            return true;
        }

        $partnerId = $invoice->pelanggan?->mitra_id;

        return $user->mitra_id !== null && $partnerId !== null && (int) $user->mitra_id === (int) $partnerId;
    }
}
