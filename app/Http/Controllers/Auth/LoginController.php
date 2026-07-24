<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        $businessCategories = \App\Models\BusinessCategory::all();
        return view('welcome', compact('businessCategories'))->with('showLoginModal', true);
    }

    /**
     * Handle login request for both administrators and regular users
     */
     public function login(Request $request)
     {
         // Validate the login credentials
         $credentials = $request->validate([
             'email'    => ['required', 'email'],
             'password' => ['required', 'string'],
         ]);
 
         // 1. Check if the user is a System Admin (in admins table)
         $admin = \App\Models\Admin::where('email', $request->email)->first();
         if ($admin) {
             // Block deactivated admins
             if (!$admin->is_active) {
                 throw ValidationException::withMessages([
                     'email' => ['This administrator account has been deactivated.'],
                 ]);
             }
 
             // Attempt authentication for the admin guard
             if (Auth::guard('admin')->attempt(
                 ['email' => $request->email, 'password' => $request->password],
                 $request->boolean('remember')
             )) {
                 $request->session()->regenerate();
                 
                 // Set verification status
                 session(['two_factor_verified' => true]);
 
                 try {
                     $admin->update(['last_login_at' => now()]);
                 } catch (\Throwable $e) {}
 
                 return redirect()->route('admin.dashboard')
                     ->with('success', "Welcome back, Admin {$admin->name}! 🛡️");
             }
         }
 
         // 2. Otherwise, attempt authentication as a regular tenant user (in users table)
         $user = \App\Models\User::where('email', $request->email)->first();
 
         // Block deactivated users
         if ($user && !$user->is_active) {
             throw ValidationException::withMessages([
                 'email' => ['Your account has been deactivated. Please contact the administrator.'],
             ]);
         }
 
         if (Auth::attempt(
             ['email' => $request->email, 'password' => $request->password],
             $request->boolean('remember')
         )) {
             // Regenerate session to prevent fixation
             $request->session()->regenerate();
 
             // Authenticated user
             $user = Auth::user();
 
             // Optional: update last login timestamp if column exists
             try {
                 $user->update(['last_login_at' => now()]);
             } catch (\Throwable $e) {
                 // Column may not exist; ignore
             }
 
             // Always bypass 2FA
             session(['two_factor_verified' => true]);
 
             // Proceed with role-based redirect
             return $this->redirectBasedOnRole($user)
                 ->with('success', "Welcome back, {$user->name}!");
         }
 
         // If authentication failed
         throw ValidationException::withMessages([
             'email' => ['The provided credentials do not match our records.'],
         ]);
     }

    /**
     * Redirect user based on their role
     */
    protected function redirectBasedOnRole($user)
    {
        $userRole = $user->role->name;

        switch ($userRole) {
            case 'cashier':
                return redirect()->route('cashier.dashboard')
                    ->with('success', "Welcome back, {$user->name}! 💰");

            case 'admin':
                return redirect()->route('dashboard')
                    ->with('success', "Welcome back, {$user->name}! 🛡️");

            case 'manager':
                return redirect()->route('dashboard')
                    ->with('success', "Welcome back, {$user->name}! 📊");

            case 'inventory':
                return redirect()->route('dashboard')
                    ->with('success', "Welcome back, {$user->name}! 📦");

            case 'accountant':
                return redirect()->route('dashboard')
                    ->with('success', "Welcome back, {$user->name}! 💼");

            case 'owner':
            default:
                return redirect()->route('dashboard')
                    ->with('success', "Welcome back, {$user->name}! 👑");
        }
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // Invalidate session and regenerate CSRF token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'You have been logged out successfully! 👋');
    }
}