<?php

namespace App\Http\Controllers;

use App\Models\BusinessSubscription;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SubscriptionController extends Controller
{
    /**
     * Show the business subscription page with current plan and payment history.
     */
    public function index()
    {
        $user     = Auth::user();
        $business = $user->business;

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'No business found for your account.');
        }

        $packages = Package::where('is_active', true)->orderBy('price')->get();

        $history = BusinessSubscription::where('business_id', $business->id)
            ->latest()
            ->paginate(10);

        return view('subscription.index', compact('business', 'packages', 'history'));
    }

    /**
     * Submit a payment request from the business side.
     */
    public function submit(Request $request)
    {
        $user     = Auth::user();
        $business = $user->business;

        if (!$business) {
            return back()->with('error', 'No business found for your account.');
        }

        $data = $request->validate([
            'package_slug'    => 'required|string|exists:packages,slug',
            'duration_months' => 'nullable|integer|min:1|max:12',
            'amount'          => 'required|numeric|min:0',
            'payment_method'  => 'required|string',
            'reference'       => 'nullable|string|max:255',
            'notes'           => 'nullable|string|max:1000',
            'proof_image'     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // max 5MB
        ]);

        $months = (int) ($data['duration_months'] ?? 1);

        // Compute start & end dates automatically based on current subscription status & duration
        $startDate = ($business->subscription_expires_at && $business->subscription_expires_at->isFuture()) 
            ? $business->subscription_expires_at->copy() 
            : now();

        $periodStart = $startDate->toDateString();
        $periodEnd   = $startDate->copy()->addMonths($months)->toDateString();

        // Handle proof image upload
        $proofPath = null;
        if ($request->hasFile('proof_image') && $request->file('proof_image')->isValid()) {
            $proofPath = $request->file('proof_image')->store('payment_proofs', 'public');
        }

        BusinessSubscription::create([
            'business_id'          => $business->id,
            'package_slug'         => $data['package_slug'],
            'amount'               => $data['amount'],
            'currency'             => $business->currency ?? 'UGX',
            'status'               => 'pending',
            'payment_method'       => $data['payment_method'],
            'reference'            => $data['reference'] ?? null,
            'notes'                => $data['notes'] ?? null,
            'proof_image'          => $proofPath,
            'submitted_by'         => 'business',
            'submitted_by_user_id' => $user->id,
            'period_start'         => $periodStart,
            'period_end'           => $periodEnd,
        ]);

        return back()->with('success', 'Payment submitted successfully! The admin will review and approve your subscription within 24 hours.');
    }
}
