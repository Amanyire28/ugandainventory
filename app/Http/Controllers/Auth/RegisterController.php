<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

use App\Models\Package;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        $businessCategories = BusinessCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        $packages = Package::where('is_active', true)
            ->orderBy('price')
            ->get();

        return view('welcome', compact('businessCategories', 'packages'))->with('showRegisterModal', true);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'business_name'        => ['required', 'string', 'max:255'],
            'business_category_id' => ['required', 'exists:business_categories,id'],
            'subscription_plan'    => ['required', 'string', 'exists:packages,slug'],
            'business_email'       => ['required', 'string', 'email', 'max:255', 'unique:businesses,email'],
            'contact'              => ['required', 'string', 'max:20'],
            'personal_name'        => ['required', 'string', 'max:255'],
            'password'             => ['required', 'confirmed', Password::min(8)],
        ]);

        DB::beginTransaction();

        try {
            // Create business with selected plan, but unconfirmed subscription (null expiry)
            $business = Business::create([
                'name'                 => $validated['business_name'],
                'slug'                 => $this->generateUniqueSlug($validated['business_name']),
                'business_category_id' => $validated['business_category_id'],
                'email'                => $validated['business_email'],
                'phone'                => $validated['contact'],
                'is_active'            => true,
                'subscription_plan'    => $validated['subscription_plan'],
                'subscription_expires_at' => null, // Requires Admin confirmation upon payment
            ]);

            // Create default main location
            Location::create([
                'business_id' => $business->id,
                'name'        => 'Main Location',
                'is_main'     => true,
                'is_active'   => true,
            ]);

            // Get owner role
            $ownerRole = Role::where('name', 'owner')->first();

            // Create owner user
            $user = User::create([
                'business_id'       => $business->id,
                'role_id'           => $ownerRole->id,
                'name'              => $validated['personal_name'],
                'email'             => $validated['business_email'],
                'phone'             => $validated['contact'],
                'password'          => Hash::make($validated['password']),
                'is_owner'          => true,
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            DB::commit();

            // Log the user in
            Auth::login($user);

            return redirect()->route('subscription.index')
                ->with('success', 'Registration successful! Welcome to your account. Payment is optional right now—you can access your account, but operational features remain locked until your subscription payment is confirmed by the Admin.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()
                ->withInput()
                ->with('error', 'Registration failed. Please try again. Error: ' . $e->getMessage());
        }
    }

    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (Business::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}