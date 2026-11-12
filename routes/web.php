<?php

use App\Http\Controllers\Admin\BillingInvoiceController;
use App\Http\Controllers\Admin\CstInternalController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LanggananController;
use App\Http\Controllers\Admin\MitraController;
use App\Http\Controllers\Admin\PackageServiceController;
use App\Http\Controllers\Admin\PaketInternetController;
use App\Http\Controllers\Admin\PelangganController;
use App\Http\Controllers\Admin\PengingatTagihanController;
use App\Http\Controllers\Admin\TagihanController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));
Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart', [DashboardController::class, 'chart'])->name('dashboard.chart');
    Route::get('/development/{feature}', function (string $feature) {
        $features = [
            'pembayaran' => 'Pembayaran',
            'invoice' => 'Invoice',
            'laporan' => 'Laporan',
            'mikrotik' => 'Integrasi MikroTik',
            'pengaturan' => 'Pengaturan',
        ];

        abort_unless(isset($features[$feature]), 404);

        if ($feature === 'pembayaran') {
            return view('admin.pembayaran.index');
        }

        return view('admin.development', ['feature' => $features[$feature]]);
    })->name('development');

    Route::middleware('role:super_admin,admin,mitra')->group(function () {
        Route::get('cst-internal', [CstInternalController::class, 'index'])->name('cst-internal.index');
        Route::post('cst-internal', [CstInternalController::class, 'store'])->name('cst-internal.store');
        Route::get('cst-internal/export', [CstInternalController::class, 'export'])->name('cst-internal.export');
        Route::get('cst-internal/{cst}', [CstInternalController::class, 'show'])->name('cst-internal.show');
        Route::put('cst-internal/{cst}', [CstInternalController::class, 'update'])->name('cst-internal.update');
        Route::post('cst-internal/{cst}/status', [CstInternalController::class, 'updateStatus'])->name('cst-internal.status');
        Route::resource('pelanggan', PelangganController::class);
        Route::resource('langganan', LanggananController::class)->except(['show', 'destroy']);
        Route::get('tagihan', [TagihanController::class, 'index'])->name('tagihan.index');
        Route::get('tagihan/create', [TagihanController::class, 'create'])->name('tagihan.create');
        Route::post('tagihan', [TagihanController::class, 'store'])->name('tagihan.store');
        Route::get('tagihan/{tagihan}', [TagihanController::class, 'show'])->name('tagihan.show');
        Route::post('tagihan/{tagihan}/pembayaran', [TagihanController::class, 'storePayment'])->name('tagihan.pembayaran.store');
        Route::get('pengingat-tagihan', [PengingatTagihanController::class, 'index'])->name('pengingat.index');
    });

    Route::middleware('role:super_admin,admin')->group(function () {
        Route::resource('mitra', MitraController::class)->except(['show', 'destroy']);
        Route::resource('paket', PaketInternetController::class)->except(['show', 'destroy']);

        Route::get('packages', [PackageServiceController::class, 'index'])->name('packages.index');
        Route::post('packages', [PackageServiceController::class, 'store'])->name('packages.store');
        Route::put('packages/{package}', [PackageServiceController::class, 'update'])->name('packages.update');
        Route::delete('packages/{package}', [PackageServiceController::class, 'destroy'])->name('packages.destroy');
        Route::post('packages/{package}/toggle', [PackageServiceController::class, 'toggleStatus'])->name('packages.toggle');

        Route::get('billing', [BillingInvoiceController::class, 'index'])->name('billing.index');
        Route::get('billing/export', [BillingInvoiceController::class, 'exportCsv'])->name('billing.export');
        Route::post('billing', [BillingInvoiceController::class, 'store'])->name('billing.store');
        Route::post('billing/generate', [BillingInvoiceController::class, 'generateBatch'])->name('billing.generate');
        Route::get('billing/generate/preview', [BillingInvoiceController::class, 'previewBatch'])->name('billing.preview');
        Route::post('billing/wa-reminder', [BillingInvoiceController::class, 'sendWaReminder'])->name('billing.wa');
        Route::get('billing/{billing}', [BillingInvoiceController::class, 'show'])->name('billing.show');
        Route::post('billing/{billing}/payment', [BillingInvoiceController::class, 'recordPayment'])->name('billing.payment');
        Route::post('billing/{billing}/pending', [BillingInvoiceController::class, 'markPending'])->name('billing.pending');
        Route::delete('billing/{billing}/void', [BillingInvoiceController::class, 'void'])->name('billing.void');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
