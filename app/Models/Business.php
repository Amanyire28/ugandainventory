<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\{Storage, Crypt};

class Business extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'business_category_id',
        'email',
        'phone',
        'address',
        'tax_number',
        'logo',
        'is_active',
        'subscription_plan',
        'subscription_expires_at',
        'owner_id',
        // ✅ Email/SMTP Settings
        'smtp_email',
        'smtp_password',
        'email_configured',
        // ✅ Additional Business Info
        'website',
        'currency',
        // ✅ Tax Settings
        'tax_enabled',
        'tax_rate',
    ];

    protected $hidden = [
        'smtp_password', // ✅ Hide SMTP password in JSON responses
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subscription_expires_at' => 'datetime',
        'email_configured' => 'boolean',
        'tax_enabled' => 'boolean',
        'tax_rate' => 'decimal: 2',
    ];

    // =====================================
    // ✅ NEW:  SMTP Password Encryption/Decryption
    // =====================================

    /**
     * Encrypt SMTP password before saving to database
     */
    public function setSmtpPasswordAttribute($value)
    {
        if ($value === null) {
            $this->attributes['smtp_password'] = null;
            return;
        }

        // Remove spaces (Gmail App Passwords sometimes have spaces) and encrypt
        $this->attributes['smtp_password'] = Crypt::encryptString(str_replace(' ', '', $value));
    }

    /**
     * Decrypt SMTP password when reading from database
     */
    public function getSmtpPasswordAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt:: decryptString($value);
        } catch (\Throwable $e) {
            // If decryption fails (old plaintext password), return raw value
            return $value;
        }
    }

    /**
     * Get decrypted SMTP password (explicit method for MailerService)
     */
    public function getDecryptedSmtpPassword(): ? string
    {
        $raw = $this->getAttributes()['smtp_password'] ?? null;
        if (empty($raw)) {
            return null;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            // If decryption fails, return raw value (backward compatibility)
            return $raw;
        }
    }

    // =====================================
    // ✅ Email Configuration Methods
    // =====================================

    /**
     * Check if business has configured their own email
     */
    public function hasEmailConfigured(): bool
    {
        return $this->email_configured && 
               ! empty($this->smtp_email) && 
               !empty($this->getDecryptedSmtpPassword());
    }

    /**
     * Get email to use for sending (business email or system default)
     */
    public function getSenderEmail(): string
    {
        return $this->hasEmailConfigured() 
            ? $this->smtp_email 
            : config('mail.from.address');
    }

    // =====================================
    // ✅ Logo Management
    // =====================================

    /**
     * Get full logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        // If logo is full URL (starts with http)
        if (str_starts_with($this->logo, 'http')) {
            return $this->logo;
        }

        // If logo is storage path
        return Storage::url($this->logo);
    }

    /**
     * Check if business has logo
     */
    public function hasLogo(): bool
    {
        return ! empty($this->logo);
    }

    /**
     * Get logo initials (first letter of business name)
     */
    public function getLogoInitialsAttribute(): string
    {
        return strtoupper(substr($this->name, 0, 1));
    }

    // =====================================
    // ✅ Relationships
    // =====================================

    /**
     * Get the owner (User) of this business
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the business category
     */
    public function businessCategory(): BelongsTo
    {
        return $this->belongsTo(BusinessCategory::class);
    }

    /**
     * Get all users belonging to this business
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all products for this business
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all customers for this business
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get all suppliers for this business
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier:: class);
    }

    /**
     * Get all locations for this business
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get all sales for this business
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get all purchases for this business
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Get all categories for this business
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category:: class);
    }

    /**
     * Get the subscription package associated with the business
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'subscription_plan', 'slug');
    }

    /**
     * Check if the business has a specific feature enabled based on its package
     */
    public function hasFeature(string $feature): bool
    {
        // If subscription is expired, block all premium features
        if ($this->subscription_expires_at && $this->subscription_expires_at->isPast()) {
            return false;
        }

        // Fetch package from database using the subscription plan key
        $package = Package::where('slug', $this->subscription_plan)->first();

        if (!$package) {
            // Default fallback trial features if no package exists yet
            $trialFeatures = ['pos', 'products', 'customers'];
            return in_array(strtolower($feature), $trialFeatures);
        }

        return in_array(strtolower($feature), array_map('strtolower', $package->features ?? []));
    }

    // =====================================
    // ✅ Business Status Methods
    // =====================================

    /**
     * Check if business is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if subscription is still active
     */
    public function isSubscriptionActive(): bool
    {
        if (!$this->subscription_expires_at) {
            return true;
        }

        return $this->subscription_expires_at->isFuture();
    }

    /**
     * Get days until subscription expires
     */
    public function daysUntilExpiry(): ?int
    {
        if (! $this->subscription_expires_at) {
            return null;
        }

        return now()->diffInDays($this->subscription_expires_at, false);
    }

    // =====================================
    // ✅ Tax Methods
    // =====================================

    /**
     * Check if tax is enabled for this business
     */
    public function isTaxEnabled(): bool
    {
        return $this->tax_enabled;
    }

    /**
     * Calculate tax amount for given subtotal
     */
    public function calculateTax(float $subtotal): float
    {
        if (!$this->tax_enabled) {
            return 0;
        }

        return round($subtotal * ($this->tax_rate / 100), 2);
    }

    /**
     * Get formatted tax rate
     */
    public function getFormattedTaxRateAttribute(): string
    {
        return number_format($this->tax_rate, 2) . '%';
    }

    // =====================================
    // ✅ Currency Formatting
    // =====================================

    /**
     * Format amount with business currency
     */
    public function formatCurrency(float $amount): string
    {
        $currency = $this->currency ??  'UGX';
        
        return $currency . ' ' . number_format($amount, 0);
    }

    // =====================================
    // ✅ Business Statistics
    // =====================================

    /**
     * Get total sales count
     */
    public function getTotalSalesAttribute(): int
    {
        return $this->sales()->count();
    }

    /**
     * Get total revenue
     */
    public function getTotalRevenueAttribute(): float
    {
        return $this->sales()->sum('total');
    }

    /**
     * Get total customers
     */
    public function getTotalCustomersAttribute(): int
    {
        return $this->customers()->count();
    }

    /**
     * Get total products
     */
    public function getTotalProductsAttribute(): int
    {
        return $this->products()->count();
    }

    // =====================================
    // ✅ Subscription Helpers
    // =====================================

    /**
     * Check if subscription is expiring soon (within 7 days)
     */
    public function isSubscriptionExpiringSoon(): bool
    {
        $daysLeft = $this->daysUntilExpiry();
        
        return $daysLeft !== null && $daysLeft <= 7 && $daysLeft > 0;
    }

    /**
     * Get subscription status badge color
     */
    public function getSubscriptionStatusColorAttribute(): string
    {
        if (! $this->subscription_expires_at) {
            return 'green'; // Lifetime
        }

        $daysLeft = $this->daysUntilExpiry();

        if ($daysLeft === null || $daysLeft > 30) {
            return 'green'; // Active
        } elseif ($daysLeft > 7) {
            return 'yellow'; // Expiring
        } elseif ($daysLeft > 0) {
            return 'orange'; // Expiring soon
        } else {
            return 'red'; // Expired
        }
    }

    /**
     * Get subscription status text
     */
    public function getSubscriptionStatusTextAttribute(): string
    {
        if (!$this->subscription_expires_at) {
            return 'Lifetime Access';
        }

        if ($this->isSubscriptionActive()) {
            $daysLeft = $this->daysUntilExpiry();
            
            if ($daysLeft > 30) {
                return 'Active';
            } elseif ($daysLeft > 7) {
                return "Expires in {$daysLeft} days";
            } else {
                return "⚠️ Expires in {$daysLeft} days";
            }
        }

        return 'Expired';
    }
}