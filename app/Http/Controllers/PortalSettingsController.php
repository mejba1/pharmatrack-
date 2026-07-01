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

        return view('portal-settings', ['portal' => PortalSettings::all()]);
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
        ]);

        PortalSettings::save($data);

        return back()->with('success', 'Customer portal settings updated.');
    }
}
