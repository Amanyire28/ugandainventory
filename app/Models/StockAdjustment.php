<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo};
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'product_id',
        'stock_taking_session_id',
        'adjustment_date',
        'physical_count',
        'system_quantity',
        'variance',
        'adjustment_quantity',
        'reason',
        'notes',
        'status',
        'recorded_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'adjustment_date' => 'datetime',
        'approved_at' => 'datetime',
        'physical_count' => 'decimal:2',
        'system_quantity' => 'decimal:2',
        'variance' => 'decimal:2',
        'adjustment_quantity' => 'decimal:2',
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

    public function session(): BelongsTo
    {
        return $this->belongsTo(StockTakingSession::class, 'stock_taking_session_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
