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
use App\Http\Controllers\AntiCounterfeitController;

/*
|--------------------------------------------------------------------------
| PharmaTrack Web Routes
|--------------------------------------------------------------------------
*/

// ── Auth ─────────────────────────────────────────────────────────────────
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    // TODO: real auth logic
    return redirect()->route('dashboard');
})->name('login.post');

Route::post('/logout', function () {
    return redirect()->route('login');
})->name('logout');

// ── Public product verification (reached from a unit's QR code) ────────────
Route::get('/verify/{code}', [BatchController::class, 'verify'])->name('verify');
Route::post('/verify/{code}/report', [BatchController::class, 'report'])->name('verify.report');
Route::post('/verify/{code}/request-info', [BatchController::class, 'requestInfo'])->name('verify.request-info');
Route::post('/verify/{code}/certificate', [BatchController::class, 'certificate'])->name('verify.certificate');

// ── Public master-carton scan (reached from a carton's QR code) ────────────
Route::get('/carton/{qr}', [MasterCartonController::class, 'scan'])->name('carton.scan');

// ── Public shipment scan (reached from a parent shipment QR code) ──────────
Route::get('/shipment/{qr}', [ConsignmentController::class, 'scan'])->name('shipment.scan');

// ── Main Application ──────────────────────────────────────────────────────
Route::middleware([])->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

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
        Route::get('/purchase-orders', function () {
            return view('orders.po');
        })->name('po');

        Route::get('/sales-orders', function () {
            return view('orders.so');
        })->name('so');

        Route::get('/proforma-invoices', function () {
            return view('orders.pi');
        })->name('pi');

        Route::get('/commercial-invoices', function () {
            return view('orders.ci');
        })->name('ci');
    });

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

    // ── Patient Portal ────────────────────────────────────────────────────
    Route::get('/patients', function () {
        return view('patients');
    })->name('patients');

    // ── Reports ───────────────────────────────────────────────────────────
    Route::get('/reports', function () {
        return view('reports');
    })->name('reports');

    // ── Notifications ─────────────────────────────────────────────────────
    Route::get('/notifications', function () {
        return view('notifications');
    })->name('notifications');

    // ── Users & Roles ─────────────────────────────────────────────────────
    Route::get('/users', function () {
        return view('users');
    })->name('users');

});
