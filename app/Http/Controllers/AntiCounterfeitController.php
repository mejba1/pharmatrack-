<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchUnit;
use App\Models\CounterfeitReport;
use App\Models\Country;
use App\Models\CountryAuthorization;
use App\Models\Product;
use App\Models\ProductRecall;
use App\Models\RiskAlert;
use App\Models\Setting;
use App\Models\VerificationDailyStat;
use App\Models\VerificationLog;
use App\Models\VerificationPolicy;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Anti-Counterfeit Product Verification & Intelligence System — admin console.
 * Every section under the sidebar "Anti-Counterfeit" submenu is served here.
 */
class AntiCounterfeitController extends Controller
{
    // ── Dashboard ─────────────────────────────────────────────────────────
    public function dashboard(): View
    {
        $today = now()->startOfDay();

        // Heavy COUNT(*)s cached briefly so repeated loads don't re-scan at scale.
        $stats = Cache::remember('ac_dash_stats', 60, fn () => [
            'total'       => VerificationLog::count(),
            'today'       => VerificationLog::where('created_at', '>=', now()->startOfDay())->count(),
            'genuine'     => VerificationLog::where('result', 'genuine')->count(),
            'suspicious'  => VerificationLog::where('result', 'suspicious')->count(),
            'invalid'     => VerificationLog::whereIn('result', ['invalid', 'expired', 'recalled', 'locked', 'blocked'])->count(),
            'open_alerts' => RiskAlert::whereIn('status', ['open', 'investigating'])->count(),
            'critical'    => RiskAlert::where('risk_level', 'critical')->whereIn('status', ['open', 'investigating'])->count(),
            'recalls'     => ProductRecall::where('active', true)->count(),
        ]);

        $topCountries = VerificationLog::selectRaw('country, country_code, COUNT(*) c')
            ->whereNotNull('country')->groupBy('country', 'country_code')
            ->orderByDesc('c')->limit(6)->get();

        $byCategory = RiskAlert::selectRaw('category, COUNT(*) c')
            ->groupBy('category')->orderByDesc('c')->limit(8)->get();

        $recentAlerts = RiskAlert::with('product')->latest()->limit(8)->get();
        $recentScans  = VerificationLog::with('product')->latest()->limit(8)->get();

        // 14-day trend from the pre-aggregated rollup table (scales to millions).
        // Today's bar is topped up live so it stays fresh between hourly rebuilds.
        $trend = VerificationDailyStat::where('day', '>=', now()->subDays(13)->toDateString())
            ->orderBy('day')->pluck('total', 'day')
            ->mapWithKeys(fn ($v, $k) => [substr((string) $k, 0, 10) => $v]);
        $trend[now()->toDateString()] = VerificationLog::where('created_at', '>=', $today)->count();

        return view('anti-counterfeit.dashboard', compact('stats', 'topCountries', 'byCategory', 'recentAlerts', 'recentScans', 'trend'));
    }

    // ── Verification Logs ─────────────────────────────────────────────────
    public function verificationLogs(Request $request): View
    {
        $filters = $request->only(['result', 'country_code', 'search', 'per_page']);
        $pp      = (int) ($filters['per_page'] ?? 20);
        $perPage = in_array($pp, [10, 20, 50, 100], true) ? $pp : 20;

        // Cursor (keyset) pagination — no COUNT(*), no deep OFFSET. Scales to
        // millions of rows. Ordered by the sequential primary key.
        $logs = VerificationLog::with('product')
            ->when($filters['result'] ?? null, fn ($q, $v) => $q->where('result', $v))
            ->when($filters['country_code'] ?? null, fn ($q, $v) => $q->where('country_code', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(fn ($w) =>
                $w->where('uuc_code', 'like', "%{$v}%")->orWhere('verification_number', 'like', "%{$v}%")))
            ->orderByDesc('id')
            ->cursorPaginate($perPage)->withQueryString();

        $countries = VerificationLog::selectRaw('country, country_code')->whereNotNull('country_code')
            ->distinct()->orderBy('country')->get();

        return view('anti-counterfeit.verification-logs', compact('logs', 'filters', 'countries', 'perPage'));
    }

    // ── Risk Alerts ───────────────────────────────────────────────────────
    public function riskAlerts(Request $request): View
    {
        $filters = $request->only(['risk_level', 'status', 'category']);

        $alerts = RiskAlert::with('product')
            ->when($filters['risk_level'] ?? null, fn ($q, $v) => $q->where('risk_level', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->latest()->paginate(25)->withQueryString();

        $categories = RiskAlert::categoryLabels();

        return view('anti-counterfeit.risk-alerts', compact('alerts', 'filters', 'categories'));
    }

    // ── Investigation Center (open / in-progress cases) ───────────────────
    public function investigationCenter(Request $request): View
    {
        $filters = $request->only(['status', 'risk_level']);

        $alerts = RiskAlert::with(['product', 'verificationLog'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['risk_level'] ?? null, fn ($q, $v) => $q->where('risk_level', $v))
            ->orderByRaw("FIELD(risk_level,'critical','high','medium','low')")
            ->latest()->paginate(20)->withQueryString();

        return view('anti-counterfeit.investigation-center', compact('alerts', 'filters'));
    }

    /** Update an alert / investigation (status, assignment, notes, escalate). */
    public function updateAlert(Request $request, RiskAlert $alert): RedirectResponse
    {
        $data = $request->validate([
            'status'      => 'nullable|in:open,investigating,resolved,dismissed',
            'assigned_to' => 'nullable|string|max:120',
            'notes'       => 'nullable|string|max:5000',
            'is_case'     => 'nullable|boolean',
        ]);

        if (($data['status'] ?? null) === 'resolved' && !$alert->resolved_at) {
            $data['resolved_at'] = now();
        }
        $alert->update($data);

        return back()->with('success', "Alert {$alert->alert_number} updated.");
    }

    // ── Counterfeit Cases (escalated alerts) ──────────────────────────────
    public function counterfeitCases(): View
    {
        $cases = RiskAlert::with('product')->where('is_case', true)
            ->latest()->paginate(25);

        return view('anti-counterfeit.counterfeit-cases', compact('cases'));
    }

    // ── Live Scan Map ─────────────────────────────────────────────────────
    public function liveMap(): View
    {
        $points = VerificationLog::whereNotNull('latitude')->whereNotNull('longitude')
            ->latest()->limit(1000)
            ->get(['id', 'latitude', 'longitude', 'country', 'city', 'result', 'uuc_code', 'created_at'])
            ->map(fn ($l) => [
                'lat'    => (float) $l->latitude,
                'lng'    => (float) $l->longitude,
                'result' => $l->result,
                'label'  => trim(($l->city ? $l->city . ', ' : '') . $l->country) . ' · ' . $l->uuc_code,
            ]);

        return view('anti-counterfeit.live-map', compact('points'));
    }

    // ── Country Authorization ─────────────────────────────────────────────
    public function countryAuthorization(Request $request): View
    {
        $products  = Product::orderBy('name')->get(['id', 'name', 'prn']);
        $countries = Country::orderBy('name')->get(['code', 'name', 'flag']);

        $productId = $request->integer('product_id') ?: $products->first()?->id;
        $authorized = $productId
            ? CountryAuthorization::where('product_id', $productId)->orderBy('country_name')->get()
            : collect();

        return view('anti-counterfeit.country-authorization', compact('products', 'countries', 'authorized', 'productId'));
    }

    public function storeCountryAuth(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id'   => 'required|exists:products,id',
            'country_code' => 'required|string|max:2',
        ]);

        $country = Country::where('code', $data['country_code'])->first();
        CountryAuthorization::firstOrCreate(
            ['product_id' => $data['product_id'], 'country_code' => strtoupper($data['country_code'])],
            ['country_name' => $country?->name ?? $data['country_code']]
        );

        return back()->with('success', 'Authorized market added.');
    }

    public function destroyCountryAuth(CountryAuthorization $authorization): RedirectResponse
    {
        $authorization->delete();
        return back()->with('success', 'Authorized market removed.');
    }

    // ── Recalled Batches ──────────────────────────────────────────────────
    public function recalledBatches(): View
    {
        $recalls  = ProductRecall::with(['product', 'batch'])->latest()->paginate(20);
        $products = Product::orderBy('name')->get(['id', 'name']);
        $batches  = Batch::with('product')->orderByDesc('id')->limit(500)->get(['id', 'brn', 'batch_number', 'product_id']);

        return view('anti-counterfeit.recalled-batches', compact('recalls', 'products', 'batches'));
    }

    public function storeRecall(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'scope'        => 'required|in:batch,country,global',
            'product_id'   => 'nullable|exists:products,id',
            'batch_id'     => 'nullable|exists:batches,id',
            'country_code' => 'nullable|string|max:2',
            'severity'     => 'required|in:normal,emergency',
            'reason'       => 'nullable|string|max:2000',
        ]);

        $data['recall_number'] = ProductRecall::nextNumber();
        $data['active']        = true;
        $data['recalled_at']   = now();
        $data['recalled_by']   = 'admin';

        // Keep batch status in sync for batch-scoped recalls.
        if ($data['scope'] === 'batch' && !empty($data['batch_id'])) {
            $batch = Batch::find($data['batch_id']);
            $data['product_id'] = $batch?->product_id;
            $batch?->update(['status' => 'recalled']);
        }

        ProductRecall::create($data);

        return back()->with('success', 'Recall issued.');
    }

    public function toggleRecall(ProductRecall $recall): RedirectResponse
    {
        $recall->update(['active' => !$recall->active]);
        if ($recall->scope === 'batch' && $recall->batch_id && !$recall->active) {
            $recall->batch?->update(['status' => 'active']);
        }
        return back()->with('success', $recall->active ? 'Recall re-activated.' : 'Recall lifted.');
    }

    // ── UUC Management ────────────────────────────────────────────────────
    public function uucManagement(Request $request): View
    {
        $filters = [
            'search'     => trim((string) $request->query('search', '')),
            'product_id' => $request->query('product_id', ''),
            'batch_id'   => $request->query('batch_id', ''),
            'state'      => $request->query('state', ''),     // locked | unlocked | blocked
            'date_from'  => $request->query('date_from', ''),
            'date_to'    => $request->query('date_to', ''),
            'sort'       => $request->query('sort', 'newest'),
            'per_page'   => (int) $request->query('per_page', 30),
        ];
        $perPage = in_array($filters['per_page'], [15, 30, 50, 100], true) ? $filters['per_page'] : 30;

        // Codes currently locked by an active code-scope locked policy.
        $lockedList = VerificationPolicy::where('scope_type', 'codes')
            ->where('locked', true)->where('active', true)
            ->get(['uuc_codes'])
            ->flatMap(fn ($p) => (array) $p->uuc_codes)->unique()->values()->all();
        $lockedCodes = collect($lockedList)->flip();

        // Per-UUC scan aggregate, joined so we can sort by scans / hits / recency.
        $flaggedSql = 'SUM(CASE WHEN result IN ("blocked","locked","recalled","invalid","expired") THEN 1 ELSE 0 END)';
        $agg = VerificationLog::selectRaw("uuc_code,
                COUNT(*) as total,
                COUNT(DISTINCT country_code) as countries,
                COUNT(DISTINCT ip_address) as ips,
                {$flaggedSql} as flagged,
                MAX(created_at) as last_scan")
            ->groupBy('uuc_code');

        $query = BatchUnit::query()
            ->leftJoinSub($agg, 'vs', 'vs.uuc_code', '=', 'batch_units.secret_code')
            ->with('batch.product')
            ->select('batch_units.*', 'vs.total as v_total', 'vs.flagged as v_flagged',
                     'vs.countries as v_countries', 'vs.ips as v_ips', 'vs.last_scan as v_last');

        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $query->where(fn ($q) => $q->where('batch_units.secret_code', 'like', "%{$s}%")
                ->orWhere('batch_units.unique_number', 'like', "%{$s}%"));
        }
        if ($filters['product_id'] !== '') {
            $query->whereHas('batch', fn ($q) => $q->where('product_id', $filters['product_id']));
        }
        if ($filters['batch_id'] !== '') {
            $query->where('batch_units.batch_id', $filters['batch_id']);
        }
        if ($filters['state'] === 'locked') {
            $query->whereIn('batch_units.secret_code', $lockedList ?: ['']);
        } elseif ($filters['state'] === 'unlocked') {
            $query->whereNotIn('batch_units.secret_code', $lockedList ?: ['']);
        } elseif ($filters['state'] === 'blocked') {
            $query->whereIn('batch_units.status', ['blocked', 'inactive', 'expired']);
        }
        if ($filters['date_from'] !== '') {
            $query->whereDate('batch_units.created_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to'] !== '') {
            $query->whereDate('batch_units.created_at', '<=', $filters['date_to']);
        }

        match ($filters['sort']) {
            'oldest' => $query->orderBy('batch_units.id'),
            'scans'  => $query->orderByRaw('COALESCE(vs.total, 0) DESC'),
            'hits'   => $query->orderByRaw('COALESCE(vs.flagged, 0) DESC'),
            'recent' => $query->orderByRaw('vs.last_scan IS NULL, vs.last_scan DESC'),
            default  => $query->orderByDesc('batch_units.id'),
        };

        $units = $query->paginate($perPage)->withQueryString();

        // Inline scan summary keyed by code (built from the joined aggregate).
        $scanStats = $units->getCollection()->mapWithKeys(fn ($u) => [$u->secret_code => (object) [
            'total'     => $u->v_total,
            'flagged'   => $u->v_flagged,
            'countries' => $u->v_countries,
            'ips'       => $u->v_ips,
            'last_scan' => $u->v_last,
        ]]);

        $products = Product::orderBy('name')->get(['id', 'name', 'prn']);
        $batches  = Batch::with('product')->orderByDesc('id')->limit(1000)->get(['id', 'brn', 'batch_number', 'product_id']);

        return view('anti-counterfeit.uuc-management', compact('units', 'lockedCodes', 'scanStats', 'filters', 'products', 'batches'));
    }

    /**
     * Download a per-UUC report (product details + full scan history) as a PDF.
     */
    public function uucReport(BatchUnit $unit)
    {
        $unit->load('batch.product');

        $scans = VerificationLog::where('uuc_code', $unit->secret_code)
            ->latest()->limit(1000)->get();

        $alerts = RiskAlert::where('uuc_code', $unit->secret_code)
            ->latest()->limit(200)->get();

        $summary = [
            'total'     => $scans->count(),
            'countries' => $scans->pluck('country_code')->filter()->unique()->count(),
            'ips'       => $scans->pluck('ip_address')->filter()->unique()->count(),
            'devices'   => $scans->pluck('user_agent')->filter()->unique()->count(),
            'flagged'   => $scans->whereIn('result', ['blocked', 'locked', 'recalled', 'invalid', 'expired'])->count(),
            'first'     => $scans->min('created_at'),
            'last'      => $scans->max('created_at'),
        ];

        $policy = VerificationPolicy::resolveForUnit($unit);
        $cfg    = Setting::config();

        $pdf = Pdf::loadView('anti-counterfeit.uuc-report', [
            'unit'    => $unit,
            'product' => $unit->batch?->product,
            'batch'   => $unit->batch,
            'scans'   => $scans,
            'alerts'  => $alerts,
            'summary' => $summary,
            'policy'  => $policy,
            'cfg'     => $cfg,
            'issued'  => now(),
        ])->setPaper('a4');

        return $pdf->download('uuc-report-' . $unit->secret_code . '.pdf');
    }

    /** Quick lock / unlock a single UUC (creates or removes a code-scope policy). */
    public function quickLockUnit(BatchUnit $unit, Request $request): RedirectResponse
    {
        $name = 'Quick lock — ' . $unit->secret_code;
        $isLocked = VerificationPolicy::where('name', $name)->where('scope_type', 'codes')->exists();

        if ($isLocked) {
            $this->unlockUnit($unit);
            return back()->with('success', "Unlocked {$unit->secret_code}.");
        }

        $reason = trim((string) $request->input('lock_reason', '')) ?: 'Quick lock from UUC Management.';
        $this->lockUnit($unit, $reason);
        return back()->with('success', "Locked {$unit->secret_code}.");
    }

    /** Lock or unlock many UUCs at once (checkbox bulk action). */
    public function bulkLockUnits(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids'         => 'required|array',
            'ids.*'       => 'integer',
            'action'      => 'required|in:lock,unlock',
            'lock_reason' => 'nullable|string|max:500',
        ]);

        $units  = BatchUnit::whereIn('id', $data['ids'])->get();
        $reason = trim((string) ($data['lock_reason'] ?? '')) ?: 'Bulk lock from UUC Management.';

        foreach ($units as $unit) {
            $data['action'] === 'lock' ? $this->lockUnit($unit, $reason) : $this->unlockUnit($unit);
        }

        $verb = $data['action'] === 'lock' ? 'Locked' : 'Unlocked';
        return back()->with('success', "{$verb} {$units->count()} UUC code(s).");
    }

    /** Create/keep a code-scope locked policy and stamp the unit's lock metadata. */
    private function lockUnit(BatchUnit $unit, string $reason): void
    {
        $name = 'Quick lock — ' . $unit->secret_code;
        if (!VerificationPolicy::where('name', $name)->where('scope_type', 'codes')->exists()) {
            VerificationPolicy::create([
                'name'       => $name,
                'scope_type' => 'codes',
                'uuc_codes'  => [$unit->secret_code],
                'locked'     => true,
                'active'     => true,
                'notes'      => $reason,
            ]);
        }
        $unit->forceFill(['lock_reason' => $reason, 'locked_at' => $unit->locked_at ?? now()])->save();
    }

    /** Remove the quick-lock policy and clear the unit's active lock (keep forensics). */
    private function unlockUnit(BatchUnit $unit): void
    {
        VerificationPolicy::where('name', 'Quick lock — ' . $unit->secret_code)
            ->where('scope_type', 'codes')->delete();
        $unit->forceFill(['lock_reason' => null, 'locked_at' => null])->save();
    }

    // ── Device Intelligence ───────────────────────────────────────────────
    public function deviceIntelligence(): View
    {
        $byBrowser = VerificationLog::selectRaw('browser, COUNT(*) c')->whereNotNull('browser')->groupBy('browser')->orderByDesc('c')->get();
        $byOs      = VerificationLog::selectRaw('os, COUNT(*) c')->whereNotNull('os')->groupBy('os')->orderByDesc('c')->get();
        $byDevice  = VerificationLog::selectRaw('device_type, COUNT(*) c')->whereNotNull('device_type')->groupBy('device_type')->orderByDesc('c')->get();
        $proxy     = VerificationLog::where('is_proxy', true)->count();

        return view('anti-counterfeit.device-intelligence', compact('byBrowser', 'byOs', 'byDevice', 'proxy'));
    }

    // ── Geo Intelligence ──────────────────────────────────────────────────
    public function geoIntelligence(): View
    {
        $byCountry = VerificationLog::selectRaw('country, country_code, COUNT(*) c, SUM(result="suspicious") sus')
            ->whereNotNull('country')->groupBy('country', 'country_code')->orderByDesc('c')->limit(50)->get();
        $byCity = VerificationLog::selectRaw('city, country, COUNT(*) c')
            ->whereNotNull('city')->groupBy('city', 'country')->orderByDesc('c')->limit(25)->get();
        $byIsp = VerificationLog::selectRaw('isp, COUNT(*) c')
            ->whereNotNull('isp')->groupBy('isp')->orderByDesc('c')->limit(15)->get();

        return view('anti-counterfeit.geo-intelligence', compact('byCountry', 'byCity', 'byIsp'));
    }

    // ── Reports & Analytics ───────────────────────────────────────────────
    public function reports(): View
    {
        $daily = VerificationLog::selectRaw('DATE(created_at) d, COUNT(*) total, SUM(result="genuine") genuine, SUM(result="suspicious") suspicious, SUM(result IN ("invalid","expired","recalled")) invalid')
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('d')->orderByDesc('d')->get();

        $byProduct = VerificationLog::selectRaw('product_id, COUNT(*) c')->whereNotNull('product_id')
            ->groupBy('product_id')->orderByDesc('c')->limit(10)->with('product')->get();

        $alertReport = RiskAlert::selectRaw('risk_level, COUNT(*) c')->groupBy('risk_level')->pluck('c', 'risk_level');

        return view('anti-counterfeit.reports', compact('daily', 'byProduct', 'alertReport'));
    }

    // ── Settings (dynamic verification-page configuration) ────────────────
    public function settings(): View
    {
        $cfg = Setting::config();
        return view('anti-counterfeit.settings', compact('cfg'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'verify_style'     => 'nullable|in:style1,style2,style3,style4,style5',
            'brand_name'       => 'required|string|max:80',
            'primary'          => 'required|string|max:9',
            'button_color'     => 'required|string|max:9',
            'footer_color'     => 'required|string|max:9',
            'genuine_title'    => 'required|string|max:80',
            'genuine_subtitle' => 'nullable|string|max:120',
            'footer'           => 'nullable|string|max:160',
            'loader_enabled'   => 'nullable|boolean',
            'loader_style'     => 'nullable|in:spinner,dots,pulse,ring,bars,grow,orbit,flip,wave,image',
            'loader_text'      => 'nullable|string|max:60',
            'loader_min_ms'    => 'nullable|integer|min:0|max:5000',
            'loader_image'     => 'nullable|file|mimes:svg,png,gif,jpg,jpeg,webp|max:1024',
            'remove_loader_image' => 'nullable|boolean',
            'report_form'      => 'nullable|boolean',
            'info_form'        => 'nullable|boolean',
            'verify_button'      => 'nullable|boolean',
            'verify_button_text' => 'nullable|string|max:40',
            'verify_button_pos'  => 'nullable|in:hero,card',
            'show_product_photo' => 'nullable|boolean',
            'verify_code_panel'  => 'nullable|boolean',
            'show_journey'       => 'nullable|boolean',
            'history_limit'      => 'nullable|in:5,10,20,50,0',
            'show_country'       => 'nullable|boolean',
            'show_city'          => 'nullable|boolean',
            'show_ip'            => 'nullable|boolean',
            'show_device'        => 'nullable|boolean',
            'show_verification'  => 'nullable|boolean',
            'show_leaflet'       => 'nullable|boolean',
            'leaflet_label'      => 'nullable|string|max:40',
            'leaflet'            => 'nullable|file|mimes:pdf,doc,docx|max:8192',
            'remove_leaflet'     => 'nullable|boolean',
            'card_left_label'    => 'nullable|string|max:30',
            'card_left_field'    => 'nullable|string|max:20',
            'card_right_label'   => 'nullable|string|max:30',
            'card_right_field'   => 'nullable|string|max:20',
            'fields'           => 'nullable|array',
            'messages'         => 'nullable|array',
            'logo'             => 'nullable|image|max:2048',
            'remove_logo'      => 'nullable|boolean',
            'bg_image'         => 'nullable|image|max:4096',
            'remove_bg_image'  => 'nullable|boolean',
        ]);

        Setting::put('verify_style', $data['verify_style'] ?? 'style1');
        Setting::put('brand_name', $data['brand_name']);
        Setting::put('primary', $data['primary']);
        Setting::put('button_color', $data['button_color']);
        Setting::put('footer_color', $data['footer_color']);
        Setting::put('genuine_title', $data['genuine_title']);
        Setting::put('genuine_subtitle', $data['genuine_subtitle'] ?? '');
        Setting::put('footer', $data['footer'] ?? '');
        Setting::put('loader_enabled', $request->boolean('loader_enabled') ? 1 : 0);
        Setting::put('loader_style', $data['loader_style'] ?? 'spinner');
        Setting::put('loader_text', $data['loader_text'] ?? '');
        Setting::put('loader_min_ms', $data['loader_min_ms'] ?? 700);

        // Custom loader image (SVG / PNG / GIF) upload / removal.
        $currentLoaderImg = Setting::config()['loader_image'] ?? null;
        if ($request->boolean('remove_loader_image') && $currentLoaderImg) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($currentLoaderImg);
            Setting::put('loader_image', null);
        }
        if ($request->hasFile('loader_image')) {
            if ($currentLoaderImg) \Illuminate\Support\Facades\Storage::disk('public')->delete($currentLoaderImg);
            Setting::put('loader_image', $request->file('loader_image')->store('settings', 'public'));
        }
        Setting::put('report_form', $request->boolean('report_form') ? 1 : 0);
        Setting::put('info_form', $request->boolean('info_form') ? 1 : 0);
        Setting::put('verify_button', $request->boolean('verify_button') ? 1 : 0);
        Setting::put('verify_button_text', trim($data['verify_button_text'] ?? '') ?: 'Verify authenticity');
        Setting::put('verify_button_pos', $data['verify_button_pos'] ?? 'hero');
        Setting::put('show_product_photo', $request->boolean('show_product_photo') ? 1 : 0);
        Setting::put('verify_code_panel', $request->boolean('verify_code_panel') ? 1 : 0);
        Setting::put('show_journey', $request->boolean('show_journey') ? 1 : 0);
        Setting::put('history_limit', $data['history_limit'] ?? 10);
        Setting::put('show_country', $request->boolean('show_country') ? 1 : 0);
        Setting::put('show_city', $request->boolean('show_city') ? 1 : 0);
        Setting::put('show_ip', $request->boolean('show_ip') ? 1 : 0);
        Setting::put('show_device', $request->boolean('show_device') ? 1 : 0);
        Setting::put('show_verification', $request->boolean('show_verification') ? 1 : 0);
        Setting::put('show_leaflet', $request->boolean('show_leaflet') ? 1 : 0);
        Setting::put('leaflet_label', trim($data['leaflet_label'] ?? '') ?: 'Download insert / leaflet');
        Setting::put('card_left_label', $data['card_left_label'] ?? '');
        Setting::put('card_left_field', $data['card_left_field'] ?? 'none');
        Setting::put('card_right_label', $data['card_right_label'] ?? '');
        Setting::put('card_right_field', $data['card_right_field'] ?? 'none');
        Setting::put('fields', array_values($data['fields'] ?? []));
        Setting::put('messages', $data['messages'] ?? Setting::defaults()['messages']);

        // Logo upload / removal.
        $current = Setting::config()['logo_path'] ?? null;
        if ($request->boolean('remove_logo') && $current) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($current);
            Setting::put('logo_path', null);
        }
        if ($request->hasFile('logo')) {
            if ($current) \Illuminate\Support\Facades\Storage::disk('public')->delete($current);
            $path = $request->file('logo')->store('ac', 'public');
            Setting::put('logo_path', $path);
        }

        // Background image upload / removal.
        $currentBg = Setting::config()['bg_image'] ?? null;
        if ($request->boolean('remove_bg_image') && $currentBg) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($currentBg);
            Setting::put('bg_image', null);
        }
        if ($request->hasFile('bg_image')) {
            if ($currentBg) \Illuminate\Support\Facades\Storage::disk('public')->delete($currentBg);
            $bgPath = $request->file('bg_image')->store('ac', 'public');
            Setting::put('bg_image', $bgPath);
        }

        // Insert / leaflet document upload / removal.
        $currentLeaflet = Setting::config()['leaflet_path'] ?? null;
        if ($request->boolean('remove_leaflet') && $currentLeaflet) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($currentLeaflet);
            Setting::put('leaflet_path', null);
        }
        if ($request->hasFile('leaflet')) {
            if ($currentLeaflet) \Illuminate\Support\Facades\Storage::disk('public')->delete($currentLeaflet);
            $leafletPath = $request->file('leaflet')->store('ac', 'public');
            Setting::put('leaflet_path', $leafletPath);
        }

        Setting::forget();
        return back()->with('success', 'Verification page settings saved.');
    }

    /** Restore every verification-page setting to the built-in default design. */
    public function resetSettings(): RedirectResponse
    {
        // Remove uploaded assets before wiping their path pointers.
        $cfg = Setting::config();
        foreach (['logo_path', 'bg_image'] as $k) {
            if (!empty($cfg[$k])) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($cfg[$k]);
            }
        }

        Setting::resetAll();
        return back()->with('success', 'Settings restored to the default design.');
    }

    /** Apply a ready-made colour template (keeps texts, logo, fields, etc.). */
    public function applyTemplate(Request $request): RedirectResponse
    {
        $templates = Setting::templates();
        $key = (string) $request->input('template');

        if (!isset($templates[$key])) {
            return back()->with('error', 'Unknown template.');
        }

        foreach ($templates[$key]['values'] as $k => $v) {
            Setting::put($k, $v);
        }

        Setting::forget();
        return back()->with('success', 'Applied the “'.$templates[$key]['label'].'” template.');
    }

    // ── Customer Reports (public counterfeit reports) ─────────────────────
    public function customerReports(Request $request): View
    {
        $status = $request->query('status');
        $reports = CounterfeitReport::with(['product', 'batch'])
            ->when($status, fn ($q, $v) => $q->where('status', $v))
            ->latest()->paginate(20)->withQueryString();

        $counts = [
            'new'       => CounterfeitReport::where('status', 'new')->count(),
            'reviewing' => CounterfeitReport::where('status', 'reviewing')->count(),
            'resolved'  => CounterfeitReport::where('status', 'resolved')->count(),
        ];

        return view('anti-counterfeit.customer-reports', compact('reports', 'status', 'counts'));
    }

    public function updateReport(Request $request, CounterfeitReport $report): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:new,reviewing,resolved']);
        $report->update($data);
        return back()->with('success', 'Report updated.');
    }

    // ── Access Control (verification policies) ────────────────────────────
    public function accessControl(Request $request): View
    {
        $filters = $request->only(['search', 'scope', 'status', 'date', 'date_from', 'date_to', 'sort', 'per_page']);

        $pp        = (int) ($filters['per_page'] ?? 10);
        $perPage   = in_array($pp, [10, 20, 50, 100], true) ? $pp : 10;
        $policies  = $this->policiesQuery($filters)->paginate($perPage)->withQueryString();
        $products  = Product::orderBy('name')->get(['id', 'name', 'prn']);
        $batches   = Batch::with('product')->orderByDesc('id')->limit(500)->get(['id', 'brn', 'batch_number', 'product_id']);
        $countries = Country::orderBy('name')->get(['code', 'name', 'flag']);

        // Rich payload (incl. product/batch/serial details) for the view + edit modals.
        $policyData = $policies->getCollection()->map(fn ($p) => $this->policyPayload($p))->values();

        // Find-by-UUC lookup (?code=). Resolves the unit, its effective policy,
        // every policy that applies, and the single-code policy used for editing.
        $lookup = $this->lookupUnit($request->query('code'));

        // Find-by-Product/Batch lookup (?fp= / ?fb= / ?fs=). Aggregated stats +
        // the product/batch-scope policy used for inline editing.
        $find = $this->lookupProductBatch($request);

        // Global baseline policy (applies to every UUC code) for the Global tab.
        $globalPolicy = VerificationPolicy::global();

        // Scan-intelligence rule config + admin metadata for the Scan Intelligence tab.
        $cfg      = Setting::config();
        $intel    = $cfg['scan_intelligence'] ?? Setting::scanIntelligenceDefaults();
        $intelMeta = Setting::scanIntelligenceMeta();

        return view('anti-counterfeit.access-control', compact('policies', 'policyData', 'products', 'batches', 'countries', 'filters', 'lookup', 'globalPolicy', 'intel', 'intelMeta', 'find'));
    }

    /**
     * Find-by-Product/Batch/Serial lookup. Resolves a product and/or batch (or a
     * serial → its product+batch) and returns aggregated scan intelligence plus
     * the product/batch-scope policy for inline editing.
     */
    private function lookupProductBatch(Request $request): ?array
    {
        $pid    = $request->query('fp');
        $bid    = $request->query('fb');
        $serial = trim((string) $request->query('fs', ''));

        if (!$pid && !$bid && $serial === '') {
            return null;
        }

        $flagged = ['blocked', 'locked', 'recalled', 'invalid', 'expired'];
        $out = ['fp' => $pid, 'fb' => $bid, 'fs' => $serial, 'unit' => null, 'product' => null, 'batch' => null];

        // Serial / code → unit, then derive its product + batch.
        if ($serial !== '') {
            $unit = BatchUnit::with('batch.product')
                ->where('secret_code', $serial)
                ->orWhere('unique_number', $serial)
                ->when(is_numeric($serial), fn ($q) => $q->orWhere('serial_number', $serial))
                ->first();
            if (!$unit) {
                $out['unit'] = ['searched' => $serial, 'found' => false];
            } else {
                $out['unit'] = [
                    'searched'  => $serial,
                    'found'     => true,
                    'model'     => $unit,
                    'effective' => VerificationPolicy::resolveForUnit($unit),
                ];
                $pid = $pid ?: $unit->batch?->product_id;
                $bid = $bid ?: $unit->batch_id;
            }
        }

        if ($pid && ($product = Product::find($pid))) {
            // Per-batch rollups for this product (units / locked / scans / hits).
            $pBatches  = Batch::where('product_id', $product->id)->orderByDesc('id')->get();
            $batchIds  = $pBatches->pluck('id')->all();
            $unitAgg   = BatchUnit::whereIn('batch_id', $batchIds ?: [0])
                ->selectRaw('batch_id, COUNT(*) as c, SUM(CASE WHEN locked_at IS NOT NULL THEN 1 ELSE 0 END) as locked')
                ->groupBy('batch_id')->get()->keyBy('batch_id');
            $scanAgg   = VerificationLog::whereIn('batch_id', $batchIds ?: [0])
                ->selectRaw('batch_id, COUNT(*) as total, SUM(CASE WHEN result IN ("blocked","locked","recalled","invalid","expired") THEN 1 ELSE 0 END) as flagged')
                ->groupBy('batch_id')->get()->keyBy('batch_id');
            $policyBatchIds = VerificationPolicy::where('scope_type', 'batch')->whereIn('batch_id', $batchIds ?: [0])
                ->pluck('batch_id')->flip();

            $batchList = $pBatches->map(fn ($b) => [
                'model'       => $b,
                'units'       => (int) ($unitAgg[$b->id]->c ?? 0),
                'lockedUnits' => (int) ($unitAgg[$b->id]->locked ?? 0),
                'scans'       => (int) ($scanAgg[$b->id]->total ?? 0),
                'flagged'     => (int) ($scanAgg[$b->id]->flagged ?? 0),
                'hasPolicy'   => $policyBatchIds->has($b->id),
            ])->all();

            $out['product'] = [
                'model'     => $product,
                'batches'   => $pBatches->count(),
                'units'     => $unitAgg->sum('c'),
                'locked'    => $unitAgg->sum('locked'),
                'scans'     => VerificationLog::where('product_id', $product->id)->count(),
                'flagged'   => VerificationLog::where('product_id', $product->id)->whereIn('result', $flagged)->count(),
                'policy'    => VerificationPolicy::where('scope_type', 'product')->where('product_id', $product->id)->first(),
                'batchList' => $batchList,
            ];
        }

        if ($bid && ($batch = Batch::with('product')->find($bid))) {
            $out['batch'] = [
                'model'   => $batch,
                'units'   => BatchUnit::where('batch_id', $batch->id)->count(),
                'locked'  => BatchUnit::where('batch_id', $batch->id)->whereNotNull('locked_at')->count(),
                'scans'   => VerificationLog::where('batch_id', $batch->id)->count(),
                'flagged' => VerificationLog::where('batch_id', $batch->id)->whereIn('result', $flagged)->count(),
                'policy'  => VerificationPolicy::where('scope_type', 'batch')->where('batch_id', $batch->id)->first(),
            ];
        }

        return $out;
    }

    /** Create/update a product- or batch-scope permission from the Find tab. */
    public function saveScopePermission(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'scope_type'            => 'required|in:product,batch',
            'product_id'            => 'nullable|required_if:scope_type,product|exists:products,id',
            'batch_id'              => 'nullable|required_if:scope_type,batch|exists:batches,id',
            'locked'                => 'nullable|boolean',
            'allow_all'             => 'nullable|boolean',
            'allowed_countries'     => 'nullable|array',
            'allowed_cities'        => 'nullable|string|max:2000',
            'ip_whitelist'          => 'nullable|string|max:5000',
            'ip_blacklist'          => 'nullable|string|max:5000',
            'block_vpn'             => 'nullable|boolean',
            'vpn_allowed_countries' => 'nullable|string|max:2000',
            'scan_limit'            => 'nullable|integer|min:1',
            'device_limit'          => 'nullable|integer|min:1',
            'notes'                 => 'nullable|string|max:2000',
        ]);

        $isProduct = $data['scope_type'] === 'product';
        $target    = $isProduct ? Product::find($data['product_id']) : Batch::find($data['batch_id']);
        $label     = $isProduct ? $target->name : ($target->brn ?? ('Batch #' . $target->id));

        $attrs = [
            'name'                  => ($isProduct ? 'Product permission — ' : 'Batch permission — ') . $label,
            'scope_type'            => $data['scope_type'],
            'product_id'            => $isProduct ? $target->id : null,
            'batch_id'              => $isProduct ? null : $target->id,
            'uuc_codes'             => null,
            'locked'                => $request->boolean('locked'),
            'allow_all'             => $request->boolean('allow_all'),
            'allowed_countries'     => array_values(array_map('strtoupper', $data['allowed_countries'] ?? [])) ?: null,
            'allowed_cities'        => $this->splitList($data['allowed_cities'] ?? '') ?: null,
            'ip_whitelist'          => $this->splitList($data['ip_whitelist'] ?? '') ?: null,
            'ip_blacklist'          => $this->splitList($data['ip_blacklist'] ?? '') ?: null,
            'block_vpn'             => $request->boolean('block_vpn'),
            'vpn_allowed_countries' => array_values(array_map('strtoupper', $this->splitList($data['vpn_allowed_countries'] ?? ''))) ?: null,
            'scan_limit'            => $data['scan_limit'] ?? null,
            'device_limit'          => $data['device_limit'] ?? null,
            'active'                => true,
            'notes'                 => $data['notes'] ?? null,
        ];

        $existing = VerificationPolicy::where('scope_type', $data['scope_type'])
            ->where($isProduct ? 'product_id' : 'batch_id', $target->id)->first();

        $msg = $existing ? 'updated' : 'created';
        $existing ? $existing->update($attrs) : VerificationPolicy::create($attrs);

        return redirect()
            ->route('anticounterfeit.policies', ['ftab' => 'find', ($isProduct ? 'fp' : 'fb') => $target->id])
            ->with('success', ucfirst($data['scope_type']) . " permission {$msg} for {$label}.");
    }

    /**
     * Save the scan-intelligence rules (8 scenarios) into settings. Each rule
     * keeps its existing shape and is merged with the posted toggles/thresholds
     * so per-rule defaults survive.
     */
    public function saveScanIntelligence(Request $request): RedirectResponse
    {
        $posted   = (array) $request->input('rules', []);
        $defaults = Setting::scanIntelligenceDefaults();
        $rules    = [];

        foreach ($defaults as $key => $def) {
            $in = (array) ($posted[$key] ?? []);
            $rule = $def;
            $rule['enabled'] = !empty($in['enabled']);
            $rule['report']  = !empty($in['report']);
            $rule['title']   = trim((string) ($in['title'] ?? $def['title'])) ?: $def['title'];
            $rule['text']    = trim((string) ($in['text'] ?? $def['text'])) ?: $def['text'];
            // Numeric tunables — only the ones this rule actually has.
            foreach (['threshold', 'days', 'records', 'window_min', 'max'] as $n) {
                if (array_key_exists($n, $def)) {
                    $rule[$n] = max(0, (int) ($in[$n] ?? $def[$n]));
                }
            }
            $rules[$key] = $rule;
        }

        Setting::put('scan_intelligence', $rules);
        Setting::forget();

        return redirect()
            ->route('anticounterfeit.policies', ['ftab' => 'intel'])
            ->with('success', 'Scan intelligence rules saved.');
    }

    /**
     * Create or update the single global policy that applies to ALL UUC codes.
     * Acts as the baseline behind product/batch/code policies on every scan.
     */
    public function saveGlobalSetting(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locked'                => 'nullable|boolean',
            'allow_all'             => 'nullable|boolean',
            'active'                => 'nullable|boolean',
            'allowed_countries'     => 'nullable|array',
            'allowed_cities'        => 'nullable|string|max:5000',
            'ip_whitelist'          => 'nullable|string|max:5000',
            'ip_blacklist'          => 'nullable|string|max:5000',
            'block_vpn'             => 'nullable|boolean',
            'vpn_allowed_countries' => 'nullable|string|max:2000',
            'scan_limit'            => 'nullable|integer|min:1',
            'device_limit'          => 'nullable|integer|min:1',
            'notes'                 => 'nullable|string|max:2000',
        ]);

        $attrs = [
            'name'                  => 'Global verification policy (all UUC codes)',
            'scope_type'            => 'global',
            'product_id'            => null,
            'batch_id'              => null,
            'uuc_codes'             => null,
            'locked'                => $request->boolean('locked'),
            'allow_all'             => $request->boolean('allow_all'),
            'allowed_countries'     => array_values(array_map('strtoupper', $data['allowed_countries'] ?? [])) ?: null,
            'allowed_cities'        => $this->splitList($data['allowed_cities'] ?? '') ?: null,
            'ip_whitelist'          => $this->splitList($data['ip_whitelist'] ?? '') ?: null,
            'ip_blacklist'          => $this->splitList($data['ip_blacklist'] ?? '') ?: null,
            'block_vpn'             => $request->boolean('block_vpn'),
            'vpn_allowed_countries' => array_values(array_map('strtoupper', $this->splitList($data['vpn_allowed_countries'] ?? ''))) ?: null,
            'scan_limit'            => $data['scan_limit'] ?? null,
            'device_limit'          => $data['device_limit'] ?? null,
            'active'                => $request->boolean('active'),
            'notes'                 => $data['notes'] ?? null,
        ];

        $global = VerificationPolicy::global();
        if ($global) {
            $global->update($attrs);
            $msg = 'updated';
        } else {
            VerificationPolicy::create($attrs);
            $msg = 'created';
        }

        return redirect()
            ->route('anticounterfeit.policies', ['ftab' => 'global'])
            ->with('success', "Global scan & access settings {$msg} — applied to all UUC codes.");
    }

    /** Resolve a UUC code (or serial) to a unit + its permission picture. */
    private function lookupUnit(?string $code): ?array
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        $unit = BatchUnit::with('batch.product')
            ->where('secret_code', $code)
            ->orWhere('unique_number', $code)
            ->first();

        if (!$unit) {
            return ['searched' => $code, 'unit' => null];
        }

        $effective  = VerificationPolicy::resolveForUnit($unit);
        $applicable = VerificationPolicy::where('active', true)->get()->filter(function ($p) use ($unit) {
            return match ($p->scope_type) {
                'codes'   => in_array($unit->secret_code, (array) $p->uuc_codes, true),
                'batch'   => (int) $p->batch_id === (int) $unit->batch_id,
                'product' => (int) $p->product_id === (int) $unit->batch?->product_id,
                'global'  => true,   // baseline — applies to every UUC code
                default   => false,
            };
        })->values();

        // Single-code policy that targets exactly this code (the one we edit here).
        $codePolicy = VerificationPolicy::where('scope_type', 'codes')->get()
            ->first(fn ($p) => (array) $p->uuc_codes === [$unit->secret_code]);

        return [
            'searched'   => $code,
            'unit'       => $unit,
            'effective'  => $effective,
            'applicable' => $applicable,
            'codePolicy' => $codePolicy,
        ];
    }

    /** Create or update the per-code permission for one UUC (Find-by-UUC tab). */
    public function saveUnitPermission(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'                  => 'required|string|max:100',
            'locked'                => 'nullable|boolean',
            'lock_reason'           => 'nullable|string|max:500',
            'allow_all'             => 'nullable|boolean',
            'allowed_countries'     => 'nullable|array',
            'allowed_cities'        => 'nullable|string|max:2000',
            'ip_whitelist'          => 'nullable|string|max:5000',
            'ip_blacklist'          => 'nullable|string|max:5000',
            'block_vpn'             => 'nullable|boolean',
            'vpn_allowed_countries' => 'nullable|string|max:2000',
            'scan_limit'            => 'nullable|integer|min:1',
            'device_limit'          => 'nullable|integer|min:1',
            'notes'                 => 'nullable|string|max:2000',
        ]);

        $unit = BatchUnit::where('secret_code', trim($data['code']))
            ->orWhere('unique_number', trim($data['code']))->first();

        if (!$unit) {
            return back()->with('error', 'No unit matches that UUC / serial.');
        }
        $code = $unit->secret_code;

        $attrs = [
            'name'                  => 'Code permission — ' . $code,
            'scope_type'            => 'codes',
            'uuc_codes'             => [$code],
            'locked'                => $request->boolean('locked'),
            'allow_all'             => $request->boolean('allow_all'),
            'allowed_countries'     => array_values(array_map('strtoupper', $data['allowed_countries'] ?? [])) ?: null,
            'allowed_cities'        => $this->splitList($data['allowed_cities'] ?? '') ?: null,
            'ip_whitelist'          => $this->splitList($data['ip_whitelist'] ?? '') ?: null,
            'ip_blacklist'          => $this->splitList($data['ip_blacklist'] ?? '') ?: null,
            'block_vpn'             => $request->boolean('block_vpn'),
            'vpn_allowed_countries' => array_values(array_map('strtoupper', $this->splitList($data['vpn_allowed_countries'] ?? ''))) ?: null,
            'scan_limit'            => $data['scan_limit'] ?? null,
            'device_limit'          => $data['device_limit'] ?? null,
            'active'                => true,
            'notes'                 => $data['notes'] ?? null,
        ];

        $existing = VerificationPolicy::where('scope_type', 'codes')->get()
            ->first(fn ($p) => (array) $p->uuc_codes === [$code]);

        if ($existing) {
            $existing->update($attrs);
            $msg = 'updated';
        } else {
            VerificationPolicy::create($attrs);
            $msg = 'created';
        }

        // Mirror the lock state + reason onto the unit for per-UUC forensics.
        if ($request->boolean('locked')) {
            $reason = trim((string) ($data['lock_reason'] ?? '')) ?: ($data['notes'] ?? 'Locked via Find by UUC.');
            $unit->forceFill([
                'lock_reason' => $reason,
                'locked_at'   => $unit->locked_at ?? now(),
            ])->save();
        } else {
            $unit->forceFill(['lock_reason' => null, 'locked_at' => null])->save();
        }

        return redirect()
            ->route('anticounterfeit.policies', ['ftab' => 'lookup', 'code' => $code])
            ->with('success', "Permission {$msg} for {$code}.");
    }

    /** Build the JSON payload for one policy, including target product/batch/serial details. */
    private function policyPayload(VerificationPolicy $p): array
    {
        $details = [];

        if ($p->scope_type === 'product' && $p->product) {
            $details['product'] = [
                'name'         => $p->product->name,
                'prn'          => $p->product->prn,
                'generic'      => $p->product->generic_name,
                'strength'     => $p->product->strength,
                'dosage'       => $p->product->dosage_form,
                'manufacturer' => $p->product->manufacturer_name,
            ];
            $details['batches'] = Batch::where('product_id', $p->product_id)->orderByDesc('id')->limit(5)
                ->get(['brn', 'batch_number', 'manufacture_date', 'expiry_date'])
                ->map(fn ($b) => [
                    'brn' => $b->brn, 'batch_number' => $b->batch_number,
                    'mfg' => optional($b->manufacture_date)->format('d M Y'),
                    'exp' => optional($b->expiry_date)->format('d M Y'),
                ])->all();
        } elseif ($p->scope_type === 'batch' && $p->batch) {
            $details['batch'] = [
                'brn'          => $p->batch->brn,
                'batch_number' => $p->batch->batch_number,
                'lot'          => $p->batch->lot_number,
                'mfg'          => optional($p->batch->manufacture_date)->format('d M Y'),
                'exp'          => optional($p->batch->expiry_date)->format('d M Y'),
                'product'      => $p->batch->product?->name,
                'qc'           => $p->batch->qc_status,
            ];
        } elseif ($p->scope_type === 'codes') {
            $details['units'] = BatchUnit::with('batch.product')
                ->whereIn('secret_code', (array) $p->uuc_codes)->limit(25)->get()
                ->map(fn ($u) => [
                    'code'    => $u->secret_code,
                    'serial'  => $u->serial_number,
                    'status'  => $u->status,
                    'product' => $u->batch?->product?->name,
                    'brn'     => $u->batch?->brn,
                    'mfg'     => optional($u->batch?->manufacture_date)->format('d M Y'),
                    'exp'     => optional($u->batch?->expiry_date)->format('d M Y'),
                ])->all();
        }

        return [
            'id' => $p->id, 'name' => $p->name, 'scope_type' => $p->scope_type,
            'product_id' => $p->product_id, 'batch_id' => $p->batch_id,
            'uuc_codes' => $p->uuc_codes ?? [], 'locked' => (bool) $p->locked,
            'scan_limit' => $p->scan_limit, 'device_limit' => $p->device_limit,
            'allowed_cities' => $p->allowed_cities ?? [], 'allowed_countries' => $p->allowed_countries ?? [],
            'active' => (bool) $p->active, 'notes' => $p->notes,
            'product_name' => $p->product?->name, 'batch_brn' => $p->batch?->brn,
            'scope_label' => $p->scope_label, 'created' => $p->created_at?->format('d M Y, H:i'),
            'details' => $details,
        ];
    }

    /** Shared filtered/sorted query for the policies list + export. */
    private function policiesQuery(array $filters)
    {
        $query = VerificationPolicy::with(['product', 'batch']);

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                  ->orWhere('uuc_codes', 'like', "%{$s}%")          // serial / secret code match
                  ->orWhere('allowed_cities', 'like', "%{$s}%")
                  ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$s}%")->orWhere('prn', 'like', "%{$s}%"))
                  ->orWhereHas('batch', fn ($b) => $b->where('brn', 'like', "%{$s}%")->orWhere('batch_number', 'like', "%{$s}%"));
            });
        }
        if (!empty($filters['scope'])) {
            $query->where('scope_type', $filters['scope']);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('active', (int) $filters['status']);
        }
        switch ($filters['date'] ?? '') {
            case 'today': $query->whereDate('created_at', now()); break;
            case '7d':    $query->where('created_at', '>=', now()->subDays(7)); break;
            case '30d':   $query->where('created_at', '>=', now()->subDays(30)); break;
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        [$col, $dir] = match ($filters['sort'] ?? 'newest') {
            'oldest' => ['created_at', 'asc'],
            'name'   => ['name', 'asc'],
            'scope'  => ['scope_type', 'asc'],
            default  => ['created_at', 'desc'],
        };

        return $query->orderBy($col, $dir);
    }

    /** Update an existing policy (inline edit from Active Policies). */
    public function updatePolicy(Request $request, VerificationPolicy $policy): RedirectResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:150',
            'scope_type'        => 'required|in:product,batch,codes',
            'product_id'        => 'nullable|required_if:scope_type,product|exists:products,id',
            'batch_id'          => 'nullable|required_if:scope_type,batch|exists:batches,id',
            'uuc_codes'         => 'nullable|required_if:scope_type,codes|string|max:10000',
            'locked'            => 'nullable|boolean',
            'allowed_countries' => 'nullable|array',
            'allowed_cities'    => 'nullable|string|max:2000',
            'scan_limit'        => 'nullable|integer|min:1',
            'device_limit'      => 'nullable|integer|min:1',
            'active'            => 'nullable|boolean',
            'notes'             => 'nullable|string|max:2000',
        ]);

        $policy->update([
            'name'              => $data['name'],
            'scope_type'        => $data['scope_type'],
            'product_id'        => $data['scope_type'] === 'product' ? $data['product_id'] : null,
            'batch_id'          => $data['scope_type'] === 'batch' ? $data['batch_id'] : null,
            'uuc_codes'         => $data['scope_type'] === 'codes' ? $this->splitList($data['uuc_codes'] ?? '') : null,
            'locked'            => (bool) ($data['locked'] ?? false),
            'allowed_countries' => array_values(array_map('strtoupper', $data['allowed_countries'] ?? [])) ?: null,
            'allowed_cities'    => $this->splitList($data['allowed_cities'] ?? '') ?: null,
            'scan_limit'        => $data['scan_limit'] ?? null,
            'device_limit'      => $data['device_limit'] ?? null,
            'active'            => (bool) ($data['active'] ?? false),
            'notes'             => $data['notes'] ?? null,
        ]);

        return back()->with('success', "Policy '{$policy->name}' updated.");
    }

    /** CSV export of the policies list, honouring current filters. */
    public function exportPolicies(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = $request->only(['search', 'scope', 'status', 'date', 'date_from', 'date_to', 'sort']);
        $rows = $this->policiesQuery($filters)->get();

        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="access-policies-' . now()->format('Ymd_His') . '.csv"'];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Name', 'Scope', 'Target', 'Locked', 'Allowed Countries', 'Allowed Cities', 'Scan Limit', 'Device Limit', 'Active', 'Created']);
            foreach ($rows as $p) {
                $target = match ($p->scope_type) {
                    'product' => $p->product?->name,
                    'batch'   => $p->batch?->brn,
                    default   => implode(' ', (array) $p->uuc_codes),
                };
                fputcsv($out, [
                    $p->id, $p->name, $p->scope_label, $target,
                    $p->locked ? 'Yes' : 'No',
                    implode(' ', (array) $p->allowed_countries),
                    implode(' ', (array) $p->allowed_cities),
                    $p->scan_limit ?? '', $p->device_limit ?? '',
                    $p->active ? 'Yes' : 'No',
                    $p->created_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, 'access-policies.csv', $headers);
    }

    public function storePolicy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:150',
            'scope_type'        => 'required|in:product,batch,codes',
            'product_id'        => 'nullable|required_if:scope_type,product|exists:products,id',
            'batch_id'          => 'nullable|required_if:scope_type,batch|exists:batches,id',
            'uuc_codes'         => 'nullable|required_if:scope_type,codes|string|max:10000',
            'locked'            => 'nullable|boolean',
            'allowed_countries' => 'nullable|array',
            'allowed_cities'    => 'nullable|string|max:2000',
            'scan_limit'        => 'nullable|integer|min:1',
            'device_limit'      => 'nullable|integer|min:1',
            'notes'             => 'nullable|string|max:2000',
        ]);

        // Parse free-text lists into arrays.
        $codes  = $this->splitList($data['uuc_codes'] ?? '');
        $cities = $this->splitList($data['allowed_cities'] ?? '');

        VerificationPolicy::create([
            'name'              => $data['name'],
            'scope_type'        => $data['scope_type'],
            'product_id'        => $data['scope_type'] === 'product' ? $data['product_id'] : null,
            'batch_id'          => $data['scope_type'] === 'batch' ? $data['batch_id'] : null,
            'uuc_codes'         => $data['scope_type'] === 'codes' ? $codes : null,
            'locked'            => (bool) ($data['locked'] ?? false),
            'allowed_countries' => array_values(array_map('strtoupper', $data['allowed_countries'] ?? [])) ?: null,
            'allowed_cities'    => $cities ?: null,
            'scan_limit'        => $data['scan_limit'] ?? null,
            'device_limit'      => $data['device_limit'] ?? null,
            'active'            => true,
            'notes'             => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Verification policy created.');
    }

    public function togglePolicy(VerificationPolicy $policy): RedirectResponse
    {
        $policy->update(['active' => !$policy->active]);
        return back()->with('success', $policy->active ? 'Policy activated.' : 'Policy disabled.');
    }

    public function destroyPolicy(VerificationPolicy $policy): RedirectResponse
    {
        $policy->delete();
        return back()->with('success', 'Policy deleted.');
    }

    /** Split a comma / space / newline separated string into a clean array. */
    private function splitList(string $raw): array
    {
        return collect(preg_split('/[\s,]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($v) => trim($v))->filter()->unique()->values()->all();
    }
}
