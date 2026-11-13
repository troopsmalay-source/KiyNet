<?php

namespace App\Domain\Network\Services;

use App\Domain\Network\Dto\TrustedScope;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Temporary authorization bridge built on the legacy role column.
 *
 * Agent A's permission catalog replaces the role-name lists with the
 * network.device, network.usage and network.session permissions, but tenant
 * scope must always be derived from the authenticated user (never HTTP input).
 */
final class NetworkAuthorizer
{
    public function scopeFor(User $user): TrustedScope
    {
        if (! $user->is_active) {
            throw new AccessDeniedHttpException('Akun tidak aktif.');
        }

        if ($user->hasRole((array) config('network.central_roles', []))) {
            return TrustedScope::central();
        }

        if ($user->hasRole((array) config('network.partner_roles', [])) && $user->mitra_id !== null) {
            return TrustedScope::partner((int) $user->mitra_id);
        }

        throw new AccessDeniedHttpException('Tidak punya akses data jaringan.');
    }

    /**
     * Partner roles must never execute network writes.
     */
    public function assertCentral(User $user): TrustedScope
    {
        $scope = $this->scopeFor($user);
        $scope->assertCentral();

        return $scope;
    }
}
