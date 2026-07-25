<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'category_id',
        'sku',
        'name',
        'description',
        'unit',
        'cost_price',
        'selling_price',
        'requires_vat',
        'reorder_level',
        'quantity',              // ✅ ADDED
        'opening_stock',         // ✅ ADDED - Initial stock value
        'barcode',
        'image',
        'manufacture_date',
        'expiry_date',
        'track_expiry',
        'expiry_alert_days',
        'has_variants',
        'is_active',
    ];

    protected $casts = [
        'cost_price'    => 'decimal:2',
        'selling_price' => 'decimal:2',
        'quantity'      => 'decimal:2',    // ✅ ADDED
        'opening_stock' => 'decimal:2',  // ✅ ADDED
        'requires_vat'  => 'boolean',
        'has_variants'  => 'boolean',
        'is_active'     => 'boolean',
        'track_expiry'   => 'boolean',
        'manufacture_date' => 'date',
        'expiry_date'   => 'date',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // ========================================
    // EXPIRY METHODS
    // ========================================

    /**
     * Check if product is expired
     */
    public function isExpired(): bool
    {
        if (!$this->track_expiry || !$this->expiry_date) {
            return false;
        }

        return Carbon::today()->greaterThan($this->expiry_date);
    }

    /**
     * Check if product is expiring soon
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->track_expiry || !$this->expiry_date) {
            return false;
        }

        $alertDate = Carbon::today()->addDays($this->expiry_alert_days ?? 30);
        
        return Carbon::today()->lessThanOrEqualTo($this->expiry_date) 
               && $this->expiry_date->lessThanOrEqualTo($alertDate);
    }

    /**
     * Get days until expiry
     */
    public function daysUntilExpiry(): ?int
    {
        if (!$this->track_expiry || !$this->expiry_date) {
            return null;
        }

        return Carbon::today()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get expiry status (expired, expiring_soon, fresh, no_tracking)
     */
    public function getExpiryStatus(): string
    {
        if (!$this->track_expiry) {
            return 'no_tracking';
        }

        if (!$this->expiry_date) {
            return 'no_date';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isExpiringSoon()) {
            return 'expiring_soon';
        }

        return 'fresh';
    }

    /**
     * Get expiry status color for UI
     */
    public function getExpiryStatusColor(): string
    {
        return match($this->getExpiryStatus()) {
            'expired' => 'red',
            'expiring_soon' => 'yellow',
            'fresh' => 'green',
            'no_tracking' => 'gray',
            'no_date' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get expiry status text
     */
    public function getExpiryStatusText(): string
    {
        return match($this->getExpiryStatus()) {
            'expired' => 'Expired',
            'expiring_soon' => 'Expiring Soon',
            'fresh' => 'Fresh',
            'no_tracking' => 'Not Tracked',
            'no_date' => 'No Date Set',
            default => 'Unknown',
        };
    }

    // ========================================
    // INVENTORY METHODS (✅ UPDATED TO USE quantity COLUMN)
    // ========================================

    /**
     * Get total stock (from quantity column OR inventory table)
     */
    public function getTotalStock(): float
    {
        // ✅ Use quantity column if exists, fallback to inventory table
        if (isset($this->quantity)) {
            return (float) $this->quantity;
        }
        
        return $this->inventory()->sum('quantity');
    }

    /**
     * Check if product is low stock
     */
    public function isLowStock(): bool
    {
        return $this->getTotalStock() <= $this->reorder_level;
    }

    /**
     * Check if product is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->getTotalStock() <= 0;
    }

    /**
     * Get stock status
     */
    public function getStockStatus(): string
    {
        if ($this->isOutOfStock()) {
            return 'out_of_stock';
        }

        if ($this->isLowStock()) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Get stock status color
     */
    public function getStockStatusColor(): string
    {
        return match($this->getStockStatus()) {
            'out_of_stock' => 'red',
            'low_stock' => 'yellow',
            'in_stock' => 'green',
            default => 'gray',
        };
    }

    /**
     * Get stock status text
     */
    public function getStockStatusText(): string
    {
        return match($this->getStockStatus()) {
            'out_of_stock' => 'Out of Stock',
            'low_stock' => 'Low Stock',
            'in_stock' => 'In Stock',
            default => 'Unknown',
        };
    }

    // ========================================
    // PRICING METHODS
    // ========================================

    /**
     * Calculate profit margin
     */
    public function getProfitMargin(): float
    {
        if ($this->cost_price <= 0) {
            return 0;
        }

        return (($this->selling_price - $this->cost_price) / $this->cost_price) * 100;
    }

    /**
     * Calculate profit per unit
     */
    public function getProfitPerUnit(): float
    {
        return $this->selling_price - $this->cost_price;
    }

    /**
     * Get total inventory value (cost)
     */
    public function getTotalInventoryValue(): float
    {
        return $this->getTotalStock() * $this->cost_price;
    }

    /**
     * Get potential revenue (if all sold)
     */
    public function getPotentialRevenue(): float
    {
        return $this->getTotalStock() * $this->selling_price;
    }

    // ========================================
    // SALES STATISTICS
    // ========================================

    /**
     * Get total units sold
     */
    public function getTotalUnitsSold($startDate = null, $endDate = null): float
    {
        $query = $this->saleItems();

        if ($startDate) {
            $query->whereHas('sale', function($q) use ($startDate) {
                $q->where('sale_date', '>=', $startDate);
            });
        }

        if ($endDate) {
            $query->whereHas('sale', function($q) use ($endDate) {
                $q->where('sale_date', '<=', $endDate);
            });
        }

        return $query->sum('quantity');
    }

    /**
     * Get total sales revenue
     */
    public function getTotalSalesRevenue($startDate = null, $endDate = null): float
    {
        $query = $this->saleItems();

        if ($startDate) {
            $query->whereHas('sale', function($q) use ($startDate) {
                $q->where('sale_date', '>=', $startDate);
            });
        }

        if ($endDate) {
            $query->whereHas('sale', function($q) use ($endDate) {
                $q->where('sale_date', '<=', $endDate);
            });
        }

        return $query->sum('total');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope: Active products only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Inactive products
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: Get expired products
     */
    public function scopeExpired($query)
    {
        return $query->where('track_expiry', true)
                     ->whereNotNull('expiry_date')
                     ->where('expiry_date', '<', Carbon::today());
    }

    /**
     * Scope: Get products expiring soon
     */
    public function scopeExpiringSoon($query, $days = null)
    {
        $days = $days ?? 30;
        $alertDate = Carbon::today()->addDays($days);

        return $query->where('track_expiry', true)
                     ->whereNotNull('expiry_date')
                     ->where('expiry_date', '>=', Carbon::today())
                     ->where('expiry_date', '<=', $alertDate);
    }

    /**
     * Scope: Get fresh products (not expired, not expiring soon)
     */
    public function scopeFresh($query, $days = null)
    {
        $days = $days ?? 30;
        $alertDate = Carbon::today()->addDays($days);

        return $query->where('track_expiry', true)
                     ->whereNotNull('expiry_date')
                     ->where('expiry_date', '>', $alertDate);
    }

    /**
     * Scope: Low stock products (✅ UPDATED TO USE quantity COLUMN)
     */
    public function scopeLowStock($query)
    {
        return $query->where('quantity', '<=', DB::raw('reorder_level'));
    }

    /**
     * Scope: Out of stock products (✅ UPDATED TO USE quantity COLUMN)
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    /**
     * Scope: In stock products (✅ UPDATED TO USE quantity COLUMN)
     */
    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Search by name or SKU
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%");
        });
    }

    // ========================================
    // ATTRIBUTE ACCESSORS
    // ========================================

    /**
     * Get formatted cost price
     */
    public function getFormattedCostPriceAttribute(): string
    {
        return 'UGX ' . number_format($this->cost_price, 0);
    }

    /**
     * Get formatted selling price
     */
    public function getFormattedSellingPriceAttribute(): string
    {
        return 'UGX ' . number_format($this->selling_price, 0);
    }

    /**
     * Get image URL
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }

        return asset('images/no-product-image.png');
    }

    /**
     * Get display name with SKU
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name . ' (' . $this->sku . ')';
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Check if product can be sold
     */
    public function canBeSold(): bool
    {
        return $this->is_active 
               && !$this->isExpired() 
               && !$this->isOutOfStock();
    }

    /**
     * Get product details for POS
     */
    public function getPOSDetails(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'selling_price' => $this->selling_price,
            'stock' => $this->getTotalStock(),
            'unit' => $this->unit,
            'can_sell' => $this->canBeSold(),
            'image_url' => $this->image_url,
            'expiry_status' => $this->getExpiryStatus(),
        ];
    }

    /**
     * Get product summary
     */
    public function getSummary(): array
    {
        return [
            'basic' => [
                'id' => $this->id,
                'sku' => $this->sku,
                'name' => $this->name,
                'category' => $this->category->name ?? 'Uncategorized',
                'unit' => $this->unit,
            ],
            'pricing' => [
                'cost_price' => $this->cost_price,
                'selling_price' => $this->selling_price,
                'profit_margin' => round($this->getProfitMargin(), 2),
                'profit_per_unit' => $this->getProfitPerUnit(),
            ],
            'inventory' => [
                'total_stock' => $this->getTotalStock(),
                'reorder_level' => $this->reorder_level,
                'stock_status' => $this->getStockStatus(),
                'inventory_value' => $this->getTotalInventoryValue(),
            ],
            'expiry' => [
                'track_expiry' => $this->track_expiry,
                'expiry_date' => $this->expiry_date?->format('Y-m-d'),
                'days_until_expiry' => $this->daysUntilExpiry(),
                'expiry_status' => $this->getExpiryStatus(),
            ],
            'status' => [
                'is_active' => $this->is_active,
                'can_be_sold' => $this->canBeSold(),
            ],
        ];
    }

    // ========================================
    // ✅ NEW: QUANTITY MANAGEMENT METHODS
    // ========================================

    /**
     * Add stock quantity
     */
    public function addStock(float $quantity): void
    {
        $this->increment('quantity', $quantity);
    }

    /**
     * Remove stock quantity
     */
    public function removeStock(float $quantity): void
    {
        $this->decrement('quantity', $quantity);
    }

    /**
     * Set stock quantity
     */
    public function setStock(float $quantity): void
    {
        $this->update(['quantity' => $quantity]);
    }
}