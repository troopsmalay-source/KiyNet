<?php

namespace App\Policies;

use App\Models\Pelanggan;
use App\Models\User;
use App\Support\TenantContext;

class PelangganPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::isCentral($user) || $user->mitra_id !== null;
    }

    public function view(User $user, Pelanggan $pelanggan): bool
    {
        return $this->sameTenant($user, $pelanggan);
    }

    public function create(User $user): bool
    {
        return TenantContext::isCentral($user) || $user->mitra_id !== null;
    }

    public function update(User $user, Pelanggan $pelanggan): bool
    {
        return $this->sameTenant($user, $pelanggan);
    }

    public function delete(User $user, Pelanggan $pelanggan): bool
    {
        return $this->sameTenant($user, $pelanggan);
    }

    private function sameTenant(User $user, Pelanggan $pelanggan): bool
    {
        if (TenantContext::isCentral($user)) {
            return true;
        }

        return $user->mitra_id !== null
            && $pelanggan->mitra_id !== null
            && (int) $user->mitra_id === (int) $pelanggan->mitra_id;
    }
}
