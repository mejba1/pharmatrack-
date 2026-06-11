<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BatchController;
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
    Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
    Route::put('/batches/{batch}', [BatchController::class, 'update'])->name('batches.update');
    Route::delete('/batches/{batch}', [BatchController::class, 'destroy'])->name('batches.destroy');

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

    // ── Shipments ─────────────────────────────────────────────────────────
    Route::get('/shipments', function () {
        return view('shipments');
    })->name('shipments');

    // ── Distribution ─────────────────────────────────────────────────────
    Route::get('/distribution', function () {
        return view('distribution');
    })->name('distribution');

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
