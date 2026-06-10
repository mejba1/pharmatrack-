<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    /**
     * Display paginated product list with search / filter.
     *
     * GET /products
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'dosage_form', 'status']);

        $products = Product::withCount([
                        'countryRegistrations as countries_count' => fn ($q) => $q->where('status', 'approved'),
                    ])
                    ->filter($filters)
                    ->latest()
                    ->paginate(15)
                    ->withQueryString();

        $stats = [
            'total'        => Product::count(),
            'active'       => Product::where('status', 'active')->count(),
            'pending'      => Product::where('status', 'pending_approval')->count(),
            'discontinued' => Product::where('status', 'discontinued')->count(),
        ];

        return view('products', compact('products', 'stats', 'filters'));
    }

    /**
     * Save a new product.
     *
     * POST /products
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Auto-generate PRN from country_of_origin + dosage_form
        $countryCode = strtoupper($data['country_of_origin'] ?? 'XX');
        $formAbbr    = Product::formAbbreviations()[$data['dosage_form']] ?? 'GEN';
        $data['prn'] = Product::generatePrn($countryCode, $formAbbr);

        // Defaults
        $data['status']                  = $data['status']                  ?? 'active';
        $data['controlled_substance']    = $data['controlled_substance']    ?? 'no';
        $data['temperature_sensitivity'] = $data['temperature_sensitivity'] ?? 'ambient';
        $data['unit_of_measure']         = $data['unit_of_measure']         ?? 'unit';

        $product = Product::create($data);

        return redirect()
            ->route('products.index')
            ->with('success', "Product '{$product->name}' ({$product->prn}) created successfully.");
    }

    /**
     * Return product JSON for the view modal (AJAX).
     *
     * GET /products/{product}
     */
    public function show(Product $product): JsonResponse
    {
        $product->loadCount([
            'countryRegistrations as countries_count' => fn ($q) => $q->where('status', 'approved'),
        ]);
        $product->load('countryRegistrations.country');

        return response()->json(array_merge($product->toArray(), [
            'cold_chain'         => $product->cold_chain,
            'dosage_form_label'  => $product->dosage_form_label,
            'status_label'       => $product->status_label,
            'status_badge_class' => $product->status_badge_class,
        ]));
    }

    /**
     * Update an existing product.
     *
     * PUT /products/{product}
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('products.index')
            ->with('success', "Product '{$product->name}' updated successfully.");
    }

    /**
     * Soft-delete a product.
     *
     * DELETE /products/{product}
     */
    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', "Product '{$name}' has been removed.");
    }
}
