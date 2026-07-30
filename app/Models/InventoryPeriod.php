<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        'status',
        'closed_by',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'opening_stock' => 'decimal:2',
        'purchases' => 'decimal:2',
        'sales' => 'decimal:2',
        'adjustments' => 'decimal:2',
        'calculated_stock' => 'decimal:2',
        'physical_count' => 'decimal:2',
        'closing_stock' => 'decimal:2',
        'variance' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    // Relationships
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
}
