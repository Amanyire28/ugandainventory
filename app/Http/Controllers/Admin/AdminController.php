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
        $query->where('is_active', $request->status === 'active');
    }

    // Role filter
    if ($request->filled('role')) {
        $query->where('role_id', $request->role);
    }

    // Order by business_id to assist grouping visually
    $users = $query->orderBy('business_id', 'asc')->latest()->paginate(50);
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
        // Define all system features available to gate
        $availableFeatures = [
            'pos' => 'Point of Sale (POS) Billing',
            'products' => 'Products Management',
            'inventory' => 'Inventory & Stock Session Audits',
            'invoices' => 'Invoices / Credit Sales',
            'customers' => 'Customer Accounts & Ledger',
            'suppliers' => 'Supplier Tracking',
            'expenses' => 'Expense Records',
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

    public function monitorBusiness(Business $business)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        if (session('two_factor_verified') !== true) {
            return redirect()->route('admin.auth.twofactor.show');
        }

        // Load operations indicators
        $users = User::where('business_id', $business->id)->with('role')->get();
        $recentSales = Sale::where('business_id', $business->id)->latest()->take(20)->get();
        
        // Dynamic stats
        $totalSales = Sale::where('business_id', $business->id)->count();
        $totalRevenue = Sale::where('business_id', $business->id)->sum('grand_total');
        $totalInvoices = Invoice::where('business_id', $business->id)->count();
        $totalPayments = Payment::where('business_id', $business->id)->count();

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
            'totalRevenue', 'totalInvoices', 'totalPayments', 'activityLogs'
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
            DB::table('payments')->where('business_id', $business->id)->delete();
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
}