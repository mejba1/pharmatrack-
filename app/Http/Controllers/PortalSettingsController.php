<?php

namespace App\Http\Controllers;

use App\Support\PortalSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Super-admin management of the customer portal (what shows / what's allowed). */
class PortalSettingsController extends Controller
{
    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Only a super admin can manage the customer portal.');
    }

    public function edit(Request $request): View
    {
        $this->authorizeSuperAdmin($request);

        return view('portal-settings', [
            'portal' => PortalSettings::all(),
            'logs'   => \App\Models\PortalSettingLog::with('user')->latest()->limit(15)->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $data = $request->validate([
            'portal_enabled'            => 'nullable|boolean',
            'portal_show_orders'        => 'nullable|boolean',
            'portal_show_invoices'      => 'nullable|boolean',
            'portal_show_documents'     => 'nullable|boolean',
            'portal_show_units'         => 'nullable|boolean',
            'portal_allow_ordering'     => 'nullable|boolean',
            'portal_allow_registration' => 'nullable|boolean',
            'portal_allow_profile_edit' => 'nullable|boolean',
            'portal_welcome_message'    => 'nullable|string|max:500',
            'portal_support_email'      => 'nullable|email|max:160',
            'portal_brand_name'         => 'nullable|string|max:60',
            'portal_primary'            => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'portal_accent'             => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'portal_logo'               => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        // Logo upload replaces the previous file; keep existing if none uploaded.
        if ($request->hasFile('portal_logo')) {
            $old = PortalSettings::get('portal_logo');
            if ($old) \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
            $data['portal_logo'] = $request->file('portal_logo')->store('portal', 'public');
        } else {
            $data['portal_logo'] = PortalSettings::get('portal_logo');
        }

        $before = PortalSettings::all();
        PortalSettings::save($data);
        $after = PortalSettings::all();

        // Record what changed for the audit trail.
        $changes = [];
        foreach ($after as $key => $value) {
            if (($before[$key] ?? null) !== $value) {
                $changes[$key] = ['from' => $before[$key] ?? null, 'to' => $value];
            }
        }
        if ($changes) {
            \App\Models\PortalSettingLog::create([
                'user_id' => $request->user()->id,
                'summary' => count($changes) . ' setting(s) changed: ' . implode(', ', array_keys($changes)),
                'changes' => $changes,
            ]);
        }

        return back()->with('success', 'Customer portal settings updated.');
    }
}
