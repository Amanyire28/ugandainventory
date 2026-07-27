<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    /**
     * Display a listing of all branches/locations under the active business.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $businessId = $user->business_id;

        $branches = Location::where('business_id', $businessId)
            ->withCount(['users', 'sales', 'inventories'])
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->get();

        // Calculate summary metrics for each branch
        foreach ($branches as $branch) {
            $branch->today_sales = DB::table('sales')
                ->where('business_id', $businessId)
                ->where('location_id', $branch->id)
                ->whereDate('sale_date', today())
                ->sum('total');

            $branch->total_stock_qty = DB::table('inventory')
                ->where('location_id', $branch->id)
                ->sum('quantity');
        }

        return view('branches.index', compact('branches'));
    }

    /**
     * Store a newly created branch/location in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'address'  => 'nullable|string|max:500',
            'phone'    => 'nullable|string|max:30',
            'is_main'  => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $isMain = !empty($validated['is_main']);

            // If new branch is marked as main, unmark existing main branches
            if ($isMain) {
                Location::where('business_id', $user->business_id)
                    ->update(['is_main' => false]);
            }

            $branch = Location::create([
                'business_id' => $user->business_id,
                'name'        => $validated['name'],
                'address'     => $validated['address'] ?? null,
                'phone'       => $validated['phone'] ?? null,
                'is_main'     => $isMain,
                'is_active'   => true,
            ]);

            DB::commit();

            return redirect()->route('branches.index')
                ->with('success', "Branch '{$branch->name}' added successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to add branch: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified branch/location in storage.
     */
    public function update(Request $request, Location $branch)
    {
        $user = Auth::user();

        if ($branch->business_id !== $user->business_id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'address'   => 'nullable|string|max:500',
            'phone'     => 'nullable|string|max:30',
            'is_main'   => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $isMain = !empty($validated['is_main']);

            // If updating to main branch, unmark other main branches
            if ($isMain && !$branch->is_main) {
                Location::where('business_id', $user->business_id)
                    ->where('id', '!=', $branch->id)
                    ->update(['is_main' => false]);
            }

            $branch->update([
                'name'      => $validated['name'],
                'address'   => $validated['address'] ?? null,
                'phone'     => $validated['phone'] ?? null,
                'is_main'   => $isMain,
                'is_active' => isset($validated['is_active']) ? (bool)$validated['is_active'] : $branch->is_active,
            ]);

            DB::commit();

            return redirect()->route('branches.index')
                ->with('success', "Branch '{$branch->name}' updated successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update branch: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified branch/location from storage.
     */
    public function destroy(Location $branch)
    {
        $user = Auth::user();

        if ($branch->business_id !== $user->business_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($branch->is_main) {
            return redirect()->back()
                ->with('error', 'Cannot delete the Main Branch. Designate another branch as Main first.');
        }

        try {
            $branchName = $branch->name;
            $branch->delete();

            return redirect()->route('branches.index')
                ->with('success', "Branch '{$branchName}' has been deleted.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete branch: ' . $e->getMessage());
        }
    }

    /**
     * Switch the active branch jurisdiction in user session.
     */
    public function switchBranch(Request $request)
    {
        $user = Auth::user();
        $locationId = $request->input('location_id');

        if ($locationId === 'all') {
            session()->forget('active_location_id');
            return redirect()->back()->with('success', 'Switched viewing context to All Branches (Consolidated).');
        }

        $branch = Location::where('business_id', $user->business_id)
            ->where('id', $locationId)
            ->first();

        if ($branch) {
            session(['active_location_id' => $branch->id]);
            return redirect()->back()->with('success', "Switched viewing context to branch '{$branch->name}'.");
        }

        return redirect()->back()->with('error', 'Selected branch was not found.');
    }
}
