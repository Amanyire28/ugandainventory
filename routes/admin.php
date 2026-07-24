<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\TwoFactorController;

Route:: prefix('admin')->name('admin.')->group(function () {

    // ========================================
    // SETUP & LOGIN (Anyone can access)
    // ========================================
    Route::get('/setup', [AdminController::class, 'showSetup'])->name('setup.show');
    Route::post('/setup', [AdminController::class, 'storeSetup'])->name('setup.store');
    Route::get('/login', function () {
        return redirect()->route('login');
    })->name('login');

    // ========================================
    // 2FA (After login, before dashboard)
    // ========================================
    Route::get('/auth/2fa', [TwoFactorController::class, 'show'])->name('auth.twofactor.show');
    Route::post('/auth/2fa/verify', [TwoFactorController::class, 'verify'])->name('auth.twofactor.verify');
    Route::post('/auth/2fa/resend', [TwoFactorController::class, 'resend'])->name('auth.twofactor.resend');

    // ========================================
    // PROTECTED ROUTES (Protection inside controller)
    // ========================================
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [AdminController::class, 'editProfile'])->name('profile.edit');
    Route::patch('/profile', [AdminController::class, 'updateProfile'])->name('profile.update');
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::patch('/users/{user}/toggle', [AdminController::class, 'toggleUserActive'])->name('users.toggle');
    Route::patch('/users/{user}/email', [AdminController::class, 'updateUserEmail'])->name('users.email.update');
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
    Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');

    // ========================================
    // BUSINESSES (TENANTS) MANAGEMENT
    // ========================================
    Route::get('/businesses', [AdminController::class, 'businesses'])->name('businesses.index');
    Route::patch('/businesses/{business}/toggle', [AdminController::class, 'toggleBusinessActive'])->name('businesses.toggle');
    Route::put('/businesses/{business}/subscription', [AdminController::class, 'updateBusinessSubscription'])->name('businesses.subscription.update');

    // ========================================
    // BUSINESS OPERATIONS MONITORING
    // ========================================
    Route::get('/businesses/{business}/monitor', [AdminController::class, 'monitorBusiness'])->name('businesses.monitor');
    Route::post('/businesses/{business}/reset-transactions', [AdminController::class, 'resetBusinessTransactions'])->name('businesses.reset_transactions');
    Route::post('/businesses/{business}/reset-settings', [AdminController::class, 'resetBusinessSettings'])->name('businesses.reset_settings');

    // ========================================
    // PACKAGES MANAGEMENT
    // ========================================
    Route::get('/packages', [AdminController::class, 'packagesIndex'])->name('packages.index');
    Route::post('/packages', [AdminController::class, 'packagesStore'])->name('packages.store');
    Route::put('/packages/{package}', [AdminController::class, 'packagesUpdate'])->name('packages.update');
    Route::delete('/packages/{package}', [AdminController::class, 'packagesDestroy'])->name('packages.destroy');
});