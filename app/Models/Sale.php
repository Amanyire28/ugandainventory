<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'location_id',
        'customer_id',
        'user_id',
        'sale_number',
        'sale_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'payment_status',
        'payment_method',
        'status',
        'void_reason',
        'voided_at',
        'voided_by',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'voided_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    // Scopes

    /**
     * Exclude voided sales from all financial queries.
     * Use this scope on every revenue, COGS, profit, and report query.
     */
    public function scopeNotVoided($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('status')->orWhere('status', '!=', 'voided');
        });
    }

    /**
     * Return only voided sales (for audit / traceability views).
     */
    public function scopeVoided($query)
    {
        return $query->where('status', 'voided');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('sale_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('sale_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('sale_date', now()->month)
                     ->whereYear('sale_date', now()->year);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    // Methods
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function getStatusBadgeColor(): string
    {
        return match($this->payment_status) {
            'paid' => 'green',
            'partial' => 'yellow',
            'unpaid' => 'red',
            default => 'gray',
        };
    }
}