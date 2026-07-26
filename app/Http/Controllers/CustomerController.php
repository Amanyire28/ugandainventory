<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Display customers list
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userRole = $user->role->name;

        $query = Customer::where('business_id', $user->business_id)
            ->where('is_active', true);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')->paginate(20);

        // ✅ LOAD DIFFERENT VIEW BASED ON ROLE
        if ($userRole === 'cashier') {
            return view('cashier.customers-index', compact('customers'));
        }

        return view('customers.index', compact('customers'));
    }

    /**
     * Show form to create customer
     */
    public function create()
    {
        $user = Auth::user();
        $userRole = $user->role->name;

        // ✅ LOAD DIFFERENT VIEW BASED ON ROLE
        if ($userRole === 'cashier') {
            return view('cashier.customers-create');
        }

        return view('customers.create');
    }

    /**
     * Store new customer
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        try {
            $customer = Customer::create([
                'business_id' => $user->business_id,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'is_active' => true,
            ]);

            return redirect()->route('customers.index')
                ->with('success', "Customer '{$customer->name}' added successfully! ✅");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to add customer: ' . $e->getMessage());
        }
    }

    /**
     * Show customer details
     */
    public function show(Customer $customer)
    {
        $user = Auth::user();
        $userRole = $user->role->name;

        if ($customer->business_id !== $user->business_id) {
            abort(403);
        }

        $customer->load(['sales' => function($query) {
            $query->latest()->limit(10);
        }]);

        // Calculate customer stats
        $totalPurchases = $customer->sales()->count();
        $totalSpent = $customer->sales()->sum('total');
        $lastPurchase = $customer->sales()->latest()->first();

        // ✅ LOAD DIFFERENT VIEW BASED ON ROLE
        if ($userRole === 'cashier') {
            return view('cashier.customers-show', compact('customer', 'totalPurchases', 'totalSpent', 'lastPurchase'));
        }

        return view('customers.show', compact('customer', 'totalPurchases', 'totalSpent', 'lastPurchase'));
    }

    /**
     * Show edit form
     */
    public function edit(Customer $customer)
    {
        $user = Auth::user();
        $userRole = $user->role->name;

        if ($customer->business_id !== $user->business_id) {
            abort(403);
        }

        // Cashiers cannot edit customers (only create & view)
        if ($userRole === 'cashier') {
            abort(403, 'Cashiers cannot edit customers.');
        }

        return view('customers.edit', compact('customer'));
    }

    /**
     * Update customer
     */
    public function update(Request $request, Customer $customer)
    {
        $user = Auth::user();
        $userRole = $user->role->name;

        if ($customer->business_id !== $user->business_id) {
            abort(403);
        }

        // Cashiers cannot edit customers
        if ($userRole === 'cashier') {
            abort(403, 'Cashiers cannot edit customers.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        try {
            $customer->update($validated);

            return redirect()->route('customers.index')
                ->with('success', "Customer '{$customer->name}' updated successfully! ✅");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update customer: ' . $e->getMessage());
        }
    }

    /**
     * Delete customer
     */
    public function destroy(Customer $customer)
    {
        $user = Auth::user();
        $userRole = $user->role->name;

        if ($customer->business_id !== $user->business_id) {
            abort(403);
        }

        // Only owner/admin can delete
        if (!in_array($userRole, ['owner', 'admin'])) {
            abort(403, 'You do not have permission to delete customers.');
        }

        $customer->update(['is_active' => false]);

        return redirect()->route('customers.index')
            ->with('success', 'Customer deactivated successfully!');
    }
}