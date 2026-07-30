<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Inventory extends Model
{
    // No created_at, only updated_at
    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';

    protected $table = 'inventory';

    protected $fillable = [
        'business_id',
        'product_id',
        'location_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'updated_at' => 'datetime',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Add stock to inventory
     */
    public function addStock(float $quantity): self
    {
        $this->quantity += $quantity;
        $this->save();

        return $this;
    }

    /**
     * Remove stock from inventory
     */
    public function removeStock(float $quantity): self
    {
        if ($this->quantity < $quantity) {
            throw new \Exception("Insufficient stock. Available: {$this->quantity}, Requested: {$quantity}");
        }

        $this->quantity -= $quantity;
        $this->save();

        return $this;
    }

    /**
     * Set stock quantity
     */
    public function setStock(float $quantity): self
    {
        $this->quantity = $quantity;
        $this->save();

        return $this;
    }

    /**
     * Check if stock is low
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->product->reorder_level;
    }

    /**
     * Check if out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
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
     * Get stock value (quantity * cost price)
     */
    public function getStockValue(): float
    {
        return $this->quantity * $this->product->cost_price;
    }

    /**
     * Get potential revenue (quantity * selling price)
     */
    public function getPotentialRevenue(): float
    {
        return $this->quantity * $this->product->selling_price;
    }

    /**
     * Get last updated human readable
     */
    public function getLastUpdatedAttribute(): string
    {
        return $this->updated_at ? $this->updated_at->diffForHumans() : 'Never';
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope: Low stock items
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('inventory.quantity', '<=', 'products.reorder_level')
                     ->join('products', 'inventory.product_id', '=', 'products.id');
    }

    /**
     * Scope: Out of stock items
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('inventory.quantity', '<=', 0);
    }

    /**
     * Scope: In stock items
     */
    public function scopeInStock($query)
    {
        return $query->where('inventory.quantity', '>', 0);
    }

    /**
     * Scope: By location
     */
    public function scopeByLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    // ========================================
    // STATIC METHODS
    // ========================================

    /**
     * Get or create inventory record
     */
    public static function getOrCreate($businessId, $productId, $locationId)
    {
        return static::firstOrCreate(
            [
                'business_id' => $businessId,
                'product_id' => $productId,
                'location_id' => $locationId,
            ],
            [
                'quantity' => 0,
            ]
        );
    }

    /**
     * Transfer stock between locations
     */
    public static function transferStock($businessId, $productId, $fromLocationId, $toLocationId, $quantity)
    {
        if ($fromLocationId == $toLocationId) {
            throw new \Exception("Cannot transfer to the same location");
        }

        // Get inventory records
        $fromInventory = static::getOrCreate($businessId, $productId, $fromLocationId);
        $toInventory = static::getOrCreate($businessId, $productId, $toLocationId);

        // Check if enough stock in source location
        if ($fromInventory->quantity < $quantity) {
            throw new \Exception("Insufficient stock in source location. Available: {$fromInventory->quantity}");
        }

        // Perform transfer
        $fromInventory->removeStock($quantity);
        $toInventory->addStock($quantity);

        return [
            'from' => $fromInventory,
            'to' => $toInventory,
        ];
    }

    /**
     * Get total stock across all locations for a product
     */
    public static function getTotalStock($businessId, $productId)
    {
        return static::where('business_id', $businessId)
                     ->where('product_id', $productId)
                     ->sum('quantity');
    }

    /**
     * Get inventory summary by location
     */
    public static function getSummaryByLocation($businessId)
    {
        return static::where('inventory.business_id', $businessId)
                     ->join('locations', 'inventory.location_id', '=', 'locations.id')
                     ->join('products', 'inventory.product_id', '=', 'products.id')
                     ->groupBy('locations.id', 'locations.name')
                     ->select(
                         'locations.id',
                         'locations.name',
                         \DB::raw('COUNT(inventory.id) as product_count'),
                         \DB::raw('SUM(inventory.quantity) as total_quantity'),
                         \DB::raw('SUM(inventory.quantity * products.cost_price) as total_value')
                     )
                     ->get();
    }
}