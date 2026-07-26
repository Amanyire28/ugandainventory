<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers
     */
    public function index()
    {
        $suppliers = Supplier::where('business_id', Auth::user()->business_id)
            ->latest()
            ->paginate(20);

        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Show the form for creating a new supplier
     */
    public function create()
    {
        return view('suppliers.create');
    }

    /**
     * Store a newly created supplier
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        try {
            Supplier::create([
                'business_id' => Auth::user()->business_id,
                'name' => $validated['name'],
                'contact_person' => $validated['contact_person'] ?? null,
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'country' => $validated['country'] ?? 'Uganda',
                'is_active' => true,
            ]);

            return redirect()->route('suppliers.index')
                ->with('success', "Supplier '{$validated['name']}' created successfully! ✅");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create supplier: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the supplier
     */
    public function edit(Supplier $supplier)
    {
        if ($supplier->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update the supplier
     */
    public function update(Request $request, Supplier $supplier)
    {
        if ($supplier->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'is_active' => 'required|boolean',
        ]);

        try {
            $supplier->update($validated);

            return redirect()->route('suppliers.index')
                ->with('success', "Supplier '{$supplier->name}' updated successfully! ✅");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update supplier: ' . $e->getMessage());
        }
    }

    /**
     * Remove the supplier
     */
    public function destroy(Supplier $supplier)
    {
        if ($supplier->business_id !== Auth::user()->business_id) {
            abort(403);
        }

        try {
            $name = $supplier->name;
            $supplier->delete();

            return redirect()->route('suppliers.index')
                ->with('success', "Supplier '{$name}' deleted successfully! ✅");
        } catch (\Exception $e) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Failed to delete supplier: ' . $e->getMessage());
        }
    }
}