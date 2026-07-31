<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'business_id',
        'location_id',
        'role_id',
        'name',
        'email',
        'phone',
        'password',
        'is_owner',
        'is_active',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'profile_image',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_owner' => 'boolean',
        'is_active' => 'boolean',
        'password' => 'hashed',
        'profile_image_updated_at' => 'datetime',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    // ========================================
    // PERMISSION METHODS
    // ========================================

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->role->hasPermission($permission);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    // ========================================
    // ROLE METHODS
    // ========================================

    /**
     * Check if user is business owner
     */
    public function isOwner(): bool
    {
        return $this->is_owner;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role->name === $roleName;
    }

    /**
     * Check if user has any of given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role->name, $roles);
    }

    /**
     * Check if user is a system admin
     */
    public function isAdmin(): bool
    {
        if (isset($this->is_admin) && $this->is_admin) {
            return true;
        }

        return $this->role && strtolower($this->role->name) === 'admin';
    }

    /**
     * Get role display name
     */
    public function getRoleName(): string
    {
        return $this->role ? $this->role->display_name : 'User';
    }

    // ========================================
    // ACTIVITY METHODS
    // ========================================

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => Carbon::now()]);
    }

    /**
     * Check if user logged in today
     */
    public function loggedInToday(): bool
    {
        return $this->last_login_at && $this->last_login_at->isToday();
    }

    /**
     * Get days since last login
     */
    public function daysSinceLastLogin(): ?int
    {
        if (!$this->last_login_at) {
            return null;
        }

        return Carbon::now()->diffInDays($this->last_login_at);
    }

    /**
     * Check if user is inactive (not logged in for X days)
     */
    public function isInactive(int $days = 30): bool
    {
        if (!$this->last_login_at) {
            return true;
        }

        return $this->daysSinceLastLogin() > $days;
    }

    // ========================================
    // STATISTICS METHODS
    // ========================================

    /**
     * Get total sales made by this user
     */
    public function getTotalSales(): float
    {
        return $this->sales()->sum('total');
    }

    /**
     * Get sales count for period
     */
    public function getSalesCount($startDate = null, $endDate = null): int
    {
        $query = $this->sales();

        if ($startDate) {
            $query->whereDate('sale_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('sale_date', '<=', $endDate);
        }

        return $query->count();
    }

    /**
     * Get sales total for period
     */
    public function getSalesTotal($startDate = null, $endDate = null): float
    {
        $query = $this->sales();

        if ($startDate) {
            $query->whereDate('sale_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('sale_date', '<=', $endDate);
        }

        return $query->sum('total');
    }

    /**
     * Get today's sales
     */
    public function getTodaySales(): float
    {
        return $this->sales()
            ->whereDate('sale_date', Carbon::today())
            ->sum('total');
    }

    /**
     * Get this month's sales
     */
    public function getThisMonthSales(): float
    {
        return $this->sales()
            ->whereMonth('sale_date', Carbon::now()->month)
            ->whereYear('sale_date', Carbon::now()->year)
            ->sum('total');
    }

    /**
     * Get total purchases made by this user
     */
    public function getTotalPurchases(): float
    {
        return $this->purchases()->sum('total');
    }

    // ========================================
    // ATTRIBUTE ACCESSORS
    // ========================================

    /**
     * Get user's initials
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';

        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }

        return $initials;
    }

    /**
     * Get user's first name
     */
    public function getFirstNameAttribute(): string
    {
        $parts = explode(' ', $this->name);
        return $parts[0] ?? '';
    }

    /**
     * Get user's last name
     */
    public function getLastNameAttribute(): string
    {
        $parts = explode(' ', $this->name);
        return $parts[count($parts) - 1] ?? '';
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        if (!$this->is_active || ($this->business && !$this->business->is_active)) {
            return 'red';
        }

        if ($this->loggedInToday()) {
            return 'green';
        }

        if ($this->isInactive(7)) {
            return 'yellow';
        }

        return 'blue';
    }

    /**
     * Get status text
     */
    public function getStatusTextAttribute(): string
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        if ($this->business && !$this->business->is_active) {
            return 'Business Suspended';
        }

        if ($this->loggedInToday()) {
            return 'Active Today';
        }

        if (!$this->last_login_at) {
            return 'Never Logged In';
        }

        return 'Last login ' . $this->last_login_at->diffForHumans();
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope: Active users only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereDoesntHave('business')
                  ->orWhereHas('business', function ($b) {
                      $b->where('is_active', true);
                  });
            });
    }

    /**
     * Scope: Inactive users only
     */
    public function scopeInactive($query)
    {
        return $query->where(function ($q) {
            $q->where('is_active', false)
              ->orWhereHas('business', function ($b) {
                  $b->where('is_active', false);
              });
        });
    }

    /**
     * Scope: Owners only
     */
    public function scopeOwners($query)
    {
        return $query->where('is_owner', true);
    }

    /**
     * Scope: Staff only (non-owners)
     */
    public function scopeStaff($query)
    {
        return $query->where('is_owner', false);
    }

    /**
     * Scope: By role
     */
    public function scopeByRole($query, string $roleName)
    {
        return $query->whereHas('role', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    /**
     * Scope: Logged in within days
     */
    public function scopeLoggedInWithin($query, int $days)
    {
        return $query->where('last_login_at', '>=', Carbon::now()->subDays($days));
    }

    // ========================================
    // STATIC METHODS
    // ========================================

    /**
     * Get users by performance (sales)
     */
    public static function getTopPerformers($businessId, $limit = 5, $period = 'month')
    {
        $startDate = match($period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        return static::where('users.business_id', $businessId)
            ->where('users.is_owner', false)
            ->leftJoin('sales', 'users.id', '=', 'sales.user_id')
            ->where(function($q) use ($startDate) {
                $q->where('sales.sale_date', '>=', $startDate)
                  ->orWhereNull('sales.id');
            })
            ->groupBy('users.id', 'users.name', 'users.email')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                \DB::raw('COALESCE(SUM(sales.total), 0) as total_sales'),
                \DB::raw('COUNT(sales.id) as sales_count')
            )
            ->orderByDesc('total_sales')
            ->take($limit)
            ->get();
    }
}