<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\PermissionCatalog;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $requestId = $request->header('X-Request-ID', '');

        return ApiResponse::ok([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->name,
            'partner_id' => TenantContext::isCentral($user) ? null : $user->mitra_id,
            'partner_name' => $user->mitra?->nama_mitra,
            'is_central' => TenantContext::isCentral($user),
            'permissions' => PermissionCatalog::forRole($user->role?->name),
        ], 200, $requestId);
    }
}
