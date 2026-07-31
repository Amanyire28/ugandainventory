<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\{BelongsTo};

class InventoryPeriod extends Model
{
    protected $fillable = [
        'business_id',
        'product_id',
        'period_start',
        'period_end',
        'opening_stock',
        'purchases',
        'sales',
        'adjustments',
        'calculated_stock',
        'physical_count',
        'closing_stock',
        'variance',
        'variance_percentage',
        'adjustment_value',
        'stock_taking_session_id',
        'is_locked',
        'opening_stock_value',
        'closing_stock_value',
        'purchases_value',
        'sales_cost_value',
        'status',
        'closed_by',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'period_start'           => 'date',
        'period_end'             => 'date',
        'opening_stock'          => 'decimal:2',
        'purchases'              => 'decimal:2',
        'sales'                  => 'decimal:2',
        'adjustments'            => 'decimal:2',
        'calculated_stock'       => 'decimal:2',
        'physical_count'         => 'decimal:2',
        'closing_stock'          => 'decimal:2',
        'variance'               => 'decimal:2',
        'variance_percentage'    => 'decimal:2',
        'adjustment_value'       => 'decimal:2',
        'opening_stock_value'    => 'decimal:2',
        'closing_stock_value'    => 'decimal:2',
        'purchases_value'        => 'decimal:2',
        'sales_cost_value'       => 'decimal:2',
        'is_locked'              => 'boolean',
        'closed_at'              => 'datetime',
    ];

    // =============================================
    // RELATIONSHIPS
    // =============================================

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(StockTakingSession::class, 'stock_taking_session_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    /** Only return locked (finalized) periods. */
    public function scopeLocked(Builder $query): Builder
    {
        return $query->where('is_locked', true);
    }

    /** Only return unlocked (draft) periods. */
    public function scopeUnlocked(Builder $query): Builder
    {
        return $query->where('is_locked', false);
    }

    /** Filter periods for a specific product. */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /** Filter periods for a specific business. */
    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    // =============================================
    // COMPUTED HELPERS
    // =============================================

    /** Returns true if a physical count was conducted and there is a non-zero variance. */
    public function hasVariance(): bool
    {
        return $this->physical_count !== null && abs((float) $this->variance) > 0.001;
    }

    /** Returns the positive variance amount (stock gain). Zero if not a gain. */
    public function getStockGainAttribute(): float
    {
        return (float) $this->variance > 0.001 ? (float) $this->variance : 0.0;
    }

    /** Returns the absolute negative variance amount (stock loss). Zero if not a loss. */
    public function getStockLossAttribute(): float
    {
        return (float) $this->variance < -0.001 ? abs((float) $this->variance) : 0.0;
    }

    /** Returns true when physical count equals system calculated (perfect reconciliation). */
    public function isPerfectMatch(): bool
    {
        return $this->physical_count !== null && abs((float) $this->variance) < 0.001;
    }

    /**
     * Find the latest locked period for this product before the given start date.
     * Used to determine the opening stock of a new period.
     */
    public static function getPreviousClosingStock(int $productId, string $periodStart): ?float
    {
        $previous = static::forProduct($productId)
            ->where('period_end', '<', $periodStart)
            ->orderBy('period_end', 'desc')
            ->first();

        return $previous ? (float) $previous->closing_stock : null;
    }
}
