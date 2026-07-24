<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessSubscription extends Model
{
    protected $fillable = [
        'business_id',
        'package_slug',
        'amount',
        'currency',
        'status',
        'payment_method',
        'reference',
        'notes',
        'period_start',
        'period_end',
        'paid_at',
        'recorded_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'paid_at'      => 'datetime',
        'amount'       => 'decimal:2',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_slug', 'slug');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'recorded_by');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeWithinDateRange($query, $start, $end)
    {
        return $query->whereBetween('paid_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'paid'      => 'green',
            'pending'   => 'yellow',
            'failed'    => 'red',
            'refunded'  => 'blue',
            'cancelled' => 'gray',
            default     => 'gray',
        };
    }
}
