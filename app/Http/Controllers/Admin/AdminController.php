<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Admin;
use App\Models\User;
use App\Models\Role; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    // ========================================
    // SETUP (First Admin)
    // ========================================
    public function showSetup()
    {
        if (Admin::exists()) {
            return redirect()->route('admin.login');
        }
        return view('admin.auth.setup');
    }

    public function storeSetup(Request $request)
    {
        if (Admin::exists()) {
            return redirect()->route('admin.login');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin = Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
            'is_superadmin' => true,
        ]);

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        session(['two_factor_verified' => true]);
        return redirect()->route('admin.dashboard')
            ->with('success', 'Admin created successfully.');
    }

    // ========================================
    // LOGIN
    // ========================================
    public function showLogin()
    {
        if (! Admin::exists()) {
            return redirect()->route('admin.setup.show');
        }
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $admin = Admin:: where('email', $data['email'])->first();
        
        if ($admin && ! $admin->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This admin account is deactivated.'],
            ]);
        }

        if (! Auth::guard('admin')->attempt(
            ['email' => $data['email'], 'password' => $data['password']],
            $request->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        $request->session()->regenerate();

        $authAdmin = Auth::guard('admin')->user();
        if ($authAdmin) {
            $authAdmin->last_login_at = now();
            $authAdmin->save();
        }

        session(['two_factor_verified' => true]);
        return redirect()->route('admin.dashboard')
            ->with('success', 'Welcome back!');
    }

    // ========================================
    // LOGOUT
    // ========================================
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('success', 'Logged out.');
    }

    // ========================================
    // DASHBOARD (Protected)
    // ========================================
    public function dashboard()
    {
        // ✅ CHECK IF ADMIN IS LOGGED IN
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        // ✅ CHECK IF 2FA IS VERIFIED
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        $admin = Auth::guard('admin')->user();
        
        // ========================================
        // 1. QUICK STATS
        // ========================================
        $stats = [
            'total_users' => User::count(),
            'active_users' => User:: where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'total_businesses' => Business:: count(),
            'total_admins' => Admin::count(),
            'admins_active' => Admin::where('is_active', true)->count(),
        ];

        // ========================================
        // 2. USERS GROWTH (Last 30 days)
        // ========================================
        $usersGrowth = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ========================================
        // 3. USER DISTRIBUTION BY ROLE
        // ========================================
        $usersByRole = User::with('role')
            ->select('role_id', DB::raw('COUNT(*) as count'))
            ->groupBy('role_id')
            ->get()
            ->map(function($item) {
                return [
                    'role' => $item->role?->name ?? 'No Role',
                    'count' => $item->count,
                ];
            });

        // ========================================
        // 4. BUSINESS STATUS DISTRIBUTION
        // ========================================
        $businessStatus = Business::selectRaw('is_active, COUNT(*) as count')
            ->groupBy('is_active')
            ->get()
            ->map(function($item) {
                return [
                    'status' => $item->is_active ? 'Active' : 'Inactive',
                    'count' => $item->count,
                ];
            });

        // ========================================
        // 5. RECENT USERS (Last 5)
        // ========================================
        $recentUsers = User::with('role')
            ->latest()
            ->take(5)
            ->get();

        // ========================================
        // 6. RECENT BUSINESSES (Last 5)
        // ========================================
        $recentBusinesses = Business::with('owner')
            ->latest()
            ->take(5)
            ->get();

        // ========================================
        // 7. ADMIN ACTIVITY
        // ========================================
        $adminActivity = Admin::where('is_active', true)
            ->orderBy('last_login_at', 'desc')
            ->take(5)
            ->get();

        // ========================================
        // 8. 2FA ENABLED STATS
        // ========================================
        $twoFactorStats = [
            'users_2fa_enabled' => User::where('two_factor_enabled', true)->count(),
            'admins_2fa_enabled' => Admin::where('two_factor_enabled', true)->count(),
        ];

        return view('admin.dashboard', compact(
            'admin',
            'stats',
            'usersGrowth',
            'usersByRole',
            'businessStatus',
            'recentUsers',
            'recentBusinesses',
            'adminActivity',
            'twoFactorStats'
        ));
    }

    // ========================================
    // USERS MANAGEMENT (Protected)

    // ========================================

    // ========================================
// USERS MANAGEMENT (Protected)
// ========================================
public function users(Request $request)
{
    // ✅ PROTECT THIS ROUTE
    if (! Auth::guard('admin')->check()) {
        return redirect()->route('admin.login');
    }
    if (session('two_factor_verified') !== true) {
        return redirect()->route('admin.auth.twofactor.show');
    }

    $query = User::with('role');

    // Search filter
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
    }

    // Status filter
    if ($request->filled('status')) {
        $query->where('is_active', $request->status === 'active');
    }

    // Role filter
    if ($request->filled('role')) {
        $query->where('role_id', $request->role);
    }

    $users = $query->latest()->paginate(20);
    $roles = Role::all();

    return view('admin.users.index', compact('users', 'roles'));
}
   

    public function toggleUserActive(User $user)
    {
        // ✅ PROTECT THIS ROUTE
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        $user->update(['is_active' => !$user->is_active]);

        return back()->with('success', 
            $user->is_active ?  'User activated.' : 'User deactivated.');
    }

    public function updateUserEmail(Request $request, User $user)
    {
        // ✅ PROTECT THIS ROUTE
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        $data = $request->validate([
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update(['email' => $data['email']]);

        return back()->with('success', 'User email updated.');
    }

    // ========================================
    // PROFILE (Protected)
    // ========================================
    public function editProfile()
    {
        // ✅ PROTECT THIS ROUTE
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        $admin = Auth::guard('admin')->user();
        return view('admin.profile.edit', compact('admin'));
    }

    // ========================================
// UPDATE USER (For Edit Modal)
// ========================================
public function updateUser(Request $request, User $user)
{
    // ✅ PROTECT THIS ROUTE
    if (! Auth::guard('admin')->check()) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    if (session('two_factor_verified') !== true) {
        return response()->json(['error' => 'Verification required'], 401);
    }

    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
        'role_id' => ['required', 'exists:roles,id'],
        'password' => ['nullable', 'string', 'min:8', 'confirmed'],
    ]);

    $user->name = $data['name'];
    $user->email = $data['email'];
    $user->role_id = $data['role_id'];

    if (! empty($data['password'])) {
        $user->password = Hash:: make($data['password']);
    }

    $user->save();

    return response()->json([
        'success' => true,
        'message' => 'User updated successfully.',
        'user' => $user
    ]);
}

    public function updateProfile(Request $request)
    {
        // ✅ PROTECT THIS ROUTE
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }
    // ✅ LOAD BOTH role AND business
     $query = User::with(['role', 'business']);
     
        $admin = Auth::guard('admin')->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('admins')->ignore($admin->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $admin->name = $data['name'];
        $admin->email = $data['email'];
        
        if (! empty($data['password'])) {
            $admin->password = Hash::make($data['password']);
        }
        
        $admin->save();

        return back()->with('success', 'Profile updated.');
    }

    // ========================================
    // BUSINESSES (TENANTS) MANAGEMENT
    // ========================================
    public function businesses(Request $request)
    {
        // ✅ PROTECT THIS ROUTE
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        $query = Business::with(['owner', 'businessCategory']);

        // Search filter (business name, email, or phone)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('business_category_id', $request->category_id);
        }

        // Plan filter
        if ($request->filled('plan')) {
            $query->where('subscription_plan', $request->plan);
        }

        $businesses = $query->latest()->paginate(20);
        $categories = \App\Models\BusinessCategory::all();

        return view('admin.businesses.index', compact('businesses', 'categories'));
    }

    public function toggleBusinessActive(Business $business)
    {
        // ✅ PROTECT THIS ROUTE
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        $business->update(['is_active' => !$business->is_active]);

        return back()->with('success', 
            $business->is_active ? 'Business account activated successfully.' : 'Business account suspended successfully.');
    }

    public function updateBusinessSubscription(Request $request, Business $business)
    {
        // ✅ PROTECT THIS ROUTE
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        $data = $request->validate([
            'subscription_plan' => ['required', 'string', 'max:255'],
            'subscription_expires_at' => ['nullable', 'date'],
        ]);

        // Normalize plan value to valid database ENUM options
        $plan = strtolower($data['subscription_plan']);
        if ($plan === 'free trial' || $plan === 'trial') {
            $plan = 'trial';
        } elseif ($plan === 'basic') {
            $plan = 'basic';
        } elseif ($plan === 'premium') {
            $plan = 'premium';
        } else {
            // Map 'enterprise' or any fallback to 'standard'
            $plan = 'standard';
        }

        $business->update([
            'subscription_plan' => $plan,
            'subscription_expires_at' => $data['subscription_expires_at'] ? \Carbon\Carbon::parse($data['subscription_expires_at']) : null,
        ]);

        return back()->with('success', 'Business subscription updated successfully.');
    }
}