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
            'package_slug'   => 'required|string|exists:packages,slug',
            'amount'         => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'reference'      => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:1000',
            'period_start'   => 'nullable|date',
            'period_end'     => 'nullable|date',
            'proof_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // max 5MB
        ]);

        // Handle proof image upload
        $proofPath = null;
        if ($request->hasFile('proof_image') && $request->file('proof_image')->isValid()) {
            $proofPath = $request->file('proof_image')->store('payment_proofs', 'public');
        }

        BusinessSubscription::create([
            'business_id'        => $business->id,
            'package_slug'       => $data['package_slug'],
            'amount'             => $data['amount'],
            'currency'           => $business->currency ?? 'UGX',
            'status'             => 'pending',
            'payment_method'     => $data['payment_method'],
            'reference'          => $data['reference'] ?? null,
            'notes'              => $data['notes'] ?? null,
            'proof_image'        => $proofPath,
            'submitted_by'       => 'business',
            'submitted_by_user_id' => $user->id,
            'period_start'       => $data['period_start'] ?? now()->toDateString(),
            'period_end'         => $data['period_end'] ?? now()->addMonth()->toDateString(),
        ]);

        return back()->with('success', 'Payment submitted successfully! The admin will review and approve your payment within 24 hours.');
    }
}
