<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\PartialBatchController;
use App\Http\Controllers\BatchDownloadController;
use App\Http\Controllers\MasterCartonController;
use App\Http\Controllers\ConsignmentController;
use App\Http\Controllers\DistributionController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\TherapeuticClassController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\ProformaInvoiceController;
use App\Http\Controllers\CommercialInvoiceController;
use App\Http\Controllers\CountryManagerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AntiCounterfeitController;

/*
|--------------------------------------------------------------------------
| PharmaTrack Web Routes
|--------------------------------------------------------------------------
*/

// ── Auth ─────────────────────────────────────────────────────────────────
Route::get('/', function () {
    return redirect()->route(\Illuminate\Support\Facades\Auth::check() ? 'dashboard' : 'login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Staff password reset
Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:6,1')->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1')->name('password.update');

// ── Public product verification (reached from a unit's QR code) ────────────
Route::get('/verify/{code}', [BatchController::class, 'verify'])->name('verify');
Route::post('/verify/{code}/report', [BatchController::class, 'report'])->name('verify.report');
Route::post('/verify/{code}/request-info', [BatchController::class, 'requestInfo'])->name('verify.request-info');
Route::post('/verify/{code}/certificate', [BatchController::class, 'certificate'])->name('verify.certificate');

// ── Public master-carton scan (reached from a carton's QR code) ────────────
Route::get('/carton/{qr}', [MasterCartonController::class, 'scan'])->name('carton.scan');

// ── Public shipment scan (reached from a parent shipment QR code) ──────────
Route::get('/shipment/{qr}', [ConsignmentController::class, 'scan'])->name('shipment.scan');

// ── Customer self-service portal (separate `customer` auth guard) ──────────
Route::prefix('portal')->name('portal.')->group(function () {
    $portal = \App\Http\Controllers\CustomerPortalController::class;

    Route::get('/login', [$portal, 'showLogin'])->name('login');
    Route::post('/login', [$portal, 'login'])->middleware('throttle:10,1')->name('login.post');
    Route::post('/logout', [$portal, 'logout'])->name('logout');

    // Self-registration
    Route::get('/register', [$portal, 'showRegister'])->name('register');
    Route::post('/register', [$portal, 'register'])->middleware('throttle:10,1')->name('register.post');

    // Password reset
    Route::get('/forgot-password', [$portal, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [$portal, 'sendResetLink'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [$portal, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [$portal, 'resetPassword'])->middleware('throttle:6,1')->name('password.update');

    Route::middleware('auth:customer')->group(function () use ($portal) {
        Route::get('/', [$portal, 'dashboard'])->name('dashboard');
        Route::get('/profile', [$portal, 'editProfile'])->name('profile');
        Route::post('/profile', [$portal, 'updateProfile'])->name('profile.update');
        Route::get('/order', [$portal, 'createOrder'])->name('order.create');
        Route::post('/order', [$portal, 'storeOrder'])->name('order.store');
        // View / edit / cancel an existing order (edit gated by PO status)
        Route::get('/orders/{purchaseOrder}', [$portal, 'showOrder'])->name('order.show');
        Route::get('/orders/{purchaseOrder}/edit', [$portal, 'editOrder'])->name('order.edit');
        Route::put('/orders/{purchaseOrder}', [$portal, 'updateOrder'])->name('order.update');
        Route::post('/orders/{purchaseOrder}/cancel', [$portal, 'cancelOrder'])->name('order.cancel');
        Route::post('/notifications/read', [$portal, 'markNotificationsRead'])->name('notifications.read');
        Route::get('/invoices/{type}/{id}/pdf', [$portal, 'invoicePdf'])->whereIn('type', ['ci', 'pi'])->name('invoice.pdf');
    });
});

// ── Main Application (auth required + per-module permission gate) ──────────
Route::middleware(['auth', 'module'])->group(function () {

    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // ── Products & Batches ────────────────────────────────────────────────
    // Full resource: index, store, show (JSON), update, destroy
    // 'create' and 'edit' are omitted — handled by inline modals in the view
    Route::resource('products', ProductController::class)
         ->only(['index', 'store', 'show', 'update', 'destroy'])
         ->names('products');

    Route::get('/batches', [BatchController::class, 'index'])->name('batches');
    Route::post('/batches', [BatchController::class, 'store'])->name('batches.store');
    Route::get('/batches/{batch}/units', [BatchController::class, 'units'])->name('batches.units');
    Route::get('/batches/{batch}/labels', [BatchController::class, 'labels'])->name('batches.labels');
    Route::get('/batches/{batch}/export', [BatchController::class, 'export'])->name('batches.export');
    Route::get('/batches/{batch}/logs', [BatchController::class, 'logs'])->name('batches.logs');
    Route::get('/batches/{batch}/tracking', [BatchController::class, 'tracking'])->name('batches.tracking');
    Route::get('/batches/{batch}/tracking/export', [BatchController::class, 'trackingExport'])->name('batches.tracking.export');
    Route::get('/batches/{batch}/export-scopes', [BatchController::class, 'exportScopes'])->name('batches.export-scopes');
    Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
    Route::put('/batches/{batch}', [BatchController::class, 'update'])->name('batches.update');
    Route::delete('/batches/{batch}', [BatchController::class, 'destroy'])->name('batches.destroy');

    // ── Partial Batch Quantity (Batch Quantity Extension) ─────────────────
    Route::get('/partial-batches', [PartialBatchController::class, 'index'])->name('partial-batches');
    Route::get('/partial-batches/products/{product}/batches', [PartialBatchController::class, 'batches'])->name('partial-batches.batches');
    Route::get('/partial-batches/batches/{batch}/info', [PartialBatchController::class, 'batchInfo'])->name('partial-batches.batch-info');
    Route::post('/partial-batches', [PartialBatchController::class, 'store'])->name('partial-batches.store');

    // ── Batch Downloads (export center) ───────────────────────────────────
    Route::get('/batch-downloads', [BatchDownloadController::class, 'index'])->name('batch-downloads');

    // ── Master Carton Management (Factory → Depot) ────────────────────────
    Route::get('/master-cartons', [MasterCartonController::class, 'index'])->name('master-cartons');
    Route::post('/master-cartons', [MasterCartonController::class, 'store'])->name('master-cartons.store');
    Route::get('/master-cartons/create-packed', [MasterCartonController::class, 'packedForm'])->name('master-cartons.create-packed');
    Route::post('/master-cartons/create-packed', [MasterCartonController::class, 'storePacked'])->name('master-cartons.store-packed');
    Route::get('/master-cartons/packing-cartons', [MasterCartonController::class, 'packingCartons'])->name('master-cartons.packing-cartons');
    Route::get('/master-cartons/batch-summary', [MasterCartonController::class, 'batchSummaryView'])->name('master-cartons.batch-summary');
    Route::get('/master-cartons/batch-summary-page', [MasterCartonController::class, 'batchSummaryPage'])->name('master-cartons.batch-summary-page');
    Route::get('/master-cartons/batch/{batch}/cartons', [MasterCartonController::class, 'batchCartons'])->name('master-cartons.batch-cartons');
    Route::post('/master-cartons/contents', [MasterCartonController::class, 'addContent'])->name('master-cartons.contents.add');
    Route::post('/master-cartons/contents-serials', [MasterCartonController::class, 'addContentSerials'])->name('master-cartons.contents.add-serials');
    Route::delete('/master-cartons/contents/{content}', [MasterCartonController::class, 'removeContent'])->name('master-cartons.contents.remove');
    Route::get('/master-cartons/labels-center', [MasterCartonController::class, 'labelsCenter'])->name('master-cartons.labels-center');
    Route::get('/master-cartons/labels', [MasterCartonController::class, 'labels'])->name('master-cartons.labels');
    Route::get('/master-cartons/labels/pdf', [MasterCartonController::class, 'labelsPdf'])->name('master-cartons.labels-pdf');
    Route::get('/master-cartons/batches/{batch}/pack-info', [MasterCartonController::class, 'batchPackInfo'])->name('master-cartons.pack-info');
    Route::get('/master-cartons/{masterCarton}/serials-pdf', [MasterCartonController::class, 'serialsPdf'])->name('master-cartons.serials-pdf');
    Route::get('/master-cartons/{masterCarton}/contents', [MasterCartonController::class, 'cartonContents'])->name('master-cartons.contents');
    Route::get('/master-cartons/{masterCarton}', [MasterCartonController::class, 'show'])->name('master-cartons.show');
    Route::post('/master-cartons/{masterCarton}/move', [MasterCartonController::class, 'move'])->name('master-cartons.move');
    Route::delete('/master-cartons/{masterCarton}', [MasterCartonController::class, 'destroy'])->name('master-cartons.destroy');

    // ── Master Data (add countries & therapeutic classes dynamically) ──────
    Route::prefix('master')->name('master.')->group(function () {
        Route::get('countries', [CountryController::class, 'index'])->name('countries.index');
        Route::post('countries', [CountryController::class, 'store'])->name('countries.store');
        Route::put('countries/{country}', [CountryController::class, 'update'])->name('countries.update');
        Route::delete('countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy');

        Route::get('therapeutic-classes', [TherapeuticClassController::class, 'index'])->name('tclasses.index');
        Route::post('therapeutic-classes', [TherapeuticClassController::class, 'store'])->name('tclasses.store');
        Route::put('therapeutic-classes/{therapeuticClass}', [TherapeuticClassController::class, 'update'])->name('tclasses.update');
        Route::delete('therapeutic-classes/{therapeuticClass}', [TherapeuticClassController::class, 'destroy'])->name('tclasses.destroy');
    });

    // ── Orders ────────────────────────────────────────────────────────────
    Route::prefix('orders')->name('orders.')->group(function () {
        // Purchase Orders (Phase 1 — wired to real data)
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('po');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('po.store');
        Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('po.show');
        Route::post('/purchase-orders/{purchaseOrder}/status', [PurchaseOrderController::class, 'updateStatus'])->name('po.status');
        Route::delete('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('po.destroy');
        Route::get('/purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'pdf'])->name('po.pdf');
        Route::post('/purchase-orders/{purchaseOrder}/documents', [PurchaseOrderController::class, 'storeDoc'])->name('po.documents.store');
        Route::get('/order-documents/{document}/download', [PurchaseOrderController::class, 'downloadDoc'])->name('po.documents.download');
        Route::delete('/order-documents/{document}', [PurchaseOrderController::class, 'destroyDoc'])->name('po.documents.destroy');

        // Sales Orders (Phase 2 — wired, with serial allocation)
        Route::get('/sales-orders', [SalesOrderController::class, 'index'])->name('so');
        Route::post('/sales-orders', [SalesOrderController::class, 'store'])->name('so.store');
        Route::get('/sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('so.show');
        Route::post('/sales-orders/{salesOrder}/status', [SalesOrderController::class, 'updateStatus'])->name('so.status');
        Route::get('/sales-orders/{salesOrder}/pdf', [SalesOrderController::class, 'pdf'])->name('so.pdf');
        Route::post('/sales-orders/{salesOrder}/documents', [SalesOrderController::class, 'storeDoc'])->name('so.documents.store');
        Route::get('/so-documents/{document}/download', [SalesOrderController::class, 'downloadDoc'])->name('so.documents.download');
        Route::delete('/so-documents/{document}', [SalesOrderController::class, 'destroyDoc'])->name('so.documents.destroy');

        // Proforma Invoices (Phase 3 — wired, finance approval workflow)
        Route::get('/proforma-invoices', [ProformaInvoiceController::class, 'index'])->name('pi');
        Route::post('/proforma-invoices', [ProformaInvoiceController::class, 'store'])->name('pi.store');
        Route::get('/proforma-invoices/{proformaInvoice}', [ProformaInvoiceController::class, 'show'])->name('pi.show');
        Route::post('/proforma-invoices/{proformaInvoice}/status', [ProformaInvoiceController::class, 'updateStatus'])->name('pi.status');
        Route::get('/proforma-invoices/{proformaInvoice}/pdf', [ProformaInvoiceController::class, 'pdf'])->name('pi.pdf');
        Route::post('/proforma-invoices/{proformaInvoice}/documents', [ProformaInvoiceController::class, 'storeDoc'])->name('pi.documents.store');
        Route::get('/pi-documents/{document}/download', [ProformaInvoiceController::class, 'downloadDoc'])->name('pi.documents.download');
        Route::delete('/pi-documents/{document}', [ProformaInvoiceController::class, 'destroyDoc'])->name('pi.documents.destroy');

        // Commercial Invoices (Phase 4 — partial CIs)
        Route::get('/commercial-invoices', [CommercialInvoiceController::class, 'index'])->name('ci');
        Route::post('/commercial-invoices', [CommercialInvoiceController::class, 'store'])->name('ci.store');
        Route::get('/commercial-invoices/{commercialInvoice}', [CommercialInvoiceController::class, 'show'])->name('ci.show');
        Route::post('/commercial-invoices/{commercialInvoice}/status', [CommercialInvoiceController::class, 'updateStatus'])->name('ci.status');
        Route::get('/commercial-invoices/{commercialInvoice}/pdf', [CommercialInvoiceController::class, 'pdf'])->name('ci.pdf');
        Route::post('/commercial-invoices/{commercialInvoice}/documents', [CommercialInvoiceController::class, 'storeDoc'])->name('ci.documents.store');
        Route::get('/ci-documents/{document}/download', [CommercialInvoiceController::class, 'downloadDoc'])->name('ci.documents.download');
        Route::delete('/ci-documents/{document}', [CommercialInvoiceController::class, 'destroyDoc'])->name('ci.documents.destroy');
        // Payments (accountant) against a commercial invoice
        Route::post('/commercial-invoices/{commercialInvoice}/payments', [CommercialInvoiceController::class, 'storePayment'])->name('ci.payments.store');
        Route::delete('/invoice-payments/{payment}', [CommercialInvoiceController::class, 'destroyPayment'])->name('ci.payments.destroy');
    });

    // ── Promo Codes (sales discounts applied at order placement) ──────────────
    Route::get('/promo-codes', [\App\Http\Controllers\PromoCodeController::class, 'index'])->name('promo-codes.index');
    Route::get('/promo-codes/generate', [\App\Http\Controllers\PromoCodeController::class, 'generate'])->name('promo-codes.generate');
    Route::post('/promo-codes/bulk', [\App\Http\Controllers\PromoCodeController::class, 'bulkStore'])->name('promo-codes.bulk');
    Route::post('/promo-codes', [\App\Http\Controllers\PromoCodeController::class, 'store'])->name('promo-codes.store');
    Route::put('/promo-codes/{promoCode}', [\App\Http\Controllers\PromoCodeController::class, 'update'])->name('promo-codes.update');
    Route::post('/promo-codes/{promoCode}/toggle', [\App\Http\Controllers\PromoCodeController::class, 'toggle'])->name('promo-codes.toggle');
    Route::delete('/promo-codes/{promoCode}', [\App\Http\Controllers\PromoCodeController::class, 'destroy'])->name('promo-codes.destroy');

    // ── Shipments / Consignments (parent aggregation over master cartons) ──
    Route::get('/shipments', [ConsignmentController::class, 'index'])->name('shipments');
    Route::get('/shipments/receiving', [ConsignmentController::class, 'receiving'])->name('shipments.receiving');
    Route::post('/shipments', [ConsignmentController::class, 'store'])->name('shipments.store');
    Route::get('/shipments/available-cartons', [ConsignmentController::class, 'availableCartons'])->name('shipments.available-cartons');
    Route::get('/shipments/labels', [ConsignmentController::class, 'labels'])->name('shipments.labels');
    Route::get('/shipments/labels/pdf', [ConsignmentController::class, 'labelsPdf'])->name('shipments.labels-pdf');
    Route::get('/shipments/{consignment}', [ConsignmentController::class, 'show'])->name('shipments.show');
    Route::post('/shipments/{consignment}/cartons', [ConsignmentController::class, 'addCartons'])->name('shipments.cartons.add');
    Route::delete('/shipments/{consignment}/cartons/{masterCarton}', [ConsignmentController::class, 'removeCarton'])->name('shipments.cartons.remove');
    Route::post('/shipments/{consignment}/move', [ConsignmentController::class, 'move'])->name('shipments.move');
    Route::post('/shipments/{consignment}/cartons/{masterCarton}/receive', [ConsignmentController::class, 'receiveCarton'])->name('shipments.cartons.receive');
    Route::delete('/shipments/{consignment}', [ConsignmentController::class, 'destroy'])->name('shipments.destroy');

    // ── Distribution dashboard + universal traceability search ────────────
    Route::get('/distribution', [DistributionController::class, 'index'])->name('distribution');
    Route::get('/distribution/lookup', [DistributionController::class, 'lookup'])->name('distribution.lookup');

    // ── Countries ─────────────────────────────────────────────────────────
    Route::get('/countries', function () {
        return view('countries');
    })->name('countries');

    // ── Anti-Counterfeit Verification & Intelligence System ───────────────
    Route::prefix('anti-counterfeit')->name('anticounterfeit.')->group(function () {
        Route::get('/', [AntiCounterfeitController::class, 'dashboard'])->name('dashboard');
        Route::get('/verification-logs', [AntiCounterfeitController::class, 'verificationLogs'])->name('logs');
        Route::get('/risk-alerts', [AntiCounterfeitController::class, 'riskAlerts'])->name('alerts');
        Route::get('/investigations', [AntiCounterfeitController::class, 'investigationCenter'])->name('investigations');
        Route::put('/alerts/{alert}', [AntiCounterfeitController::class, 'updateAlert'])->name('alerts.update');
        Route::get('/counterfeit-cases', [AntiCounterfeitController::class, 'counterfeitCases'])->name('cases');
        Route::get('/live-map', [AntiCounterfeitController::class, 'liveMap'])->name('map');
        Route::get('/country-authorization', [AntiCounterfeitController::class, 'countryAuthorization'])->name('countries');
        Route::post('/country-authorization', [AntiCounterfeitController::class, 'storeCountryAuth'])->name('countries.store');
        Route::delete('/country-authorization/{authorization}', [AntiCounterfeitController::class, 'destroyCountryAuth'])->name('countries.destroy');
        Route::get('/access-control', [AntiCounterfeitController::class, 'accessControl'])->name('policies');
        Route::get('/access-control/export', [AntiCounterfeitController::class, 'exportPolicies'])->name('policies.export');
        Route::post('/access-control', [AntiCounterfeitController::class, 'storePolicy'])->name('policies.store');
        Route::post('/access-control/global', [AntiCounterfeitController::class, 'saveGlobalSetting'])->name('policies.global');
        Route::post('/access-control/scan-intelligence', [AntiCounterfeitController::class, 'saveScanIntelligence'])->name('policies.intel');
        Route::post('/access-control/scope-permission', [AntiCounterfeitController::class, 'saveScopePermission'])->name('policies.scope');
        Route::post('/access-control/unit-permission', [AntiCounterfeitController::class, 'saveUnitPermission'])->name('policies.unit');
        Route::put('/access-control/{policy}', [AntiCounterfeitController::class, 'updatePolicy'])->name('policies.update');
        Route::post('/access-control/{policy}/toggle', [AntiCounterfeitController::class, 'togglePolicy'])->name('policies.toggle');
        Route::delete('/access-control/{policy}', [AntiCounterfeitController::class, 'destroyPolicy'])->name('policies.destroy');
        Route::get('/recalled-batches', [AntiCounterfeitController::class, 'recalledBatches'])->name('recalls');
        Route::post('/recalled-batches', [AntiCounterfeitController::class, 'storeRecall'])->name('recalls.store');
        Route::post('/recalled-batches/{recall}/toggle', [AntiCounterfeitController::class, 'toggleRecall'])->name('recalls.toggle');
        Route::get('/customer-reports', [AntiCounterfeitController::class, 'customerReports'])->name('reports-list');
        Route::put('/customer-reports/{report}', [AntiCounterfeitController::class, 'updateReport'])->name('reports-list.update');
        Route::get('/uuc-management', [AntiCounterfeitController::class, 'uucManagement'])->name('uuc');
        Route::get('/uuc-management/{unit}/report', [AntiCounterfeitController::class, 'uucReport'])->name('uuc.report');
        Route::post('/quick-lock/unit/{unit}', [AntiCounterfeitController::class, 'quickLockUnit'])->name('quicklock.unit');
        Route::post('/uuc-management/bulk-lock', [AntiCounterfeitController::class, 'bulkLockUnits'])->name('uuc.bulk');
        Route::get('/device-intelligence', [AntiCounterfeitController::class, 'deviceIntelligence'])->name('devices');
        Route::get('/geo-intelligence', [AntiCounterfeitController::class, 'geoIntelligence'])->name('geo');
        Route::get('/reports', [AntiCounterfeitController::class, 'reports'])->name('reports');
        Route::get('/verification-page', [AntiCounterfeitController::class, 'settings'])->name('verification-page');
        Route::post('/verification-page', [AntiCounterfeitController::class, 'updateSettings'])->name('verification-page.update');
        Route::post('/verification-page/reset', [AntiCounterfeitController::class, 'resetSettings'])->name('verification-page.reset');
        Route::post('/verification-page/template', [AntiCounterfeitController::class, 'applyTemplate'])->name('verification-page.template');
    });

    // ── Document Vault ────────────────────────────────────────────────────
    Route::get('/vault', function () {
        return view('vault');
    })->name('vault');

    // ── Country Managers (team) ───────────────────────────────────────────
    Route::prefix('country-managers')->name('country-managers.')->group(function () {
        Route::get('/', [CountryManagerController::class, 'index'])->name('index');
        Route::post('/', [CountryManagerController::class, 'store'])->name('store');
        Route::put('/{manager}', [CountryManagerController::class, 'update'])->name('update');
        Route::delete('/{manager}', [CountryManagerController::class, 'destroy'])->name('destroy');
    });

    // ── Customers & customer-wise sales ───────────────────────────────────
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/trace', [CustomerController::class, 'trace'])->name('trace');
        // Customer portal management (super admin only — gated in controller)
        Route::get('/portal-settings', [\App\Http\Controllers\PortalSettingsController::class, 'edit'])->name('portal-settings');
        Route::post('/portal-settings', [\App\Http\Controllers\PortalSettingsController::class, 'update'])->name('portal-settings.update');
        Route::post('/sales', [CustomerController::class, 'storeSale'])->name('sales.store');
        Route::get('/sales/{sale}', [CustomerController::class, 'showSale'])->name('sales.show');
        // Bulk actions + one-click approve
        Route::post('/bulk', [CustomerController::class, 'bulkAction'])->name('bulk');
        Route::post('/{customer}/approve', [CustomerController::class, 'approve'])->name('approve');
        Route::post('/{customer}/reset-password', [CustomerController::class, 'sendResetLink'])->name('reset-password');
        // Shared documents (staff → customer portal)
        Route::post('/{customer}/documents', [CustomerController::class, 'uploadDocument'])->name('documents.store');
        Route::delete('/documents/{document}', [CustomerController::class, 'destroyDocument'])->name('documents.destroy');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
    });

    // ── Patient Portal ────────────────────────────────────────────────────
    Route::get('/patients', function () {
        return view('patients');
    })->name('patients');

    // ── Reports ───────────────────────────────────────────────────────────
    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports');
    Route::get('/reports/export/csv', [\App\Http\Controllers\ReportController::class, 'csv'])->name('reports.csv');
    Route::get('/reports/export/pdf', [\App\Http\Controllers\ReportController::class, 'pdf'])->name('reports.pdf');

    // ── Notifications ─────────────────────────────────────────────────────
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/{notification}/dismiss', [\App\Http\Controllers\NotificationController::class, 'dismiss'])->name('notifications.dismiss');

    // ── Users & Roles (Spatie role-based access) ──────────────────────────
    // 1) Create Role — name-only CRUD
    Route::get('/roles', [\App\Http\Controllers\RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [\App\Http\Controllers\RoleController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [\App\Http\Controllers\RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [\App\Http\Controllers\RoleController::class, 'destroy'])->name('roles.destroy');

    // 2) Permission Set — pick a role, check modules, submit
    Route::get('/roles-permissions', [\App\Http\Controllers\RoleController::class, 'permissions'])->name('roles.permissions');
    Route::put('/roles/{role}/permissions', [\App\Http\Controllers\RoleController::class, 'syncPermissions'])->name('roles.permissions.update');

    // 3) Users — add users and assign a role
    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/reset-password', [UserController::class, 'sendResetLink'])->name('users.reset-password');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

});
