<?php

namespace App\Policies;

use App\Models\Mitra;
use App\Models\User;
use App\Support\TenantContext;

class MitraPolicy
{
    public function viewAny(User $user): bool
    {
        return TenantContext::isCentral($user) || $user->mitra_id !== null;
    }

    public function view(User $user, Mitra $mitra): bool
    {
        return $this->sameTenant($user, $mitra);
    }

    public function create(User $user): bool
    {
        return TenantContext::isCentral($user);
    }

    public function update(User $user, Mitra $mitra): bool
    {
        return $this->sameTenant($user, $mitra);
    }

    public function delete(User $user, Mitra $mitra): bool
    {
        return TenantContext::isCentral($user);
    }

    private function sameTenant(User $user, Mitra $mitra): bool
    {
        if (TenantContext::isCentral($user)) {
            return true;
        }

        return $user->mitra_id !== null && (int) $user->mitra_id === (int) $mitra->id;
    }
}
