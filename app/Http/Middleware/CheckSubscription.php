<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * Check if business subscription is active
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // System Admins always pass through
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }

        $business = $user->business;

        if (!$business) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Business not found.');
        }

        // Check if subscription is active and confirmed by admin
        if (!$business->isSubscriptionActive()) {
            $currentRoute = $request->route()?->getName();

            // Routes accessible even when subscription is unconfirmed / expired
            $allowedRoutes = [
                'dashboard',
                'dashboard.annual',
                'dashboard.annual.export',
                'subscription.index',
                'subscription.pay',
                'profile.edit',
                'profile.update',
                'profile.password.update',
                'profile.destroy',
                'owner.profile.edit',
                'owner.profile.update',
                'owner.profile.avatar',
                'owner.profile.update_email',
                'owner.profile.update_password',
                'owner.profile.update_photo',
                'owner.profile.delete_photo',
                'owner.profile.destroy',
                'logout',
            ];

            if (!in_array($currentRoute, $allowedRoutes)) {
                return redirect()->route('subscription.index')
                    ->with('error', '🔒 Features Locked: Operational features (POS, Invoicing, Inventory, Sales, Reports) require an active subscription confirmed by the Admin. Payment is optional at registration, but you must submit subscription payment below for Admin confirmation to unlock all features.');
            }
        }

        return $next($request);
    }
}