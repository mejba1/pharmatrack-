<?php

use Illuminate\Support\Facades\Route;

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

// ── Main Application ──────────────────────────────────────────────────────
Route::middleware([])->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // ── Products & Batches ────────────────────────────────────────────────
    Route::get('/products', function () {
        return view('products');
    })->name('products');

    Route::get('/batches', function () {
        return view('batches');
    })->name('batches');

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
