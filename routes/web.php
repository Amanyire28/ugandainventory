<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\{LoginController, RegisterController};
use App\Http\Controllers\{
    DashboardController,
    Admin\AdminController,
    CashierDashboardController,
    InvoiceController,
    CashierPerformanceController,
    ProfileController,
    StaffController,
    ProductController,
    CategoryController,
    CashierInvoiceController, 
    SaleController, 
    PurchaseController,
    InventoryController,
    SupplierController,
    CustomerController,
    ReportController,
    POSController,
    ProfitController,
    SettingsController,
    CashierSalesController,
    CashierCustomerController,
    CashierProductController,
    CashierProfileController
};
use App\Http\Controllers\ExpenseController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

/*
|--------------------------------------------------------------------------
| GUEST ROUTES (Login & Register)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // ========================================
    // LOGOUT
    // ========================================
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // ========================================
    // OWNER PROFILE ROUTES (FIXED)
    // ========================================
    // NOTE: When using Route::prefix('owner'), inner paths MUST NOT repeat "owner".
    Route::prefix('owner')->name('owner.')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');                         // GET  /owner/profile
        Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');                    // POST /owner/profile
        Route::get('/profile/avatar', [ProfileController::class, 'avatar'])->name('profile.avatar');              // GET  /owner/profile/avatar
        Route::post('/profile/email', [ProfileController::class, 'updateEmail'])->name('profile.update_email');   // POST /owner/profile/email
        Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update_password'); // POST /owner/profile/password
        Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.update_photo');   // POST /owner/profile/photo
        Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.delete_photo'); // DELETE /owner/profile/photo
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');                // DELETE /owner/profile
    });

    // ========================================
    // MAIN DASHBOARD (Auto-redirects based on role)
    // ========================================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/annual', [DashboardController::class, 'annual'])->name('dashboard.annual');
    Route::get('/dashboard/annual/export', [DashboardController::class, 'exportAnnual'])->name('dashboard.annual.export');

    // ========================================
    // PROFILE MANAGEMENT (Everyone)
    // ========================================
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::patch('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // ========================================
    // POS - POINT OF SALE (Everyone)
    // ========================================
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [POSController::class, 'index'])->name('index');
        Route::post('/process', [POSController::class, 'process'])->name('process');
        Route::get('/product/{id}', [POSController::class, 'getProduct'])->name('product');
        Route::get('/receipt/{id}', [POSController::class, 'receipt'])->name('receipt');
        Route::get('/print/{id}', [POSController::class, 'printReceipt'])->name('print');
    });

    // ========================================
    // SALES (Everyone - filtered in controller)
    // ========================================
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SaleController::class, 'index'])->name('index');
        Route::get('/today', [SaleController::class, 'today'])->name('today');
        Route::get('/weekly', [SaleController::class, 'weekly'])->name('weekly');
        Route::get('/monthly', [SaleController::class, 'monthly'])->name('monthly');
        Route::get('/export/today', [SaleController::class, 'exportToday'])->name('export.today');
        Route::get('/export/weekly', [SaleController::class, 'exportWeekly'])->name('export.weekly');
        Route::get('/export/monthly', [SaleController::class, 'exportMonthly'])->name('export.monthly');
        Route::get('/{sale}', [SaleController::class, 'show'])->name('show');
    });

    // ========================================
    // PRODUCTS (Role checked in controller)
    // ========================================
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/expired', [ProductController::class, 'expired'])->name('expired');
        Route::get('/expiring-soon', [ProductController::class, 'expiringSoon'])->name('expiring-soon');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
        Route::get('/{id}', [ProductController::class, 'show'])->name('show');
    });

    // ========================================
    // CATEGORIES
    // ========================================
    //Route::resource('categories', CategoryController::class);

    // ========================================
    // PROFIT REPORT
    // ========================================
    Route::get('/profit', [ProfitController::class, 'index'])->name('profit.index');

    // ========================================
    // INVENTORY (Role checked in controller)
    // ========================================
    Route::get('/inventory/activities', [InventoryController::class, 'activities'])->name('inventory.activities');
    Route::get('/inventory/periods', [InventoryController::class, 'periods'])->name('inventory.periods');
    Route::get('/inventory/reconciliation/{periodId}', [InventoryController::class, 'showReconciliation'])->name('inventory.reconciliation');
    Route::get('/api/inventory/reconciliation/{productId}/{periodStart?}/{periodEnd?}', [InventoryController::class, 'getReconciliation'])->name('api.inventory.reconciliation');
    Route::resource('inventory', InventoryController::class);

    // ========================================
    // STOCK TAKING (Inventory Reconciliation)
    // ========================================
    Route::prefix('stock-taking')->name('stock-taking.')->group(function () {
        Route::get('/', [InventoryController::class, 'stockTakingIndex'])->name('index');
        Route::get('/session/{id}', [InventoryController::class, 'stockTakingSession'])->name('session');
        Route::post('/session', [InventoryController::class, 'createSession'])->name('create-session');
        Route::post('/record', [InventoryController::class, 'recordCount'])->name('record-count');
        Route::post('/close-session/{id}', [InventoryController::class, 'closeSession'])->name('close-session');
    });

    // ========================================
    // SUPPLIERS (Role checked in controller)
    // ========================================
    Route::resource('suppliers', SupplierController::class);

    // ========================================
    // CUSTOMERS (Role checked in controller)
    // ========================================
    Route::resource('customers', CustomerController::class);

    // ========================================
    // REPORTS (Role checked in controller)
    // ========================================
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/products', [ReportController::class, 'products'])->name('products');
        Route::get('/top-selling', [ReportController::class, 'topSelling'])->name('top-selling');
        Route::get('/custom', [ReportController::class, 'custom'])->name('custom');
        Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
    });

    // ========================================
    // STAFF MANAGEMENT (Role checked in controller)
    // ========================================
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/', [StaffController::class, 'index'])->name('index');
        Route::get('/create', [StaffController::class, 'create'])->name('create');
        Route::post('/', [StaffController::class, 'store'])->name('store');
        Route::get('/{staff}', [StaffController::class, 'show'])->name('show');
        Route::get('/{staff}/edit', [StaffController::class, 'edit'])->name('edit');
        Route::put('/{staff}', [StaffController::class, 'update'])->name('update');
        Route::delete('/{staff}', [StaffController::class, 'destroy'])->name('destroy');
        Route::patch('/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])->name('toggle-status');
    });

    // ========================================
    // ✅ BUSINESS SETTINGS (CONSOLIDATED - Admin/Owner only)
    // ========================================
    Route::prefix('settings')->name('settings.')->group(function () {

        // Main settings page
        Route::get('/', [SettingsController::class, 'index'])->name('index');

        // Business Information
        Route::put('/info', [SettingsController::class, 'updateInfo'])->name('update-info');

        // Logo Management
        Route::post('/logo', [SettingsController::class, 'updateLogo'])->name('update-logo');
        Route::delete('/logo', [SettingsController::class, 'removeLogo'])->name('remove-logo');

        // Email Settings
        Route::put('/email', [SettingsController::class, 'updateEmail'])->name('update-email');
        Route::get('/email/test', fn() => redirect()->route('settings.index')->with('info', 'Use the test form on the settings page to send a test email.'))->name('test-email-get');
        Route::post('/email/test', [SettingsController::class, 'testEmail'])->name('test-email');
        Route::delete('/email', [SettingsController::class, 'removeEmail'])->name('remove-email');


        // Tax Settings
        Route::put('/tax', [SettingsController::class, 'updateTax'])->name('update-tax');

        // Toggle Business Status
        Route::post('/toggle-status', [SettingsController::class, 'toggleStatus'])->name('toggle-status');
    });

    // ========================================
    // PURCHASES (Role checked in controller)
    // ========================================
    //Route::resource('purchases', PurchaseController::class);

    // ========================================
    // CASHIER ROUTES (Isolated System)
    // ========================================
    Route::prefix('cashier')->name('cashier.')->group(function () {

        // Dashboard
        Route::get('/dashboard', [CashierDashboardController::class, 'index'])->name('dashboard');

        // Sales (Cashier-specific views)
        Route::get('/sales', [CashierSalesController::class, 'index'])->name('sales');
        Route::get('/sales/today', [CashierSalesController::class, 'today'])->name('sales.today');
        Route::get('/sales/{sale}', [CashierSalesController::class, 'show'])->name('sales.show');

        // Performance (Charts & Reports)
        Route::get('/performance', [CashierPerformanceController::class, 'index'])->name('performance');
        Route::get('/performance/daily', [CashierPerformanceController::class, 'daily'])->name('performance.daily');
        Route::get('/performance/weekly', [CashierPerformanceController::class, 'weekly'])->name('performance.weekly');
        Route::get('/performance/monthly', [CashierPerformanceController::class, 'monthly'])->name('performance.monthly');

        // Customers (View & Add only)
        Route::get('/customers', [CashierCustomerController::class, 'index'])->name('customers');
        Route::get('/customers/create', [CashierCustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CashierCustomerController::class, 'store'])->name('customers.store');

        // Product Search (Read-only)
        Route::get('/products', [CashierProductController::class, 'index'])->name('products');
        Route::get('/products/{product}', [CashierProductController::class, 'show'])->name('products.show');

        // Profile
        Route::get('/profile', [CashierProfileController::class, 'edit'])->name('profile');
        Route::patch('/profile', [CashierProfileController::class, 'update'])->name('profile.update');
        Route::patch('/profile/password', [CashierProfileController::class, 'updatePassword'])->name('profile.password');
    });


});

 // Cashier Invoices (CREDIT/INVOICE)
Route::get('/cashier/invoices', [CashierInvoiceController::class, 'index'])->name('cashier.invoices.index');
Route::get('/cashier/invoices/paid', [CashierInvoiceController::class, 'paid'])->name('cashier.invoices.paid');
Route::get('/cashier/invoices/unpaid', [CashierInvoiceController::class, 'unpaid'])->name('cashier.invoices.unpaid');
Route::get('/cashier/invoices/{id}', [CashierInvoiceController::class, 'show'])->name('cashier.invoices.show');
Route::get('/cashier/invoices/{id}/print', [CashierInvoiceController::class, 'print'])->name('cashier.invoices.print');
Route::post('/cashier/invoices/{id}/mark-paid', [CashierInvoiceController::class, 'markPaid'])->name('cashier.invoices.markPaid');
Route::delete('/cashier/invoices/{id}', [CashierInvoiceController::class, 'destroy'])->name('cashier.invoices.destroy');
// ========================================
// EXPENSES ROUTES (as in your file)
// ========================================
Route::middleware(['auth'])->group(function () {
    // Owner/Manager (full privileges)
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');

    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/today', [ExpenseController::class, 'today'])->name('expenses.today');
    Route::get('/expenses/weekly', [ExpenseController::class, 'weekly'])->name('expenses.weekly');
    Route::get('/expenses/monthly', [ExpenseController::class, 'monthly'])->name('expenses.monthly');

    // Cashier (own-only)
    Route::prefix('cashier')->name('cashier.')->group(function () {
        Route::prefix('expenses')->name('expenses.')->group(function () {
            Route::get('/create', [ExpenseController::class, 'create'])->name('create');
            Route::post('/', [ExpenseController::class, 'store'])->name('store');

            // If these should be cashier-specific methods, adjust controller method names accordingly
            Route::get('/my', [ExpenseController::class, 'my'])->name('my');
            Route::get('/today', [ExpenseController::class, 'today'])->name('today');
        });
    });
});

use App\Http\Controllers\Auth\TwoFactorController;

// Place this inside your existing Route::middleware(['auth'])->group(...)
Route::middleware(['auth'])->group(function () {
    Route::get('/auth/2fa', [TwoFactorController::class, 'show'])->name('auth.twofactor.show');
    Route::post('/auth/2fa/verify', [TwoFactorController::class, 'verify'])->name('auth.twofactor.verify');
    Route::post('/auth/2fa/resend', [TwoFactorController::class, 'resend'])->name('auth.twofactor.resend');
});

// ========================================
// INVOICES (NEW - for credit sales/invoice printing)
// ========================================
Route::prefix('invoices')->name('invoices.')->group(function () {
    Route::get('/', [\App\Http\Controllers\InvoiceController::class, 'index'])->name('index');
    Route::get('/paid', [\App\Http\Controllers\InvoiceController::class, 'paid'])->name('paid');
    Route::get('/unpaid', [\App\Http\Controllers\InvoiceController::class, 'unpaid'])->name('unpaid');
    Route::get('/{invoice}/edit', [\App\Http\Controllers\InvoiceController::class, 'edit'])->name('edit');
    Route::post('/{invoice}/add-product', [\App\Http\Controllers\InvoiceController::class, 'addProduct'])->name('addProduct');
    Route::get('/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'show'])->name('show');
    Route::get('/{invoice}/print', [\App\Http\Controllers\InvoiceController::class, 'print'])->name('print');
    Route::delete('/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'destroy'])->name('destroy');
    Route::delete('/{invoice}/item/{item}', [\App\Http\Controllers\InvoiceController::class, 'deleteItem'])->name('item.delete');
    Route::post('/{invoice}/mark-paid', [\App\Http\Controllers\InvoiceController::class, 'markPaid'])->name('markPaid');
    // This route is for both owner and cashier (controller enforces role checks for ownership)
    Route::get('/invoices/{id}/print', [\App\Http\Controllers\InvoiceController::class, 'print'])->name('invoices.print');
   
    Route::post('/{invoice}/update-cart', [\App\Http\Controllers\InvoiceController::class, 'updateCart'])->name('updateCart');
    
    // Route::post('/pos', [\App\Http\Controllers\InvoiceController::class, 'posInvoice'])->name('pos');

    

});

Route::post('/invoices/pos', [\App\Http\Controllers\InvoiceController::class, 'posInvoice'])->name('invoices.pos');
// Cashier: Store/submit a new "credit" (invoice) sale from POS
Route::post('/cashier/pos/invoice', [CashierInvoiceController::class, 'posInvoice'])->name('cashier.posInvoice');

// Show the payment form for a specific invoice (GET)
Route::get('/invoices/{id}/pay', [InvoiceController::class, 'payForm'])->name('invoices.payForm');

// Process/post the payment for a specific invoice (POST)
Route::post('/invoices/{id}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');


Route::get('invoices/customer/{customer}', [InvoiceController::class, 'customerFinancialSummary']) ->name('invoices.customerSummary');
    
Route::get('/invoices/customers', [InvoiceController::class, 'customersWithInvoices']) ->name('invoices.customersWithInvoices');
   
Route::get('/test-invoice-customers', function () {
    return view('invoices.customers');
});

Route::get('/invoices/creditors', [InvoiceController::class, 'creditors'])  ->name('invoices.creditors');
  

// Include admin routes
require __DIR__ . '/admin.php';