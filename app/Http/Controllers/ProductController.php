<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Country;
use App\Models\TherapeuticClass;
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

        // Dynamic option lists (grow as users add new values)
        $therapeuticClasses = $this->therapeuticClassOptions();
        $countries          = Country::orderBy('name')->pluck('name')->values();

        return view('products', compact(
            'products', 'stats', 'filters', 'therapeuticClasses', 'countries'
        ));
    }

    /**
     * Therapeutic-class options sourced from the database: the managed
     * Master Data list, plus any legacy values already stored on products
     * (so editing an older record never loses its current value).
     */
    private function therapeuticClassOptions(): array
    {
        $fromTable = TherapeuticClass::where('is_active', true)->pluck('name')->all();

        $fromProducts = Product::query()
            ->whereNotNull('therapeutic_class')
            ->where('therapeutic_class', '<>', '')
            ->distinct()
            ->pluck('therapeutic_class')
            ->all();

        return collect($fromTable)->merge($fromProducts)
            ->map(fn ($v) => trim($v))
            ->unique()
            ->sort(SORT_FLAG_CASE | SORT_NATURAL)
            ->values()
            ->all();
    }

    // ── Store ─────────────────────────────────────────────────────────────

    public function store(StoreProductRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        // Resolve Country of Origin from the chosen/typed country name → store its code
        if ($request->filled('country_of_origin_name')) {
            $data['country_of_origin'] = $this->resolveCountry($request->input('country_of_origin_name'))->code;
        }

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
        unset($data['images'], $data['primary_image_index'], $data['remove_images'],
              $data['pdf'], $data['country_of_origin_name'], $data['countries']);

        $product = Product::create($data);

        // Sync registered/marketed countries (creates any new ones on the fly)
        $this->syncCountries($product, $request->input('countries', []));

        // Handle uploaded images
        if ($request->hasFile('images')) {
            $primaryIdx = (int) $request->input('primary_image_index', 0);
            foreach ($request->file('images') as $i => $file) {
                $this->saveImage($product, $file, isPrimary: $i === $primaryIdx);
            }
        }

        // Handle uploaded PDF document
        if ($request->hasFile('pdf')) {
            $product->update(['pdf_path' => $this->savePdf($product, $request->file('pdf'))]);
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
            'pdf_url'            => $product->pdf_url,
            'pdf_name'           => $product->pdf_name,
            'country_of_origin_name' => $this->originDisplayName($product),
            'registered_countries'   => $product->countryRegistrations()
                                                 ->with('country')->get()
                                                 ->map(fn ($r) => $r->country?->name)
                                                 ->filter()->values(),
        ]);
    }

    /**
     * Display name for the product's country of origin: resolve the stored
     * code to a country name, falling back to the raw stored value.
     */
    private function originDisplayName(Product $product): ?string
    {
        if (!$product->country_of_origin) {
            return null;
        }
        $country = Country::whereRaw('UPPER(code) = ?', [mb_strtoupper($product->country_of_origin)])->first();

        return $country?->name ?? $product->country_of_origin;
    }

    // ── Update ────────────────────────────────────────────────────────────

    public function update(UpdateProductRequest $request, Product $product): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        // Resolve Country of Origin from the chosen/typed country name → store its code
        if ($request->filled('country_of_origin_name')) {
            $data['country_of_origin'] = $this->resolveCountry($request->input('country_of_origin_name'))->code;
        } elseif ($request->has('country_of_origin_name')) {
            $data['country_of_origin'] = null;
        }

        // Strip non-column keys
        unset($data['images'], $data['primary_image_index'], $data['primary_image_id'],
              $data['remove_images'], $data['pdf'], $data['remove_pdf'],
              $data['country_of_origin_name'], $data['countries']);

        $product->update($data);

        // Sync registered/marketed countries (creates any new ones on the fly)
        if ($request->has('countries')) {
            $this->syncCountries($product, $request->input('countries', []));
        }

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

        // 4. PDF document — remove flagged and/or replace with a new upload
        if ($request->boolean('remove_pdf') && $product->pdf_path) {
            Storage::disk('public')->delete($product->pdf_path);
            $product->update(['pdf_path' => null]);
        }
        if ($request->hasFile('pdf')) {
            if ($product->pdf_path) {
                Storage::disk('public')->delete($product->pdf_path);
            }
            $product->update(['pdf_path' => $this->savePdf($product, $request->file('pdf'))]);
        }

        $message = "Product '{$product->name}' updated successfully.";

        if ($request->wantsJson()) {
            // Flash so the success banner shows after the JS redirect to the index.
            session()->flash('success', $message);

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
        // Delete the uploaded PDF document, if any
        if ($product->pdf_path) {
            Storage::disk('public')->delete($product->pdf_path);
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

    private function savePdf(Product $product, UploadedFile $file): string
    {
        $slug     = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = time() . '_' . uniqid() . '_' . ($slug ?: 'document') . '.pdf';

        return $file->storeAs("products/{$product->id}/docs", $filename, 'public');
    }

    /**
     * Find a country by name (or code), creating it on the fly if missing.
     */
    private function resolveCountry(string $name): Country
    {
        $name = trim($name);

        $country = Country::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                          ->orWhereRaw('UPPER(code) = ?', [mb_strtoupper($name)])
                          ->first();

        return $country ?? Country::create([
            'code'              => $this->uniqueCountryCode($name),
            'name'              => $name,
            'regulatory_status' => 'approved',
            'is_active'         => true,
        ]);
    }

    /**
     * Generate a unique 3-char country code derived from the name.
     */
    private function uniqueCountryCode(string $name): string
    {
        $base      = strtoupper(preg_replace('/[^A-Za-z]/', '', $name)) ?: 'XXX';
        $code      = str_pad(substr($base, 0, 3), 3, 'X');
        $candidate = $code;
        $i         = 0;

        while (Country::where('code', $candidate)->exists()) {
            $i++;
            $candidate = $i <= 9
                ? substr($code, 0, 2) . $i
                : strtoupper(substr(uniqid(), -3));
        }

        return $candidate;
    }

    /**
     * Sync a product's registered-country list (by name) into
     * product_country_registrations, creating any new countries on the fly.
     */
    private function syncCountries(Product $product, array $names): void
    {
        $ids = [];
        foreach ($names as $n) {
            $n = trim((string) $n);
            if ($n === '') continue;
            $ids[] = $this->resolveCountry($n)->id;
        }
        $ids = array_values(array_unique($ids));

        // Remove registrations no longer selected
        $product->countryRegistrations()
                ->whereNotIn('country_id', $ids ?: [0])
                ->delete();

        // Add newly selected ones
        $existing = $product->countryRegistrations()->pluck('country_id')->all();
        foreach (array_diff($ids, $existing) as $cid) {
            $product->countryRegistrations()->create([
                'country_id' => $cid,
                'status'     => 'approved',
            ]);
        }
    }
}
