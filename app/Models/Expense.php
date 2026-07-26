<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Expense extends Model
{
    protected $fillable = [
        'business_id',
        'location_id',
        'user_id',
        'spent_by',
        'purpose',
        'amount',
        'vat_amount',
        'date_spent',
        'notes',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'date_spent' => 'date',
    ];

    // Scopes
    public function scopeForBusiness(Builder $q, $businessId): Builder
    {
        return $q->where('business_id', $businessId);
    }

    public function scopeForUser(Builder $q, $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    public function scopeBetweenDates(Builder $q, $start, $end): Builder
    {
        return $q->whereBetween('date_spent', [$start, $end]);
    }

    public function scopeOnDate(Builder $q, $date): Builder
    {
        return $q->whereDate('date_spent', $date);
    }

    public function scopePurposeLike(Builder $q, ?string $term): Builder
    {
        if ($term) {
            $q->where('purpose', 'like', '%' . $term . '%');
        }
        return $q;
    }

    // Relations (optional, if you have User/Business models)
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function business()
    {
        return $this->belongsTo(\App\Models\Business::class);
    }

    public function location()
    {
        return $this->belongsTo(\App\Models\Location::class);
    }
}