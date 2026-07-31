<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTakingSession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'session_date',
        'notes',
        'status',
        'initiated_by',
        'closed_by',
        'closed_at',
        'period_month',
    ];

    protected $casts = [
        'session_date' => 'datetime',
        'closed_at'    => 'datetime',
        'period_month' => 'date',
    ];

    // Relationships
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function inventoryPeriods(): HasMany
    {
        return $this->hasMany(InventoryPeriod::class, 'stock_taking_session_id');
    }
}
