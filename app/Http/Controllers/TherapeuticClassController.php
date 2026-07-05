<?php

namespace App\Http\Controllers;

use App\Models\TherapeuticClass;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TherapeuticClassController extends Controller
{
    public function index(): View
    {
        $classes = TherapeuticClass::orderBy('name')->paginate(20);

        return view('master.therapeutic-classes', compact('classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:therapeutic_classes,name',
            'description' => 'nullable|string|max:255',
        ], [
            'name.unique' => 'That therapeutic class already exists.',
        ]);

        $data['is_active'] = true;

        TherapeuticClass::create($data);

        return back()->with('success', "Therapeutic class '{$data['name']}' added.");
    }

    public function update(Request $request, TherapeuticClass $therapeuticClass): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:therapeutic_classes,name,' . $therapeuticClass->id,
            'description' => 'nullable|string|max:255',
        ], [
            'name.unique' => 'That therapeutic class already exists.',
        ]);

        $therapeuticClass->update($data);

        return back()->with('success', "Therapeutic class '{$therapeuticClass->name}' updated.");
    }

    public function destroy(TherapeuticClass $therapeuticClass): RedirectResponse
    {
        $name = $therapeuticClass->name;
        $therapeuticClass->delete();

        return back()->with('success', "Therapeutic class '{$name}' removed.");
    }
}
