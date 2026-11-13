<?php

use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PartnerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Agent A owned bootstrap
|--------------------------------------------------------------------------
| Business domains (Agent A) are defined here. Network routes (Agent B)
| are loaded via include from routes/api/v1/network.php.
| Contract: docs/04-API-CONTRACT.md, frozen in Phase 1.
|
| ponytail: uses the default session guard (Sanctum not installed yet).
| When token auth is needed, install laravel/sanctum and swap
| 'auth' for 'auth:sanctum' here.
*/

Route::prefix('v1')->group(function (): void {
    Route::middleware('auth')->group(function (): void {
        // Identity
        Route::get('me', [MeController::class, 'index'])->name('api.v1.me');

        // Partner & customer business core (tenant scoped server-side)
        Route::apiResource('partners', PartnerController::class)->only(['index', 'show', 'store', 'update']);
        Route::apiResource('customers', CustomerController::class)->only(['index', 'show', 'store', 'update']);
    });

    // Network & accounting endpoints (Agent B owned, reviewed contract include)
    Route::middleware('auth')->group(base_path('routes/api/v1/network.php'));
});
