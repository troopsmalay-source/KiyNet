<?php

use App\Http\Controllers\Api\V1\Network\NetworkNasController;
use App\Http\Controllers\Api\V1\Network\NetworkProfileController;
use App\Http\Controllers\Api\V1\Network\NetworkSessionController;
use App\Http\Controllers\Api\V1\Network\NetworkSyncCommandController;
use App\Http\Controllers\Api\V1\Network\NetworkUsageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Network & accounting API (Agent B owned)
|--------------------------------------------------------------------------
| Loaded by Agent A from the API bootstrap, e.g.:
|
|   Route::middleware('auth:sanctum')
|       ->prefix('v1')
|       ->group(base_path('routes/api/v1/network.php'));
|
| Writes are central-operator only and always queued through the outbox.
| Partner roles are read-only and scoped server-side.
*/
Route::prefix('network')->name('api.network.')->group(function () {
    Route::get('sessions', [NetworkSessionController::class, 'index'])->name('sessions.index');
    Route::post('sessions/{session}/disconnect', [NetworkSessionController::class, 'disconnect'])->name('sessions.disconnect');

    Route::get('customers/{customer}/usage', [NetworkUsageController::class, 'customer'])->name('usage.customer');
    Route::get('partners/{partner}/usage', [NetworkUsageController::class, 'partner'])->name('usage.partner');
    Route::get('partners/{partner}/throughput', [NetworkUsageController::class, 'throughput'])->name('usage.throughput');

    Route::get('sync-commands', [NetworkSyncCommandController::class, 'index'])->name('sync.index');
    Route::post('sync-commands/{command}/retry', [NetworkSyncCommandController::class, 'retry'])->name('sync.retry');

    Route::get('nas', [NetworkNasController::class, 'index'])->name('nas.index');
    Route::post('nas', [NetworkNasController::class, 'store'])->name('nas.store');
    Route::patch('nas/{nas}', [NetworkNasController::class, 'update'])->name('nas.update');

    Route::get('profiles', [NetworkProfileController::class, 'index'])->name('profiles.index');
    Route::post('profiles', [NetworkProfileController::class, 'store'])->name('profiles.store');
    Route::patch('profiles/{profile}', [NetworkProfileController::class, 'update'])->name('profiles.update');
});
