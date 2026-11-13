<?php

namespace App\Policies;

use App\Models\PaketInternet;
use App\Models\User;
use App\Support\TenantContext;

class PaketInternetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PaketInternet $package): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return TenantContext::isCentral($user);
    }

    public function update(User $user, PaketInternet $package): bool
    {
        return TenantContext::isCentral($user);
    }
}
