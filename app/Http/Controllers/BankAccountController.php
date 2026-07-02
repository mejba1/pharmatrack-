<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function index(): View
    {
        $banks = BankAccount::orderByDesc('is_default')->orderBy('bank_name')->paginate(20);

        return view('master.bank-accounts', compact('banks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->applyDefault($data);
        BankAccount::create($data);

        return back()->with('success', "Bank account '{$data['bank_name']}' added.");
    }

    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $data = $this->validated($request);
        $this->applyDefault($data, $bankAccount->id);
        $bankAccount->update($data);

        return back()->with('success', "Bank account '{$bankAccount->bank_name}' updated.");
    }

    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        $name = $bankAccount->bank_name;
        $bankAccount->delete();

        return back()->with('success', "Bank account '{$name}' removed.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'bank_name'      => 'required|string|max:160',
            'account_name'   => 'nullable|string|max:160',
            'account_number' => 'nullable|string|max:60',
            'swift_code'     => 'nullable|string|max:20',
            'iban'           => 'nullable|string|max:60',
            'branch'         => 'nullable|string|max:160',
            'address'        => 'nullable|string|max:255',
            'currency'       => 'nullable|string|max:3',
            'notes'          => 'nullable|string|max:500',
        ]);

        $data['currency']  = $data['currency'] ? strtoupper($data['currency']) : null;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default');

        return $data;
    }

    /** Only one account can be the default — demote the others. */
    private function applyDefault(array $data, ?int $ignoreId = null): void
    {
        if (! empty($data['is_default'])) {
            BankAccount::when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->update(['is_default' => false]);
        }
    }
}
