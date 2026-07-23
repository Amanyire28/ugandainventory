<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Storage, Mail, Log};
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /**
     * Show business settings page
     */
    public function index()
    {
        $user = Auth::user();
        
        // Only admin/owner can access settings
        if (!in_array($user->role->name, ['admin', 'owner'])) {
            abort(403, 'Unauthorized. Only business owners can access settings.');
        }

        $business = $user->business;

        return view('settings.index', compact('business'));
    }

    /**
     * Update business information
     */
    public function updateInfo(Request $request)
    {
        $user = Auth::user();
        
        if (!in_array($user->role->name, ['admin', 'owner'])) {
            abort(403);
        }

        $business = $user->business;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'max:10'],
            'website' => ['nullable', 'url', 'max:255'],
        ], [
            'name.required' => 'Business name is required',
            'email.required' => 'Business email is required',
            'email.email' => 'Please enter a valid email address',
            'phone.required' => 'Phone number is required',
            'website.url' => 'Please enter a valid website URL',
        ]);

        $business->update($validated);

        Log::info('Business information updated', [
            'business_id' => $business->id,
            'updated_by' => $user->id,
        ]);

        return redirect()->route('settings.index')
            ->with('success', 'Business information updated successfully!');
    }

    /**
     * Upload/Update business logo
     */
    public function updateLogo(Request $request)
    {
        $user = Auth::user();
        
        if (!in_array($user->role->name, ['admin', 'owner'])) {
            abort(403);
        }

        $business = $user->business;

        $validated = $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ], [
            'logo.required' => 'Please select a logo image',
            'logo.image' => 'File must be an image',
            'logo.mimes' => 'Logo must be: jpeg, png, jpg, gif, or svg',
            'logo.max' => 'Logo size must not exceed 2MB',
        ]);

        try {
            // Delete old logo if exists
            if ($business->logo) {
                Storage::disk('public')->delete($business->logo);
            }

            // Store new logo
            $logoPath = $request->file('logo')->store('logos', 'public');

            $business->update(['logo' => $logoPath]);

            Log::info('Business logo updated', [
                'business_id' => $business->id,
                'logo_path' => $logoPath,
            ]);

            return redirect()->route('settings.index')
                ->with('success', 'Business logo updated successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to upload logo', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('settings.index')
                ->with('error', 'Failed to upload logo. Please try again.');
        }
    }

    /**
     * Remove business logo
     */
    public function removeLogo()
    {
        $user = Auth::user();
        
        if (!in_array($user->role->name, ['admin', 'owner'])) {
            abort(403);
        }

        $business = $user->business;

        if ($business->logo) {
            Storage::disk('public')->delete($business->logo);
            $business->update(['logo' => null]);

            Log::info('Business logo removed', ['business_id' => $business->id]);

            return redirect()->route('settings.index')
                ->with('success', 'Business logo removed successfully!');
        }

        return redirect()->route('settings.index')
            ->with('info', 'No logo to remove.');
    }

    /**
     * Update email (SMTP) settings
     */
    public function updateEmail(Request $request)
    {
        $user = Auth::user();
        
        if (!in_array($user->role->name, ['admin', 'owner'])) {
            abort(403);
        }

        $business = $user->business;

        $validated = $request->validate([
            'smtp_email' => ['required', 'email', 'max:255'],
            'smtp_password' => ['required', 'string', 'min:16', 'max:255'],
        ], [
            'smtp_email.required' => 'Gmail address is required',
            'smtp_email.email' => 'Please enter a valid email address',
            'smtp_password.required' => 'Gmail App Password is required',
            'smtp_password.min' => 'App Password must be at least 16 characters',
        ]);

        // Remove spaces from password (in case user copied with spaces)
        $validated['smtp_password'] = str_replace(' ', '', $validated['smtp_password']);

        $business->update([
            'smtp_email' => $validated['smtp_email'],
            'smtp_password' => $validated['smtp_password'],
            'email_configured' => true,
        ]);

        Log::info('Business email settings updated', [
            'business_id' => $business->id,
            'smtp_email' => $validated['smtp_email'],
        ]);

        return redirect()->route('settings.index')
            ->with('success', 'Email settings saved successfully! You can now send receipts from your own email.');
    }

    /**
     * Test email configuration
     */
    public function testEmail(Request $request)
    {
        $user = Auth::user();
        
        if (!in_array($user->role->name, ['admin', 'owner'])) {
            abort(403);
        }

        $validated = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $business = $user->business;

        if (!$business->hasEmailConfigured()) {
            return redirect()->route('settings.index')
                ->with('error', 'Please configure email settings first.');
        }

        try {
            // Temporarily set business email credentials
            config([
                'mail.mailers.smtp.username' => $business->smtp_email,
                'mail.mailers.smtp.password' => $business->getDecryptedSmtpPassword(),
            ]);


            // Send test email
            Mail::raw(
                "🎉 Success! Your email is configured correctly.\n\n" .
                "Business: {$business->name}\n" .
                "From Email: {$business->smtp_email}\n\n" .
                "You can now send receipts to your customers from your own email address.\n\n" .
                "Test sent at: " . now()->format('d M Y, h:i A'),
                function($message) use ($validated, $business) {
                    $message->to($validated['test_email'])
                            ->subject('✅ Test Email - ' . $business->name)
                            ->from($business->smtp_email, $business->name);
                }
            );

            Log::info('Test email sent successfully', [
                'business_id' => $business->id,
                'test_email' => $validated['test_email'],
            ]);

            return redirect()->route('settings.index')
                ->with('success', '✅ Test email sent successfully to ' . $validated['test_email'] . '! Check your inbox.');

        } catch (\Exception $e) {
            Log::error('Test email failed', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('settings.index')
                ->with('error', '❌ Failed to send test email: ' . $e->getMessage() . '. Please check your Gmail App Password.');
        }
    }

    /**
     * Remove email configuration
     */
    public function removeEmail()
    {
        $user = Auth::user();
        
        if (!in_array($user->role->name, ['admin', 'owner'])) {
            abort(403);
        }

        $business = $user->business;
        
        $business->update([
            'smtp_email' => null,
            'smtp_password' => null,
            'email_configured' => false,
        ]);

        Log::info('Business email configuration removed', ['business_id' => $business->id]);

        return redirect()->route('settings.index')
            ->with('success', 'Email configuration removed. System will use default email.');
    }

    /**
     * Update tax settings
     */
    public function updateTax(Request $request)
    {
        $user = Auth::user();
        
        if (!in_array($user->role->name, ['admin', 'owner'])) {
            abort(403);
        }

        $business = $user->business;

        $validated = $request->validate([
            'tax_enabled' => ['nullable', 'boolean'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:50'],
        ]);

        $business->update($validated);

        Log::info('Tax settings updated', [
            'business_id' => $business->id,
            'tax_enabled' => $validated['tax_enabled'] ?? false,
        ]);

        return redirect()->route('settings.index')
            ->with('success', 'Tax settings updated successfully!');
    }

    /**
     * Toggle business active status
     */
    public function toggleStatus()
    {
        $user = Auth::user();
        
        if (!in_array($user->role->name, ['admin', 'owner'])) {
            abort(403);
        }

        $business = $user->business;
        $business->is_active = !$business->is_active;
        $business->save();

        $status = $business->is_active ? 'activated' : 'deactivated';

        Log::warning('Business status changed', [
            'business_id' => $business->id,
            'new_status' => $business->is_active,
        ]);

        return redirect()->route('settings.index')
            ->with('success', "Business {$status} successfully!");
    }
}