<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Database\Seeders\CountrySeeder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CountryController extends Controller
{
    public function index(): View
    {
        $countries = Country::withCount('productRegistrations')
                            ->orderBy('name')
                            ->paginate(20);

        return view('master.countries', compact('countries'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255|unique:countries,name',
            'code'                => 'nullable|string|max:3|unique:countries,code',
            'region'              => 'nullable|string|max:255',
            'currency_code'       => 'nullable|string|max:3',
            'dial_code'           => 'nullable|string|max:10',
            'flag'                => 'nullable|string|max:16',
            'regulatory_status'   => 'nullable|in:approved,restricted,pending,banned',
        ], [
            'name.unique' => 'That country already exists.',
            'code.unique' => 'That country code is already in use.',
        ]);

        $data['code']              = !empty($data['code']) ? strtoupper($data['code']) : $this->uniqueCode($data['name']);
        $data['flag']              = !empty($data['flag']) ? $data['flag'] : CountrySeeder::flagEmoji($data['code']);
        $data['regulatory_status'] = $data['regulatory_status'] ?? 'approved';
        $data['is_active']         = true;

        Country::create($data);

        return back()->with('success', "Country '{$data['name']}' added.");
    }

    public function update(Request $request, Country $country): RedirectResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255|unique:countries,name,' . $country->id,
            'code'              => 'required|string|max:3|unique:countries,code,' . $country->id,
            'region'            => 'nullable|string|max:255',
            'currency_code'     => 'nullable|string|max:3',
            'dial_code'         => 'nullable|string|max:10',
            'flag'              => 'nullable|string|max:16',
            'regulatory_status' => 'nullable|in:approved,restricted,pending,banned',
        ], [
            'name.unique' => 'That country already exists.',
            'code.unique' => 'That country code is already in use.',
        ]);

        $data['code']              = strtoupper($data['code']);
        $data['flag']              = !empty($data['flag']) ? $data['flag'] : CountrySeeder::flagEmoji($data['code']);
        $data['regulatory_status'] = $data['regulatory_status'] ?? $country->regulatory_status;

        $country->update($data);

        return back()->with('success', "Country '{$country->name}' updated.");
    }

    public function destroy(Country $country): RedirectResponse
    {
        if ($country->productRegistrations()->exists()) {
            return back()->with('error', "Cannot delete '{$country->name}' — it is linked to product registrations.");
        }

        $name = $country->name;
        $country->delete();

        return back()->with('success', "Country '{$name}' removed.");
    }

    private function uniqueCode(string $name): string
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
}
