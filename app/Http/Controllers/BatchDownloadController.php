<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

/**
 * Batch Downloads — a standalone export center. Pick a product, then a batch,
 * then a scope (full batch / original units / a specific partial batch) and
 * download the serialized unit codes as Text / Excel / PDF.
 */
class BatchDownloadController extends Controller
{
    public function index(): View
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'prn']);

        return view('batch-downloads', compact('products'));
    }
}
