<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\BandwidthContractController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\InternetPackageController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PartnerController;
use App\Http\Controllers\Api\V1\PartnerUserController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaymentWebhookController;
use App\Http\Controllers\Api\V1\PublicInvoiceController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\TaxSubmissionController;
use App\Http\Controllers\Api\V1\WhatsAppTestController;
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

        // Partners, staff and wholesale contracts
        Route::apiResource('partners', PartnerController::class)->only(['index', 'show', 'store', 'update']);
        Route::get('partners/{partner}/users', [PartnerUserController::class, 'index'])->name('api.partners.users.index');
        Route::post('partners/{partner}/users', [PartnerUserController::class, 'store'])->name('api.partners.users.store');
        Route::patch('partners/{partner}/users/{user}', [PartnerUserController::class, 'update'])->name('api.partners.users.update');
        Route::get('partners/{partner}/bandwidth-contracts', [BandwidthContractController::class, 'index'])->name('api.partners.contracts.index');
        Route::post('partners/{partner}/bandwidth-contracts', [BandwidthContractController::class, 'store'])->name('api.partners.contracts.store');
        Route::patch('partners/{partner}/bandwidth-contracts/{contract}', [BandwidthContractController::class, 'update'])->name('api.partners.contracts.update');
        Route::post('partners/{partner}/bandwidth-contracts/{contract}/capacity-change-requests', [BandwidthContractController::class, 'capacityRequest'])->name('api.partners.contracts.capacity');

        // Customers
        Route::apiResource('customers', CustomerController::class)->only(['index', 'show', 'store', 'update']);
        Route::post('customers/{customer}/transfer', [CustomerController::class, 'transfer'])->name('api.customers.transfer');

        // Catalog
        Route::get('internet-packages', [InternetPackageController::class, 'index'])->name('api.packages.index');
        Route::get('internet-packages/{package}', [InternetPackageController::class, 'show'])->name('api.packages.show');
        Route::post('internet-packages', [InternetPackageController::class, 'store'])->name('api.packages.store');
        Route::patch('internet-packages/{package}', [InternetPackageController::class, 'update'])->name('api.packages.update');

        // Subscriptions
        Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('api.subscriptions.index');
        Route::post('subscriptions', [SubscriptionController::class, 'store'])->name('api.subscriptions.store');
        Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('api.subscriptions.show');
        Route::patch('subscriptions/{subscription}', [SubscriptionController::class, 'update'])->name('api.subscriptions.update');
        Route::post('subscriptions/{subscription}/activate', [SubscriptionController::class, 'activate'])->name('api.subscriptions.activate');
        Route::post('subscriptions/{subscription}/suspend', [SubscriptionController::class, 'suspend'])->name('api.subscriptions.suspend');
        Route::post('subscriptions/{subscription}/restore', [SubscriptionController::class, 'restore'])->name('api.subscriptions.restore');
        Route::post('subscriptions/{subscription}/change-package', [SubscriptionController::class, 'changePackage'])->name('api.subscriptions.change-package');

        // Billing
        Route::get('invoices', [InvoiceController::class, 'index'])->name('api.invoices.index');
        Route::post('invoices', [InvoiceController::class, 'generate'])->name('api.invoices.generate');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('api.invoices.show');
        Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('api.invoices.void');
        Route::post('invoices/{invoice}/access-tokens', [InvoiceController::class, 'issueToken'])->name('api.invoices.access-tokens.issue');
        Route::delete('invoice-access-tokens/{token}', [InvoiceController::class, 'revokeToken'])->name('api.invoice-access-tokens.revoke');

        // Payments
        Route::get('payments', [PaymentController::class, 'index'])->name('api.payments.index');
        Route::post('invoices/{invoice}/payment-intents', [PaymentController::class, 'intent'])->name('api.payments.intents.store');
        Route::post('payments/{payment}/refunds', [PaymentController::class, 'refund'])->name('api.payments.refunds.store');
        Route::post('payments/{payment}/reconcile', [PaymentController::class, 'reconcile'])->name('api.payments.reconcile');

        // Tax
        Route::get('tax/submissions', [TaxSubmissionController::class, 'index'])->name('api.tax.index');
        Route::post('tax/submissions', [TaxSubmissionController::class, 'store'])->name('api.tax.store');
        Route::get('tax/submissions/{submission}', [TaxSubmissionController::class, 'show'])->name('api.tax.show');
        Route::patch('tax/submissions/{submission}', [TaxSubmissionController::class, 'update'])->name('api.tax.update');
        Route::post('tax/submissions/{submission}/submit', [TaxSubmissionController::class, 'submit'])->name('api.tax.submit');
        Route::post('tax/submissions/{submission}/review', [TaxSubmissionController::class, 'review'])->name('api.tax.review');

        // Reports and audit
        Route::get('reports/billing', [ReportController::class, 'billing'])->name('api.reports.billing');
        Route::get('reports/partners/{partner}/tax', [TaxSubmissionController::class, 'partnerReport'])->name('api.reports.partner-tax');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('api.audit-logs.index');

        // Integrations
        Route::post('integrations/whatsapp/test', WhatsAppTestController::class)->name('api.integrations.whatsapp.test');
    });

    // Public invoice portal (opaque token, strict allowlist, rate limited)
    Route::get('public/invoices/{token}', [PublicInvoiceController::class, 'show'])
        ->middleware('throttle:30,1')
        ->name('api.public.invoices.show');

    // Provider payment webhooks (signature verified on raw body)
    Route::post('webhooks/payments/{provider}', PaymentWebhookController::class)
        ->middleware('throttle:60,1')
        ->name('api.webhooks.payments');

    // Network & accounting endpoints (Agent B owned, reviewed contract include)
    Route::middleware('auth')->group(base_path('routes/api/v1/network.php'));
});
