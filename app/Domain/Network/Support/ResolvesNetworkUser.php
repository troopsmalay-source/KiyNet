<?php

namespace App\Domain\Network\Support;

use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesNetworkUser
{
    protected function networkUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401, 'Autentikasi diperlukan.');
        abort_unless($user->is_active, 403, 'Akun tidak aktif.');

        return $user;
    }
}
