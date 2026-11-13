<?php

use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Agent A owned bootstrap
|--------------------------------------------------------------------------
| Phase 1 freezes the bootstrap, identity endpoint and the include point for
| Agent B's network routes. Business domains join in Phase 2.
| Contract: docs/04-API-CONTRACT.md and docs/FOUNDATION_CONTRACTS.md.
|
| ponytail: uses the default session guard (Sanctum not installed yet).
| When token auth is needed, install laravel/sanctum and swap
| 'auth' for 'auth:sanctum' here.
*/

Route::prefix('v1')->group(function (): void {
    Route::middleware('auth')->group(function (): void {
        // Identity
        Route::get('me', [MeController::class, 'index'])->name('api.v1.me');
    });

    // Network & accounting endpoints (Agent B owned, reviewed contract include)
    Route::middleware('auth')->group(base_path('routes/api/v1/network.php'));
});
