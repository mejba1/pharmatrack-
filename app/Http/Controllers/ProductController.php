<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'dosage_form', 'status', 'therapeutic_class']);

        $products = Product::with('primaryImage')
                    ->withCount([
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

    // ── Store ─────────────────────────────────────────────────────────────

    public function store(StoreProductRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        // Auto-generate PRN
        $countryCode = strtoupper($data['country_of_origin'] ?? 'XX');
        $formAbbr    = Product::formAbbreviations()[$data['dosage_form']] ?? 'GEN';
        $data['prn'] = Product::generatePrn($countryCode, $formAbbr);

        // Defaults
        $data['status']                  ??= 'active';
        $data['controlled_substance']    ??= 'no';
        $data['temperature_sensitivity'] ??= 'ambient';
        $data['unit_of_measure']         ??= 'unit';

        // Strip non-column keys before insert
        unset($data['images'], $data['primary_image_index'], $data['remove_images']);

        $product = Product::create($data);

        // Handle uploaded images
        if ($request->hasFile('images')) {
            $primaryIdx = (int) $request->input('primary_image_index', 0);
            foreach ($request->file('images') as $i => $file) {
                $this->saveImage($product, $file, isPrimary: $i === $primaryIdx);
            }
        }

        $message = "Product '{$product->name}' ({$product->prn}) created successfully.";

        if ($request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => $message,
                'redirect' => route('products.index'),
            ]);
        }

        return redirect()->route('products.index')->with('success', $message);
    }

    // ── Show (JSON for AJAX modals) ───────────────────────────────────────

    public function show(Product $product): JsonResponse
    {
        $product->loadCount([
            'countryRegistrations as countries_count' => fn ($q) => $q->where('status', 'approved'),
        ]);
        $product->load(['images']);

        // Build images array with public URLs
        $images = $product->images->map(fn ($img) => [
            'id'         => $img->id,
            'url'        => $img->url,
            'filename'   => $img->filename,
            'name'       => $img->original_name,
            'size_human' => $img->size_human,
            'is_primary' => $img->is_primary,
            'sort_order' => $img->sort_order,
            'toRemove'   => false,
        ])->values()->toArray();

        return response()->json([
            ...$product->toArray(),
            'images'             => $images,
            'cold_chain'         => $product->cold_chain,
            'dosage_form_label'  => $product->dosage_form_label,
            'status_label'       => $product->status_label,
            'status_badge_class' => $product->status_badge_class,
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────

    public function update(UpdateProductRequest $request, Product $product): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        // Strip non-column keys
        unset($data['images'], $data['primary_image_index'], $data['primary_image_id'],
              $data['remove_images']);

        $product->update($data);

        // 1. Remove flagged images
        if ($request->filled('remove_images')) {
            $toRemove = $product->images()->whereIn('id', $request->input('remove_images'))->get();
            foreach ($toRemove as $img) {
                $img->deleteFile();
                $img->delete();
            }
        }

        // 2. Set primary image (existing)
        if ($request->filled('primary_image_id')) {
            $product->images()->update(['is_primary' => false]);
            $product->images()->where('id', $request->input('primary_image_id'))
                    ->update(['is_primary' => true]);
        }

        // 3. Upload new images
        if ($request->hasFile('images')) {
            $hasPrimary = $product->images()->where('is_primary', true)->exists();
            foreach ($request->file('images') as $i => $file) {
                $this->saveImage($product, $file, isPrimary: !$hasPrimary && $i === 0);
                $hasPrimary = true;
            }
        }

        $message = "Product '{$product->name}' updated successfully.";

        if ($request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => $message,
                'redirect' => route('products.index'),
            ]);
        }

        return redirect()->route('products.index')->with('success', $message);
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;
        // Delete physical images before soft-deleting product
        foreach ($product->images as $img) {
            $img->deleteFile();
            $img->delete();
        }
        $product->delete();

        return redirect()->route('products.index')
                         ->with('success', "Product '{$name}' has been removed.");
    }

    // ── Private Helpers ───────────────────────────────────────────────────

    private function saveImage(Product $product, UploadedFile $file, bool $isPrimary = false): ProductImage
    {
        $ext      = $file->getClientOriginalExtension();
        $slug     = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = time() . '_' . uniqid() . '_' . $slug . '.' . $ext;
        $path     = $file->storeAs("products/{$product->id}", $filename, 'public');

        $sortOrder = $product->images()->count();

        return ProductImage::create([
            'product_id'    => $product->id,
            'filename'      => $filename,
            'original_name' => $file->getClientOriginalName(),
            'path'          => $path,
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
            'is_primary'    => $isPrimary,
            'sort_order'    => $sortOrder,
        ]);
    }
}
