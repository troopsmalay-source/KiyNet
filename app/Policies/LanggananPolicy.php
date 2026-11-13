<?php

namespace App\Policies;

use App\Models\Langganan;
use App\Models\User;
use App\Support\TenantContext;

class LanggananPolicy
{
    public function view(User $user, Langganan $subscription): bool
    {
        return $this->sameTenant($user, $subscription);
    }

    public function update(User $user, Langganan $subscription): bool
    {
        return $this->sameTenant($user, $subscription);
    }

    private function sameTenant(User $user, Langganan $subscription): bool
    {
        if (TenantContext::isCentral($user)) {
            return true;
        }

        $partnerId = $subscription->pelanggan?->mitra_id;

        return $user->mitra_id !== null && $partnerId !== null && (int) $user->mitra_id === (int) $partnerId;
    }
}
