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
    Route::get('/master-cartons/packing-cartons', [MasterCartonController::class, 'packingCartons'])->name('master-cartons.packing-cartons');
    Route::post('/master-cartons/contents', [MasterCartonController::class, 'addContent'])->name('master-cartons.contents.add');
    Route::delete('/master-cartons/contents/{content}', [MasterCartonController::class, 'removeContent'])->name('master-cartons.contents.remove');
    Route::get('/master-cartons/labels', [MasterCartonController::class, 'labels'])->name('master-cartons.labels');
    Route::get('/master-cartons/labels/pdf', [MasterCartonController::class, 'labelsPdf'])->name('master-cartons.labels-pdf');
    Route::get('/master-cartons/batches/{batch}/pack-info', [MasterCartonController::class, 'batchPackInfo'])->name('master-cartons.pack-info');
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

    // ── Anti-Counterfeit ──────────────────────────────────────────────────
    Route::get('/anti-counterfeit', function () {
        return view('anticounterfeit');
    })->name('anticounterfeit');

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
