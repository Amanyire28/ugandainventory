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
use Illuminate\Support\Facades\File;
use App\Models\Package;
use App\Models\Sale;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\BusinessCategory;
use App\Models\Location;
use Illuminate\Support\Str;
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
            'active_users' => User::active()->count(),
            'inactive_users' => User::inactive()->count(),
            'total_businesses' => Business::count(),
            'active_businesses' => Business::where('is_active', true)->count(),
            'inactive_businesses' => Business::where('is_active', false)->count(),
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

    $query = User::with(['role', 'business']);

    // Search filter
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // Status filter
    if ($request->filled('status')) {
        if ($request->status === 'active') {
            $query->active();
        } elseif ($request->status === 'inactive') {
            $query->inactive();
        }
    }

    // Role filter
    if ($request->filled('role')) {
        $query->where('role_id', $request->role);
    }

    // Order by business_id to assist grouping visually
    $users = $query->orderBy('business_id', 'asc')->latest()->paginate(50);
    $roles = Role::all();
    $businesses = Business::orderBy('name')->get();

    return view('admin.users.index', compact('users', 'roles', 'businesses'));
}

public function storeUser(Request $request)
{
    // ✅ PROTECT THIS ROUTE
    if (!Auth::guard('admin')->check()) {
        return redirect()->route('admin.login');
    }
    if (session('two_factor_verified') !== true) {
        return redirect()->route('admin.auth.twofactor.show');
    }

    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        'phone' => ['nullable', 'string', 'max:20'],
        'business_id' => ['nullable', 'exists:businesses,id'],
        'role_id' => ['required', 'exists:roles,id'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'is_active' => ['nullable', 'boolean'],
    ]);

    $role = Role::find($data['role_id']);
    $isOwner = ($role && strtolower($role->name) === 'owner');

    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'phone' => $data['phone'] ?? '',
        'business_id' => $data['business_id'] ?? null,
        'role_id' => $data['role_id'],
        'password' => Hash::make($data['password']),
        'is_owner' => $isOwner,
        'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        'email_verified_at' => now(),
    ]);

    \App\Models\AuditLog::logAdmin('create_user', \App\Models\User::class, $user->id, null, $user->toArray());

    return back()->with('success', "User '{$data['name']}' created successfully.");
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

        $oldStatus = $user->is_active;
        $user->update(['is_active' => !$user->is_active]);

        \App\Models\AuditLog::logAdmin(
            $user->is_active ? 'activate_user' : 'suspend_user',
            \App\Models\User::class,
            $user->id,
            ['is_active' => $oldStatus],
            ['is_active' => $user->is_active]
        );

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

        $oldEmail = $user->email;
        $user->update(['email' => $data['email']]);

        \App\Models\AuditLog::logAdmin('update_user_email', \App\Models\User::class, $user->id, ['email' => $oldEmail], ['email' => $user->email]);

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

    $oldValues = $user->getOriginal();
    $user->save();

    \App\Models\AuditLog::logAdmin('update_user', \App\Models\User::class, $user->id, $oldValues, $user->toArray());

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
        $categories = BusinessCategory::all();
        $packages = Package::where('is_active', true)->get();

        return view('admin.businesses.index', compact('businesses', 'categories', 'packages'));
    }

    public function storeBusiness(Request $request)
    {
        // ✅ PROTECT THIS ROUTE
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_category_id' => ['required', 'exists:business_categories,id'],
            'email' => ['required', 'email', 'max:255', 'unique:businesses,email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'subscription_plan' => ['required', 'string', 'max:100'],
            'subscription_duration_days' => ['nullable', 'integer', 'min:1'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::beginTransaction();
        try {
            $slug = Str::slug($data['name']);
            $originalSlug = $slug;
            $counter = 1;

            while (Business::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $durationDays = (int) ($data['subscription_duration_days'] ?? 30);
            $expiresAt = now()->addDays($durationDays);

            $business = Business::create([
                'name' => $data['name'],
                'slug' => $slug,
                'business_category_id' => $data['business_category_id'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'] ?? null,
                'is_active' => true,
                'subscription_plan' => strtolower($data['subscription_plan']),
                'subscription_expires_at' => $expiresAt,
            ]);

            Location::create([
                'business_id' => $business->id,
                'name' => 'Main Location',
                'is_main' => true,
                'is_active' => true,
            ]);

            $ownerRole = Role::where('name', 'owner')->first() ?? Role::first();

            $owner = User::create([
                'business_id' => $business->id,
                'role_id' => $ownerRole->id,
                'name' => $data['owner_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['owner_password']),
                'is_owner' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            \App\Models\AuditLog::logAdmin('create_business', \App\Models\Business::class, $business->id, null, $business->toArray());

            DB::commit();

            return back()->with('success', "Business '{$business->name}' and Owner account created successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create business: ' . $e->getMessage());
        }
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

        $oldStatus = $business->is_active;
        $business->update(['is_active' => !$business->is_active]);

        \App\Models\AuditLog::logAdmin(
            $business->is_active ? 'activate_business' : 'suspend_business', 
            \App\Models\Business::class, 
            $business->id, 
            ['is_active' => $oldStatus], 
            ['is_active' => $business->is_active]
        );

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

    // ========================================
    // PACKAGES MANAGEMENT ACTIONS
    // ========================================

    public function packagesIndex(Request $request)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        $packages = Package::all();
        // Define all system features available to gate in Packages Management
        $availableFeatures = [
            'pos' => 'Point of Sale (POS) Billing & Sales',
            'products' => 'Products Management',
            'inventory' => 'Inventory & Stock Session Audits',
            'invoices' => 'Invoices / Credit Sales',
            'vat' => 'VAT Management & Accounting',
            'branches' => 'Multi-Branch Location Management',
            'stock_transfers' => 'Inter-Branch Stock Transfers',
            'customers' => 'Customer Accounts & Ledger',
            'suppliers' => 'Supplier Tracking & Management',
            'expenses' => 'Expense Records & Tracking',
            'reports' => 'Profit & Sales Analytics Reports'
        ];

        return view('admin.packages.index', compact('packages', 'availableFeatures'));
    }

    public function packagesStore(Request $request)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:packages'],
            'slug' => ['required', 'string', 'max:255', 'unique:packages'],
            'description' => ['nullable', 'string'],
            'features' => ['required', 'array'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle_days' => ['required', 'integer', 'min:1'],
        ]);

        Package::create($data);

        return back()->with('success', 'Subscription package created successfully.');
    }

    public function packagesUpdate(Request $request, Package $package)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('packages')->ignore($package->id)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('packages')->ignore($package->id)],
            'description' => ['nullable', 'string'],
            'features' => ['required', 'array'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle_days' => ['required', 'integer', 'min:1'],
        ]);

        $package->update($data);

        return back()->with('success', 'Subscription package updated successfully.');
    }

    public function packagesDestroy(Package $package)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        // Prevent deleting active/in-use package to avoid breaking relationships
        $businessesUsingCount = Business::where('subscription_plan', $package->slug)->count();
        if ($businessesUsingCount > 0) {
            return back()->with('error', "Cannot delete package. It is currently assigned to {$businessesUsingCount} business(es).");
        }

        $package->delete();
        return back()->with('success', 'Subscription package deleted successfully.');
    }

    // ========================================
    // BUSINESS OPERATIONS MONITORING ACTIONS
    // ========================================

    public function monitorBusiness(Request $request, Business $business)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        // Get filter inputs
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');

        // Defaults to start of current month to today (matches PWA Reports default!)
        if (empty($start_date)) {
            $start_date = now()->startOfMonth()->format('Y-m-d');
        }
        if (empty($end_date)) {
            $end_date = now()->format('Y-m-d');
        }

        $start_time = $start_date . ' 00:00:00';
        $end_time = $end_date . ' 23:59:59';

        // Load operations indicators
        $users = User::where('business_id', $business->id)->with('role')->get();
        $recentSales = Sale::where('business_id', $business->id)
            ->whereBetween('sale_date', [$start_time, $end_time])
            ->latest()
            ->take(20)
            ->get();
        
        // Dynamic stats in timeframe
        $totalSales = Sale::where('business_id', $business->id)
            ->whereBetween('sale_date', [$start_time, $end_time])
            ->count();
        $totalRevenue = Sale::where('business_id', $business->id)
            ->whereBetween('sale_date', [$start_time, $end_time])
            ->sum('total');
        $totalInvoices = Invoice::where('business_id', $business->id)
            ->whereBetween('created_at', [$start_time, $end_time])
            ->count();
        $totalPayments = Payment::whereIn('invoice_id', function($q) use ($business) {
            $q->select('id')->from('invoices')->where('business_id', $business->id);
        })
        ->whereBetween('paid_at', [$start_time, $end_time])
        ->count();

        // Parsed activity logs from storage/logs/activity.log
        $activityLogs = [];
        $logPath = storage_path('logs/activity.log');
        
        if (File::exists($logPath)) {
            $logLines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            // Reverse so we get latest first
            $logLines = array_reverse($logLines);
            
            $limit = 100; // Limit parsed lines for performance
            $counter = 0;
            
            foreach ($logLines as $line) {
                if ($counter >= $limit) break;
                
                // Parse Monolog output e.g. [2026-07-24 08:37:03] local.INFO: User Data Modification {"user_id":1,...}
                // Extract JSON part
                preg_match('/local\.(INFO|WARNING|ERROR|DEBUG): (.*) (\{.*\})/', $line, $matches);
                if (count($matches) === 4) {
                    $actionType = $matches[2];
                    $jsonData = json_decode($matches[3], true);
                    
                    if (is_array($jsonData) && isset($jsonData['business_id']) && $jsonData['business_id'] == $business->id) {
                        $activityLogs[] = array_merge([
                            'action_title' => $actionType,
                            'timestamp' => $jsonData['timestamp'] ?? '',
                        ], $jsonData);
                        $counter++;
                    }
                }
            }
        }

        return view('admin.businesses.monitor', compact(
            'business', 'users', 'recentSales', 'totalSales', 
            'totalRevenue', 'totalInvoices', 'totalPayments', 'activityLogs',
            'start_date', 'end_date'
        ));
    }

    public function resetBusinessTransactions(Business $business)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        // Delete sales, invoices, stock adjust session and related data for this tenant
        DB::transaction(function() use ($business) {
            // Delete payments & invoice items
            DB::table('payments')->whereIn('invoice_id', function($q) use ($business) {
                $q->select('id')->from('invoices')->where('business_id', $business->id);
            })->delete();
            DB::table('invoice_items')->whereIn('invoice_id', function($q) use ($business) {
                $q->select('id')->from('invoices')->where('business_id', $business->id);
            })->delete();
            DB::table('invoices')->where('business_id', $business->id)->delete();

            // Delete sales & sale items
            DB::table('sale_items')->whereIn('sale_id', function($q) use ($business) {
                $q->select('id')->from('sales')->where('business_id', $business->id);
            })->delete();
            DB::table('sales')->where('business_id', $business->id)->delete();
            
            // Delete purchases & expenses
            DB::table('purchase_items')->whereIn('purchase_id', function($q) use ($business) {
                $q->select('id')->from('purchases')->where('business_id', $business->id);
            })->delete();
            DB::table('purchases')->where('business_id', $business->id)->delete();
            DB::table('expenses')->where('business_id', $business->id)->delete();

            // Reset opening stock of products to 0
            DB::table('products')->where('business_id', $business->id)->update([
                'quantity' => 0,
                'opening_stock' => 0
            ]);
            
            // Reset stock taking sessions
            DB::table('stock_taking_sessions')->where('business_id', $business->id)->delete();
            DB::table('stock_adjustments')->where('business_id', $business->id)->delete();
        });

        return back()->with('success', 'All business transactions, sales, expenses, and invoices have been fully reset.');
    }

    public function resetBusinessSettings(Business $business)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        $business->update([
            'tax_enabled' => false,
            'tax_rate' => 18.00,
            'smtp_email' => null,
            'smtp_password' => null,
            'email_configured' => false,
            'website' => null
        ]);

        return back()->with('success', 'Business settings and SMTP configurations have been reset to system defaults.');
    }

    public function revenueReport(Request $request)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        // Get filter inputs
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $business_id = $request->input('business_id');

        if (empty($start_date)) {
            $start_date = now()->startOfMonth()->format('Y-m-d');
        }
        if (empty($end_date)) {
            $end_date = now()->format('Y-m-d');
        }

        $businesses = Business::orderBy('name')->get();

        // Calculate stats query limits
        $start_time = $start_date . ' 00:00:00';
        $end_time = $end_date . ' 23:59:59';

        $totalRevenue = 0;
        $totalSalesCount = 0;
        $reportData = [];

        if (empty($business_id) || $business_id === 'all') {
            // Aggregate all businesses
            $totalRevenue = Sale::whereBetween('sale_date', [$start_time, $end_time])->sum('total');
            $totalSalesCount = Sale::whereBetween('sale_date', [$start_time, $end_time])->count();

            // Detail per business
            foreach ($businesses as $b) {
                $bRevenue = Sale::where('business_id', $b->id)
                    ->whereBetween('sale_date', [$start_time, $end_time])
                    ->sum('total');
                $bSalesCount = Sale::where('business_id', $b->id)
                    ->whereBetween('sale_date', [$start_time, $end_time])
                    ->count();

                $reportData[] = [
                    'business' => $b,
                    'revenue' => $bRevenue,
                    'sales_count' => $bSalesCount,
                ];
            }

            // Sort by revenue descending
            usort($reportData, function($a, $b) {
                return $b['revenue'] <=> $a['revenue'];
            });

            $selectedBusiness = null;
        } else {
            // Drilldown specific business
            $selectedBusiness = Business::findOrFail($business_id);
            $totalRevenue = Sale::where('business_id', $selectedBusiness->id)
                ->whereBetween('sale_date', [$start_time, $end_time])
                ->sum('total');
            $totalSalesCount = Sale::where('business_id', $selectedBusiness->id)
                ->whereBetween('sale_date', [$start_time, $end_time])
                ->count();

            // Daily breakdown for table
            $dailySales = Sale::where('business_id', $selectedBusiness->id)
                ->whereBetween('sale_date', [$start_time, $end_time])
                ->selectRaw('DATE(sale_date) as date, COUNT(*) as sales_count, SUM(total) as revenue')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();

            foreach ($dailySales as $day) {
                $reportData[] = [
                    'date' => $day->date,
                    'revenue' => $day->revenue,
                    'sales_count' => $day->sales_count,
                ];
            }
        }

        return view('Admin.reports.revenue', compact(
            'businesses',
            'start_date',
            'end_date',
            'business_id',
            'totalRevenue',
            'totalSalesCount',
            'reportData',
            'selectedBusiness'
        ));
    }

    // ========================================
    // PAYMENTS MANAGEMENT (SaaS Billing)
    // ========================================

    public function paymentsManagement(Request $request)
    {
        if (!Auth::guard('admin')->check()) return redirect()->route('admin.login');
        if (session('two_factor_verified') !== true) return redirect()->route('admin.auth.twofactor.show');

        $start_date = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end_date   = $request->input('end_date', now()->format('Y-m-d'));
        $status     = $request->input('status');
        $pkg        = $request->input('package_slug');
        $biz_id     = $request->input('business_id');

        // ── Pending submissions from businesses (always shown, no date filter) ──
        $pendingFromBusinesses = \App\Models\BusinessSubscription::with(['business', 'package', 'submittedByUser'])
            ->where('submitted_by', 'business')
            ->where('status', 'pending')
            ->latest()
            ->get();

        $query = \App\Models\BusinessSubscription::with(['business', 'package', 'submittedByUser'])
            ->whereBetween('created_at', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);

        if ($status)  $query->where('status', $status);
        if ($pkg)     $query->where('package_slug', $pkg);
        if ($biz_id)  $query->where('business_id', $biz_id);

        $payments = $query->latest()->paginate(30)->withQueryString();

        // Summary stats
        $totalCollected = (clone $query)->where('status', 'paid')->sum('amount');
        $totalPending   = (clone $query)->where('status', 'pending')->sum('amount');
        $totalCount     = (clone $query)->count();


        // Revenue per package
        $packageRevenue = \App\Models\BusinessSubscription::where('status', 'paid')
            ->whereBetween('created_at', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])
            ->selectRaw('package_slug, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('package_slug')
            ->get();

        $businesses = Business::orderBy('name')->get();
        $packages   = Package::where('is_active', true)->orderBy('name')->get();

        return view('Admin.payments.index', compact(
            'payments', 'start_date', 'end_date', 'status', 'pkg',
            'biz_id', 'totalCollected', 'totalPending', 'totalCount',
            'packageRevenue', 'businesses', 'packages', 'pendingFromBusinesses'
        ));
    }

    public function recordPayment(Request $request)
    {
        if (!Auth::guard('admin')->check()) return redirect()->route('admin.login');
        if (session('two_factor_verified') !== true) return redirect()->route('admin.auth.twofactor.show');

        $data = $request->validate([
            'business_id'    => 'required|exists:businesses,id',
            'package_slug'   => 'required|string',
            'amount'         => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'reference'      => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
            'period_start'   => 'nullable|date',
            'period_end'     => 'nullable|date',
            'status'         => 'required|in:pending,paid,failed,refunded,cancelled',
        ]);

        $data['recorded_by'] = Auth::guard('admin')->id();
        if ($data['status'] === 'paid') {
            $data['paid_at'] = now();
        }

        \App\Models\BusinessSubscription::create($data);

        // If paid, auto-update the business subscription plan & expiry
        if ($data['status'] === 'paid') {
            $business = Business::find($data['business_id']);
            $package  = Package::where('slug', $data['package_slug'])->first();
            if ($business && $package) {
                $expires = isset($data['period_end'])
                    ? \Carbon\Carbon::parse($data['period_end'])
                    : now()->addDays($package->billing_cycle_days);
                $business->update([
                    'subscription_plan'       => $package->slug,
                    'subscription_expires_at' => $expires,
                ]);
            }
        }

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function verifyPayment(Request $request, \App\Models\BusinessSubscription $subscription)
    {
        if (!Auth::guard('admin')->check()) return redirect()->route('admin.login');

        $subscription->update(['status' => 'paid', 'paid_at' => now()]);

        // Auto-update business plan
        $business = $subscription->business;
        $package  = Package::where('slug', $subscription->package_slug)->first();
        if ($business && $package) {
            $expires = $subscription->period_end
                ? \Carbon\Carbon::parse($subscription->period_end)
                : now()->addDays($package->billing_cycle_days);
            $business->update([
                'subscription_plan'       => $package->slug,
                'subscription_expires_at' => $expires,
            ]);
        }

        return back()->with('success', 'Payment marked as verified/paid.');
    }

    public function approvePayment(Request $request, \App\Models\BusinessSubscription $subscription)
    {
        if (!Auth::guard('admin')->check()) return redirect()->route('admin.login');

        $subscription->update(['status' => 'paid', 'paid_at' => now(), 'rejection_reason' => null]);

        // Auto-update business subscription plan & expiry
        $business = $subscription->business;
        $package  = Package::where('slug', $subscription->package_slug)->first();
        if ($business && $package) {
            $expires = $subscription->period_end
                ? \Carbon\Carbon::parse($subscription->period_end)
                : now()->addDays($package->billing_cycle_days);
            $business->update([
                'subscription_plan'       => $package->slug,
                'subscription_expires_at' => $expires,
            ]);
        }

        return back()->with('success', "Payment #{$subscription->id} approved. Business subscription activated.");
    }

    public function rejectPayment(Request $request, \App\Models\BusinessSubscription $subscription)
    {
        if (!Auth::guard('admin')->check()) return redirect()->route('admin.login');

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $subscription->update([
            'status'           => 'cancelled',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', "Payment #{$subscription->id} rejected. Business has been notified.");
    }

    public function cancelPayment(Request $request, \App\Models\BusinessSubscription $subscription)
    {
        if (!Auth::guard('admin')->check()) return redirect()->route('admin.login');

        $subscription->update(['status' => 'cancelled']);

        return back()->with('success', 'Payment has been cancelled.');
    }
}