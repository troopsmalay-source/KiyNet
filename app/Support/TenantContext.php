<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Request;

class TenantContext
{
    /**
     * Return the partner ID scope for the given user, or null for central users.
     * Never trust mitra_id from request input; always resolve from the authenticated principal.
     */
    public static function partnerScope(User $user): ?int
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return null; // central user, no scope restriction
        }

        return (int) $user->mitra_id;
    }

    /**
     * Check if user is a central (non-partner) user.
     */
    public static function isCentral(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }
}
