<?php

namespace App\Http\Controllers;

use App\Models\{User, Sale, Role};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash, DB};
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    /**
     * Display a listing of staff members
     */
    public function index()
    {
        $businessId = Auth::user()->business_id;

        // Get staff with performance metrics
        $staff = User::where('business_id', $businessId)
            ->where('id', '!=', Auth::id())
            ->with('role')
            ->withCount(['sales' => function($query) {
                $query->whereMonth('sale_date', now()->month)
                      ->whereYear('sale_date', now()->year);
            }])
            ->withSum(['sales as sales_sum_total' => function($query) {
                $query->whereMonth('sale_date', now()->month)
                      ->whereYear('sale_date', now()->year);
            }], 'total')
            ->latest()
            ->paginate(20);

        // Get statistics with optimized queries
        $stats = User::where('business_id', $businessId)
            ->select(
                DB::raw('COUNT(*) as total_staff'),
                DB::raw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_staff')
            )
            ->first();

        // Get role-based counts
        $roleStats = User::where('business_id', $businessId)
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('COUNT(*) as count'))
            ->groupBy('roles.name')
            ->pluck('count', 'name');

        return view('staff.index', [
            'staff' => $staff,
            'totalStaff' => $stats->total_staff ?? 0,
            'activeStaff' => $stats->active_staff ?? 0,
            'adminCount' => $roleStats['admin'] ?? 0,
            'cashierCount' => $roleStats['cashier'] ?? 0,
            'managerCount' => $roleStats['manager'] ?? 0,
            'staffCount' => $roleStats['staff'] ?? 0,
        ]);
    }

    /**
     * Show the form for creating a new staff member
     */
    public function create()
    {
        $roles = Role::orderBy('display_name')->get();
        
        return view('staff.create', compact('roles'));
    }

    /**
     * Store a newly created staff member
     */
    public function store(Request $request)
    {
        $businessId = Auth::user()->business_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->where(function ($query) use ($businessId) {
                    return $query->where('business_id', $businessId);
                })
            ],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'exists:roles,id'],
        ], [
            'email.unique' => 'This email is already registered in your business.',
            'role_id.required' => 'Please select a role for the staff member.',
            'role_id.exists' => 'Invalid role selected.',
        ]);

        try {
            $user = User::create([
                'business_id' => $businessId,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role_id' => $validated['role_id'],
                'is_active' => true,
                'is_owner' => false,
            ]);

            return redirect()
                ->route('staff.index')
                ->with('success', "Staff member '{$user->name}' added successfully!");

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to add staff member. Please try again.');
        }
    }

    /**
     * Display the specified staff member with performance metrics
     */
    public function show(User $staff)
    {
        // Authorization check
        if ($staff->business_id !== Auth::user()->business_id) {
            abort(403, 'Unauthorized access to staff member.');
        }

        // Load relationships
        $staff->load('role');

        // Today's performance
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        
        $todayStats = Sale::where('user_id', $staff->id)
            ->whereBetween('sale_date', [$todayStart, $todayEnd])
            ->select(
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('COALESCE(SUM(total), 0) as revenue')
            )
            ->first();

        // This week's performance
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        
        $weekStats = Sale::where('user_id', $staff->id)
            ->whereBetween('sale_date', [$weekStart, $weekEnd])
            ->select(
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('COALESCE(SUM(total), 0) as revenue')
            )
            ->first();

        // This month's performance
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        
        $monthStats = Sale::where('user_id', $staff->id)
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->select(
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('COALESCE(SUM(total), 0) as revenue')
            )
            ->first();

        // All-time performance
        $allTimeStats = Sale::where('user_id', $staff->id)
            ->select(
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('COALESCE(SUM(total), 0) as revenue'),
                DB::raw('COALESCE(AVG(total), 0) as avg_sale')
            )
            ->first();

        // Sales trend (last 7 days)
        $salesTrend = Sale::where('user_id', $staff->id)
            ->whereBetween('sale_date', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->select(
                DB::raw('DATE(sale_date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(total), 0) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Recent sales
        $recentSales = Sale::where('user_id', $staff->id)
            ->with(['customer', 'items.product'])
            ->latest('sale_date')
            ->limit(10)
            ->get();

        return view('staff.show', [
            'staff' => $staff,
            'todaySales' => $todayStats->sales_count ?? 0,
            'todayRevenue' => $todayStats->revenue ?? 0,
            'weekSales' => $weekStats->sales_count ?? 0,
            'weekRevenue' => $weekStats->revenue ?? 0,
            'monthSales' => $monthStats->sales_count ?? 0,
            'monthRevenue' => $monthStats->revenue ?? 0,
            'totalSales' => $allTimeStats->sales_count ?? 0,
            'totalRevenue' => $allTimeStats->revenue ?? 0,
            'averageSale' => $allTimeStats->avg_sale ?? 0,
            'recentSales' => $recentSales,
            'salesTrend' => $salesTrend,
        ]);
    }

    /**
     * Show the form for editing the staff member
     */
    public function edit(User $staff)
    {
        // Authorization checks
        if ($staff->business_id !== Auth::user()->business_id) {
            abort(403, 'Unauthorized access to staff member.');
        }

        if ($staff->id === Auth::id()) {
            return redirect()
                ->route('staff.index')
                ->with('error', 'Use your profile settings to edit your own account.');
        }

        // Load relationships
        $staff->load('role');
        
        // Get all available roles
        $roles = Role::orderBy('display_name')->get();

        return view('staff.edit', compact('staff', 'roles'));
    }

    /**
     * Update the specified staff member
     */
    public function update(Request $request, User $staff)
    {
        // Authorization checks
        if ($staff->business_id !== Auth::user()->business_id) {
            abort(403, 'Unauthorized access to staff member.');
        }

        if ($staff->id === Auth::id()) {
            return redirect()
                ->route('staff.index')
                ->with('error', 'Use your profile settings to edit your own account.');
        }

        // Handle quick toggle (from status badge)
        if ($request->has('quick_toggle')) {
            $staff->update(['is_active' => $request->is_active]);
            $status = $request->is_active ? 'activated' : 'deactivated';
            return redirect()
                ->route('staff.index')
                ->with('success', "Staff member '{$staff->name}' {$status} successfully.");
        }

        $businessId = Auth::user()->business_id;

        // Validation rules with old password check
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->where(function ($query) use ($businessId) {
                    return $query->where('business_id', $businessId);
                })->ignore($staff->id)
            ],
            'phone' => ['required', 'string', 'max:20'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['required', 'boolean'],
        ];

        // If password change is requested, add password validation
        if ($request->filled('current_password') || $request->filled('password')) {
            $rules['current_password'] = ['required', 'string'];
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        $validated = $request->validate($rules, [
            'email.unique' => 'This email is already registered in your business.',
            'current_password.required' => 'Current password is required to set a new password.',
            'password.required' => 'New password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        try {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'role_id' => $validated['role_id'],
                'is_active' => $validated['is_active'],
            ];

            // Verify old password and update new password
            if ($request->filled('current_password')) {
                // Verify current password
                if (!Hash::check($request->current_password, $staff->password)) {
                    return back()
                        ->withInput()
                        ->withErrors(['current_password' => 'The current password is incorrect.']);
                }

                // Check if new password is different from current
                if (Hash::check($request->password, $staff->password)) {
                    return back()
                        ->withInput()
                        ->withErrors(['password' => 'New password must be different from current password.']);
                }

                // Update password
                $updateData['password'] = Hash::make($validated['password']);
            }

            $staff->update($updateData);

            $message = "Staff member '{$staff->name}' updated successfully";
            if ($request->filled('current_password')) {
                $message .= ' with new password';
            }
            $message .= '!';

            return redirect()
                ->route('staff.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update staff member. Please try again.');
        }
    }

    /**
     * Remove the specified staff member
     */
    public function destroy(User $staff)
    {
        // Authorization checks
        if ($staff->business_id !== Auth::user()->business_id) {
            abort(403, 'Unauthorized access to staff member.');
        }

        if ($staff->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Check if staff has sales records
        $salesCount = Sale::where('user_id', $staff->id)->count();
        
        if ($salesCount > 0) {
            return back()->with('error', "Cannot delete staff member with {$salesCount} sales record(s). Please deactivate instead.");
        }

        try {
            $staffName = $staff->name;
            $staff->update(['is_active' => false]);
            $staff->delete();

            return redirect()
                ->route('staff.index')
                ->with('success', "Staff member '{$staffName}' deleted successfully.");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete staff member. Please try again.');
        }
    }

    /**
     * Toggle staff active status
     */
    public function toggleStatus(User $staff)
    {
        // Authorization checks
        if ($staff->business_id !== Auth::user()->business_id) {
            abort(403, 'Unauthorized access.');
        }

        if ($staff->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        try {
            $staff->update(['is_active' => !$staff->is_active]);

            $status = $staff->is_active ? 'activated' : 'deactivated';

            return back()->with('success', "Staff member '{$staff->name}' {$status} successfully.");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update staff status. Please try again.');
        }
    }

    /**
     * Get staff performance summary (AJAX endpoint)
     */
    public function getPerformance(User $staff)
    {
        if ($staff->business_id !== Auth::user()->business_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $performance = [
            'today' => Sale::where('user_id', $staff->id)
                ->whereDate('sale_date', now())
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as revenue')
                ->first(),
            'week' => Sale::where('user_id', $staff->id)
                ->whereBetween('sale_date', [now()->startOfWeek(), now()->endOfWeek()])
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as revenue')
                ->first(),
            'month' => Sale::where('user_id', $staff->id)
                ->whereMonth('sale_date', now()->month)
                ->whereYear('sale_date', now()->year)
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as revenue')
                ->first(),
            'total' => Sale::where('user_id', $staff->id)
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as revenue')
                ->first(),
        ];

        return response()->json($performance);
    }
}